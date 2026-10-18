<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;
use App\Core\ErrorValidacion;
use App\Core\Paginacion;

final class CasoModelo
{
    /**
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @param array{proyecto: ?int, requerimiento: ?int, estado: ?int, desde: ?string, hasta: ?string} $filtros
     */
    public static function contar(?array $proyectos, array $filtros): int
    {
        [$donde, $valores] = self::donde($proyectos, $filtros);
        $sql = Conexion::pdo()->prepare('SELECT COUNT(*) FROM casos_prueba c' . $donde);
        $sql->execute($valores);

        return (int) $sql->fetchColumn();
    }

    /**
     * Una página. Orden del índice proyecto_id, sigla, numero: sin filesort
     * La subconsulta lee solo el índice, #107
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @param array{proyecto: ?int, requerimiento: ?int, estado: ?int, desde: ?string, hasta: ?string} $filtros
     * @return list<array<string, mixed>>
     */
    public static function listar(?array $proyectos, array $filtros, int $offset): array
    {
        [$donde, $valores] = self::donde($proyectos, $filtros);
        $orden = ' ORDER BY c.proyecto_id, c.sigla, c.numero';
        $sql = Conexion::pdo()->prepare(
            'SELECT c.id, c.codigo, c.objetivo, c.tipo_prueba, c.estado, p.nombre AS proyecto, u.nombre AS autor
             FROM (SELECT c.id FROM casos_prueba c' . $donde . $orden . ' LIMIT ' . Paginacion::POR_PAGINA . ' OFFSET ' . $offset . ') k
             JOIN casos_prueba c ON c.id = k.id
             JOIN proyectos p ON p.id = c.proyecto_id
             JOIN usuarios u ON u.id = c.creado_por' . $orden
        );
        $sql->execute($valores);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Por código exacto. Varios si se repite entre proyectos, #111
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @return list<array<string, mixed>>
     */
    public static function porCodigo(?array $proyectos, string $codigo): array
    {
        [$permiso, $valores] = ProyectoModelo::permitidos('c.proyecto_id', $proyectos);
        $sql = Conexion::pdo()->prepare(
            'SELECT c.id, c.codigo, c.objetivo, p.nombre AS proyecto
             FROM casos_prueba c JOIN proyectos p ON p.id = c.proyecto_id
             WHERE ' . $permiso . ' AND c.codigo = ?
             ORDER BY c.proyecto_id LIMIT ' . Paginacion::POR_PAGINA
        );
        $sql->execute([...$valores, $codigo]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * FAULT para registrar incidente. Sin incidente abierto primero, luego lo más reciente
     * $texto: parte del código o del objetivo
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @return list<array<string, mixed>>
     */
    public static function fallidos(?array $proyectos, string $texto, int $limite): array
    {
        [$permiso, $valores] = ProyectoModelo::permitidos('c.proyecto_id', $proyectos);
        if ($texto !== '') {
            $permiso .= ' AND (c.codigo LIKE ? OR c.objetivo LIKE ?)';
            $patron = '%' . addcslashes($texto, '%_\\') . '%';
            array_push($valores, $patron, $patron);
        }
        $sql = Conexion::pdo()->prepare(
            'SELECT c.id, c.codigo, c.objetivo, c.anotado_en, p.nombre AS proyecto,
                    (SELECT COUNT(*) FROM incidentes i WHERE i.caso_id = c.id AND i.estado <> 2) AS abiertos
             FROM casos_prueba c JOIN proyectos p ON p.id = c.proyecto_id
             WHERE ' . $permiso . ' AND c.estado = 2
             ORDER BY abiertos > 0, c.anotado_en DESC, c.id DESC LIMIT ' . $limite
        );
        $sql->execute($valores);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Tablero y cronograma. Sin paginar, hasta $limite. $rango: solo los que tocan esas fechas
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @param array{proyecto: ?int, requerimiento: ?int, estado: ?int, desde: ?string, hasta: ?string} $filtros
     * @param array{0: string, 1: string}|null $rango fechas ISO, inicio y fin
     * @return list<array<string, mixed>>
     */
    public static function sinPaginar(?array $proyectos, array $filtros, ?array $rango, int $limite): array
    {
        [$donde, $valores] = self::donde($proyectos, $filtros);
        if ($rango !== null) {
            $donde .= ' AND c.fecha_inicio <= ? AND c.fecha_fin >= ?';
            $valores = [...$valores, $rango[1], $rango[0]];
        }
        $sql = Conexion::pdo()->prepare(
            'SELECT c.id, c.codigo, c.objetivo, c.estado, c.fecha_inicio, c.fecha_fin, DATE(c.anotado_en) AS anotado,
                    p.nombre AS proyecto, r.codigo AS requerimiento, r.descripcion AS requerimiento_descripcion,
                    (SELECT COUNT(*) FROM incidentes i WHERE i.caso_id = c.id AND i.estado <> 2) AS incidentes
             FROM casos_prueba c
             JOIN proyectos p ON p.id = c.proyecto_id
             JOIN requerimientos r ON r.id = c.requerimiento_id' . $donde . '
             ORDER BY c.proyecto_id, r.codigo, c.fecha_inicio, c.sigla, c.numero LIMIT ' . $limite
        );
        $sql->execute($valores);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Pendientes vencidos, el más atrasado primero
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @return list<array<string, mixed>>
     */
    public static function atrasados(?array $proyectos, int $limite): array
    {
        [$permiso, $valores] = ProyectoModelo::permitidos('c.proyecto_id', $proyectos);
        $sql = Conexion::pdo()->prepare(
            'SELECT c.id, c.codigo, c.objetivo, c.fecha_fin, c.creado_por
             FROM casos_prueba c
             WHERE ' . $permiso . ' AND c.estado = 0 AND c.fecha_fin < CURDATE()
             ORDER BY c.fecha_fin, c.id LIMIT ' . $limite
        );
        $sql->execute($valores);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Pendientes del usuario sin vencer, el más próximo primero
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @return list<array<string, mixed>>
     */
    public static function pendientesDe(?array $proyectos, int $usuarioId, int $limite): array
    {
        [$permiso, $valores] = ProyectoModelo::permitidos('c.proyecto_id', $proyectos);
        $sql = Conexion::pdo()->prepare(
            'SELECT c.id, c.codigo, c.objetivo, c.fecha_fin
             FROM casos_prueba c
             WHERE ' . $permiso . ' AND c.estado = 0 AND c.creado_por = ? AND c.fecha_fin >= CURDATE()
             ORDER BY c.fecha_fin, c.id LIMIT ' . $limite
        );
        $sql->execute([...$valores, $usuarioId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Anterior y siguiente del mismo proyecto, orden de la lista
     * OR y no (sigla, numero) > (?, ?): usa el índice único
     *
     * @param array<string, mixed> $caso
     * @return array{0: ?array<string, mixed>, 1: ?array<string, mixed>}
     */
    public static function vecinos(array $caso): array
    {
        $uno = function (string $signo, string $orden) use ($caso): ?array {
            $sql = Conexion::pdo()->prepare(
                "SELECT id, codigo FROM casos_prueba
                 WHERE proyecto_id = ? AND (sigla {$signo} ? OR (sigla = ? AND numero {$signo} ?))
                 ORDER BY sigla {$orden}, numero {$orden} LIMIT 1"
            );
            $sql->execute([$caso['proyecto_id'], $caso['sigla'], $caso['sigla'], $caso['numero']]);

            return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
        };

        return [$uno('<', 'DESC'), $uno('>', 'ASC')];
    }

    /** @return array<string, mixed>|null */
    public static function porId(int $id): ?array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT c.*, p.nombre AS proyecto, r.codigo AS requerimiento, r.descripcion AS requerimiento_descripcion,
                    u.nombre AS autor, a.nombre AS anotador, s.nombre AS solicitante, b.nombre AS aprobador
             FROM casos_prueba c
             JOIN proyectos p ON p.id = c.proyecto_id
             JOIN requerimientos r ON r.id = c.requerimiento_id
             JOIN usuarios u ON u.id = c.creado_por
             LEFT JOIN usuarios a ON a.id = c.anotado_por
             LEFT JOIN usuarios s ON s.id = c.solicitado_por
             LEFT JOIN usuarios b ON b.id = c.aprobado_por
             WHERE c.id = ?'
        );
        $sql->execute([$id]);

        return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * En transacción. Da los valores de antes para el historial
     *
     * @return array<string, mixed>|null
     */
    public static function bloquear(int $id): ?array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT c.*, r.codigo AS requerimiento
             FROM casos_prueba c JOIN requerimientos r ON r.id = c.requerimiento_id
             WHERE c.id = ? FOR UPDATE'
        );
        $sql->execute([$id]);

        return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** @param array<string, mixed> $campos */
    public static function actualizar(int $id, array $campos): void
    {
        Conexion::pdo()->prepare(
            'UPDATE casos_prueba SET ' . implode(' = ?, ', array_keys($campos)) . ' = ? WHERE id = ?'
        )->execute([...array_values($campos), $id]);
    }

    /** @param array{estado: int, resultado_obtenido: ?string, observaciones: ?string, impacto: ?string, commit_probado: ?string} $r */
    public static function anotar(int $id, array $r, int $usuarioId): void
    {
        Conexion::pdo()->prepare(
            'UPDATE casos_prueba SET estado = ?, resultado_obtenido = ?, observaciones = ?, impacto = ?, commit_probado = ?, anotado_por = ?, anotado_en = NOW() WHERE id = ?'
        )->execute([$r['estado'], $r['resultado_obtenido'], $r['observaciones'], $r['impacto'], $r['commit_probado'], $usuarioId, $id]);
    }

    /**
     * Commits del proyecto, el más reciente primero. Para el datalist
     *
     * @return list<string>
     */
    public static function commits(int $proyectoId): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT h FROM (
                SELECT commit_probado AS h, anotado_en AS f FROM casos_prueba WHERE proyecto_id = ? AND commit_probado IS NOT NULL
                UNION ALL SELECT commit_afectado, creado_en FROM incidentes WHERE proyecto_id = ?
                UNION ALL SELECT commit_corregido, creado_en FROM incidentes WHERE proyecto_id = ? AND commit_corregido IS NOT NULL
             ) t GROUP BY h ORDER BY MAX(f) DESC LIMIT 10'
        );
        $sql->execute([$proyectoId, $proyectoId, $proyectoId]);

        return $sql->fetchAll(\PDO::FETCH_COLUMN);
    }

    // En transacción
    public static function siguienteNumero(int $proyectoId, string $sigla): int
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT COALESCE(MAX(numero), 0) + 1 FROM casos_prueba WHERE proyecto_id = ? AND sigla = ? FOR UPDATE'
        );
        $sql->execute([$proyectoId, $sigla]);

        return (int) $sql->fetchColumn();
    }

    /** @param array<string, mixed> $caso */
    public static function crear(array $caso): int
    {
        $sql = Conexion::pdo()->prepare(
            'INSERT INTO casos_prueba (' . implode(', ', array_keys($caso)) . ')
             VALUES (' . implode(', ', array_fill(0, count($caso), '?')) . ')'
        );
        $sql->execute(array_values($caso));

        return (int) Conexion::pdo()->lastInsertId();
    }

    // False con evidencias o incidentes. El historial queda, ASVS 16.4.2
    public static function eliminar(int $id): bool
    {
        try {
            Conexion::pdo()->prepare('DELETE FROM casos_prueba WHERE id = ?')->execute([$id]);
        } catch (\PDOException $e) {
            if (ErrorValidacion::esLlaveForanea($e)) {
                return false;
            }
            throw $e;
        }

        return true;
    }

    /**
     * RF-22. Filtros + permiso
     *
     * @param list<int>|null $proyectos
     * @param array{proyecto: ?int, requerimiento: ?int, estado: ?int, desde: ?string, hasta: ?string} $filtros
     * @return array{0: string, 1: list<int|string>}
     */
    private static function donde(?array $proyectos, array $filtros): array
    {
        [$permiso, $valores] = ProyectoModelo::permitidos('c.proyecto_id', $proyectos);
        $condiciones = [$permiso];
        // Entra si su rango cruza el del filtro
        foreach (['proyecto' => 'c.proyecto_id = ?', 'requerimiento' => 'c.requerimiento_id = ?', 'estado' => 'c.estado = ?', 'desde' => 'c.fecha_fin >= ?', 'hasta' => 'c.fecha_inicio <= ?'] as $filtro => $condicion) {
            if ($filtros[$filtro] !== null) {
                $condiciones[] = $condicion;
                $valores[] = $filtros[$filtro];
            }
        }

        return [' WHERE ' . implode(' AND ', $condiciones), $valores];
    }
}
