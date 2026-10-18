<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;
use App\Core\Paginacion;

final class IncidenteModelo
{
    /**
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @param array<string, int|string|null> $filtros IncidenteServicio::filtros()
     */
    public static function contar(?array $proyectos, array $filtros = []): int
    {
        [$permiso, $valores] = self::donde($proyectos, $filtros);
        $sql = Conexion::pdo()->prepare('SELECT COUNT(*) FROM incidentes i WHERE ' . $permiso);
        $sql->execute($valores);

        return (int) $sql->fetchColumn();
    }

    /**
     * Abiertos primero, luego stopper, lo más grave y lo más nuevo
     *
     * Sin $offset: todos hasta $limite, tablero
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @param array<string, int|string|null> $filtros IncidenteServicio::filtros()
     * @return list<array<string, mixed>>
     */
    public static function listar(?array $proyectos, ?int $offset, array $filtros = [], int $limite = Paginacion::POR_PAGINA): array
    {
        [$permiso, $valores] = self::donde($proyectos, $filtros);
        $orden = ' ORDER BY i.estado = 2, i.es_stopper DESC, i.severidad DESC, i.creado_en DESC, i.id DESC';
        // Subconsulta solo del índice, #107
        $sql = Conexion::pdo()->prepare(
            'SELECT i.id, i.codigo, i.titulo, i.severidad, i.prioridad, i.estado, i.es_stopper, i.asignado_id, i.creado_en,
                    c.id AS caso_id, c.codigo AS caso, p.nombre AS proyecto, a.nombre AS asignado
             FROM (SELECT i.id FROM incidentes i WHERE ' . $permiso . $orden
                . ' LIMIT ' . $limite . ' OFFSET ' . ($offset ?? 0) . ') k
             JOIN incidentes i ON i.id = k.id
             JOIN casos_prueba c ON c.id = i.caso_id
             JOIN proyectos p ON p.id = i.proyecto_id
             LEFT JOIN usuarios a ON a.id = i.asignado_id' . $orden
        );
        $sql->execute($valores);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Abiertos del usuario, lo más grave primero
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @return list<array<string, mixed>>
     */
    public static function asignadosA(int $usuarioId, ?array $proyectos, int $limite): array
    {
        [$permiso, $valores] = ProyectoModelo::permitidos('i.proyecto_id', $proyectos);
        $sql = Conexion::pdo()->prepare(
            'SELECT i.id, i.codigo, i.titulo, i.severidad, i.estado, i.es_stopper
             FROM incidentes i
             WHERE ' . $permiso . ' AND i.asignado_id = ? AND i.estado <> 2
             ORDER BY i.severidad DESC, i.id LIMIT ' . $limite
        );
        $sql->execute([...$valores, $usuarioId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public static function porId(int $id): ?array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT i.*, c.codigo AS caso, c.objetivo AS caso_objetivo, p.nombre AS proyecto,
                    u.nombre AS autor, a.nombre AS asignado
             FROM incidentes i
             JOIN casos_prueba c ON c.id = i.caso_id
             JOIN proyectos p ON p.id = i.proyecto_id
             JOIN usuarios u ON u.id = i.creado_por
             LEFT JOIN usuarios a ON a.id = i.asignado_id
             WHERE i.id = ?'
        );
        $sql->execute([$id]);

        return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** @param array{estado: int, asignado_id: ?int, es_stopper: int, commit_corregido: ?string} $s */
    public static function actualizar(int $id, array $s): void
    {
        Conexion::pdo()->prepare('UPDATE incidentes SET estado = ?, asignado_id = ?, es_stopper = ?, commit_corregido = ? WHERE id = ?')
            ->execute([$s['estado'], $s['asignado_id'], $s['es_stopper'], $s['commit_corregido'], $id]);
    }

    // Stoppers abiertos: impiden cerrar el plan
    public static function stoppersAbiertos(int $proyectoId): int
    {
        $sql = Conexion::pdo()->prepare('SELECT COUNT(*) FROM incidentes WHERE proyecto_id = ? AND es_stopper = 1 AND estado <> 2');
        $sql->execute([$proyectoId]);

        return (int) $sql->fetchColumn();
    }

    // Abiertos del caso: impiden OK, BUG-048. FOR UPDATE en la transacción
    public static function abiertosDeCaso(int $casoId): int
    {
        $sql = Conexion::pdo()->prepare('SELECT COUNT(*) FROM (SELECT id FROM incidentes WHERE caso_id = ? AND estado <> 2 FOR UPDATE) a');
        $sql->execute([$casoId]);

        return (int) $sql->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    public static function deCaso(int $casoId): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT id, codigo, titulo, severidad, estado, es_stopper FROM incidentes WHERE caso_id = ? ORDER BY numero'
        );
        $sql->execute([$casoId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    // En transacción
    public static function siguienteNumero(int $proyectoId): int
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT COALESCE(MAX(numero), 0) + 1 FROM incidentes WHERE proyecto_id = ? FOR UPDATE'
        );
        $sql->execute([$proyectoId]);

        return (int) $sql->fetchColumn();
    }

    /** @param array<string, mixed> $incidente */
    public static function crear(array $incidente): int
    {
        $sql = Conexion::pdo()->prepare(
            'INSERT INTO incidentes (' . implode(', ', array_keys($incidente)) . ')
             VALUES (' . implode(', ', array_fill(0, count($incidente), '?')) . ')'
        );
        $sql->execute(array_values($incidente));

        return (int) Conexion::pdo()->lastInsertId();
    }

    /**
     * Permiso y filtros: abiertos, q, estado, severidad, stopper = 1, asignado = id
     *
     * @param list<int>|null $proyectos
     * @param array<string, int|string|null> $filtros
     * @return array{0: string, 1: list<int|string>}
     */
    private static function donde(?array $proyectos, array $filtros): array
    {
        [$permiso, $valores] = ProyectoModelo::permitidos('i.proyecto_id', $proyectos);
        if (($filtros['abiertos'] ?? null) !== null) {
            $permiso .= ' AND i.estado <> 2';
        }
        if (($filtros['q'] ?? null) !== null) {
            // Código o parte del título. % y _ como texto
            $permiso .= ' AND (i.codigo LIKE ? OR i.titulo LIKE ?)';
            $patron = '%' . addcslashes((string) $filtros['q'], '%_\\') . '%';
            array_push($valores, $patron, $patron);
        }
        foreach (['estado' => 'i.estado', 'severidad' => 'i.severidad', 'stopper' => 'i.es_stopper', 'asignado' => 'i.asignado_id'] as $filtro => $columna) {
            if (($filtros[$filtro] ?? null) !== null) {
                $permiso .= " AND {$columna} = ?";
                $valores[] = $filtros[$filtro];
            }
        }

        return [$permiso, $valores];
    }
}
