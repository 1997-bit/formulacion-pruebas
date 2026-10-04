<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;
use App\Core\Paginacion;

final class CasoModelo
{
    /**
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @param array{proyecto: ?int, requerimiento: ?int, estado: ?int} $filtros
     */
    public static function contar(?array $proyectos, array $filtros): int
    {
        [$donde, $valores] = self::donde($proyectos, $filtros);
        $sql = Conexion::pdo()->prepare('SELECT COUNT(*) FROM casos_prueba c' . $donde);
        $sql->execute($valores);

        return (int) $sql->fetchColumn();
    }

    /**
     * $offset null: todos. Orden del índice (proyecto_id, sigla, numero): sin filesort.
     * Con $offset, la subconsulta salta solo por el índice y el resto se lee para 20 filas (#107).
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @param array{proyecto: ?int, requerimiento: ?int, estado: ?int} $filtros
     * @return list<array<string, mixed>>
     */
    public static function listar(?array $proyectos, array $filtros, ?int $offset = null): array
    {
        [$donde, $valores] = self::donde($proyectos, $filtros);
        $orden = ' ORDER BY c.proyecto_id, c.sigla, c.numero';
        if ($offset !== null) {
            $desde = '(SELECT c.id FROM casos_prueba c' . $donde . $orden . ' LIMIT ' . Paginacion::POR_PAGINA . ' OFFSET ' . $offset . ')
                      k JOIN casos_prueba c ON c.id = k.id';
            $donde = '';
        }
        $sql = Conexion::pdo()->prepare(
            'SELECT c.id, c.codigo, c.objetivo, c.tipo_prueba, c.estado, p.nombre AS proyecto, u.nombre AS autor
             FROM ' . ($desde ?? 'casos_prueba c') . '
             JOIN proyectos p ON p.id = c.proyecto_id
             JOIN usuarios u ON u.id = c.creado_por' . $donde . $orden
        );
        $sql->execute($valores);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Por código exacto, en los proyectos permitidos. Varios si el código se repite entre proyectos (#111).
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @return list<array<string, mixed>>
     */
    public static function porCodigo(?array $proyectos, string $codigo): array
    {
        [$condiciones, $valores] = ProyectoModelo::permitidos('c.proyecto_id', $proyectos);
        $sql = Conexion::pdo()->prepare(
            'SELECT c.id, c.codigo, c.objetivo, p.nombre AS proyecto
             FROM casos_prueba c JOIN proyectos p ON p.id = c.proyecto_id
             WHERE ' . implode(' AND ', [...$condiciones, 'c.codigo = ?']) . '
             ORDER BY c.proyecto_id LIMIT ' . Paginacion::POR_PAGINA
        );
        $sql->execute([...$valores, $codigo]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public static function porId(int $id): ?array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT c.*, p.nombre AS proyecto, r.codigo AS requerimiento, r.descripcion AS requerimiento_descripcion,
                    u.nombre AS autor, a.nombre AS anotador
             FROM casos_prueba c
             JOIN proyectos p ON p.id = c.proyecto_id
             JOIN requerimientos r ON r.id = c.requerimiento_id
             JOIN usuarios u ON u.id = c.creado_por
             LEFT JOIN usuarios a ON a.id = c.anotado_por
             WHERE c.id = ?'
        );
        $sql->execute([$id]);

        return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Dentro de una transacción: los valores de antes para el historial.
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

    public static function anotar(int $id, int $estado, ?string $obtenido, ?string $observaciones, int $usuarioId): void
    {
        Conexion::pdo()->prepare(
            'UPDATE casos_prueba SET estado = ?, resultado_obtenido = ?, observaciones = ?, anotado_por = ?, anotado_en = NOW() WHERE id = ?'
        )->execute([$estado, $obtenido, $observaciones, $usuarioId, $id]);
    }

    // Dentro de una transacción.
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

    // False si tiene evidencias o incidentes. El historial se borra con él.
    public static function eliminar(int $id): bool
    {
        try {
            Conexion::pdo()->prepare('DELETE FROM casos_prueba WHERE id = ?')->execute([$id]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }

        return true;
    }

    /**
     * RF-22: los filtros se suman al permiso. Solo entran los filtros usados.
     *
     * @param list<int>|null $proyectos
     * @param array{proyecto: ?int, requerimiento: ?int, estado: ?int} $filtros
     * @return array{0: string, 1: list<int>}
     */
    private static function donde(?array $proyectos, array $filtros): array
    {
        [$condiciones, $valores] = ProyectoModelo::permitidos('c.proyecto_id', $proyectos);
        foreach (['proyecto' => 'c.proyecto_id', 'requerimiento' => 'c.requerimiento_id', 'estado' => 'c.estado'] as $filtro => $columna) {
            if ($filtros[$filtro] !== null) {
                $condiciones[] = "{$columna} = ?";
                $valores[] = $filtros[$filtro];
            }
        }

        return [$condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones), $valores];
    }
}
