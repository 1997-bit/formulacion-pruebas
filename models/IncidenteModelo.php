<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;
use App\Core\Paginacion;

final class IncidenteModelo
{
    /**
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @param array<string, ?int> $filtros IncidenteServicio::filtros()
     */
    public static function contar(?array $proyectos, array $filtros = []): int
    {
        [$permiso, $valores] = self::donde($proyectos, $filtros);
        $sql = Conexion::pdo()->prepare('SELECT COUNT(*) FROM incidentes i WHERE ' . $permiso);
        $sql->execute($valores);

        return (int) $sql->fetchColumn();
    }

    /**
     * Por proyecto: abiertos primero, luego lo más grave. Orden del índice: sin filesort.
     *
     * Sin $offset: todos hasta $limite, para el tablero.
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @param array<string, ?int> $filtros IncidenteServicio::filtros()
     * @return list<array<string, mixed>>
     */
    public static function listar(?array $proyectos, ?int $offset, array $filtros = [], int $limite = Paginacion::POR_PAGINA): array
    {
        [$permiso, $valores] = self::donde($proyectos, $filtros);
        $orden = ' ORDER BY i.proyecto_id, i.estado, i.severidad DESC, i.numero';
        // La subconsulta salta solo por el índice (#107).
        $sql = Conexion::pdo()->prepare(
            'SELECT i.id, i.codigo, i.titulo, i.severidad, i.prioridad, i.estado, i.es_stopper, i.asignado_id,
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
     * Abiertos asignados a un usuario, lo más grave primero.
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

    public static function actualizar(int $id, int $estado, ?int $asignadoId, int $stopper): void
    {
        Conexion::pdo()->prepare('UPDATE incidentes SET estado = ?, asignado_id = ?, es_stopper = ? WHERE id = ?')
            ->execute([$estado, $asignadoId, $stopper, $id]);
    }

    // Stoppers sin cerrar: impiden cerrar el plan.
    public static function stoppersAbiertos(int $proyectoId): int
    {
        $sql = Conexion::pdo()->prepare('SELECT COUNT(*) FROM incidentes WHERE proyecto_id = ? AND es_stopper = 1 AND estado <> 2');
        $sql->execute([$proyectoId]);

        return (int) $sql->fetchColumn();
    }

    // Sin cerrar del caso: impiden marcarlo OK (BUG-048). FOR UPDATE dentro de la transacción del resultado.
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

    // Dentro de una transacción.
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
     * Permiso y filtros usados: estado, severidad, stopper (1) y asignado (id).
     *
     * @param list<int>|null $proyectos
     * @param array<string, ?int> $filtros
     * @return array{0: string, 1: list<int>}
     */
    private static function donde(?array $proyectos, array $filtros): array
    {
        [$permiso, $valores] = ProyectoModelo::permitidos('i.proyecto_id', $proyectos);
        foreach (['estado' => 'i.estado', 'severidad' => 'i.severidad', 'stopper' => 'i.es_stopper', 'asignado' => 'i.asignado_id'] as $filtro => $columna) {
            if (($filtros[$filtro] ?? null) !== null) {
                $permiso .= " AND {$columna} = ?";
                $valores[] = $filtros[$filtro];
            }
        }

        return [$permiso, $valores];
    }
}
