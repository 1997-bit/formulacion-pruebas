<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;
use App\Core\Paginacion;

final class IncidenteModelo
{
    // Tester: solo sus proyectos (RF-05).
    private const PERMITIDO = '(? = 1 OR EXISTS (SELECT 1 FROM proyecto_miembros m WHERE m.proyecto_id = i.proyecto_id AND m.usuario_id = ?))';

    public static function contar(int $usuarioId, bool $admin): int
    {
        $sql = Conexion::pdo()->prepare('SELECT COUNT(*) FROM incidentes i WHERE ' . self::PERMITIDO);
        $sql->execute([(int) $admin, $usuarioId]);

        return (int) $sql->fetchColumn();
    }

    /**
     * Abiertos primero; luego lo más grave.
     *
     * @return list<array<string, mixed>>
     */
    public static function listar(int $usuarioId, bool $admin, int $offset): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT i.id, i.codigo, i.titulo, i.severidad, i.prioridad, i.estado, i.es_stopper,
                    c.id AS caso_id, c.codigo AS caso, p.nombre AS proyecto, a.nombre AS asignado
             FROM incidentes i
             JOIN casos_prueba c ON c.id = i.caso_id
             JOIN proyectos p ON p.id = i.proyecto_id
             LEFT JOIN usuarios a ON a.id = i.asignado_id
             WHERE ' . self::PERMITIDO . '
             ORDER BY i.estado = 2, i.severidad DESC, p.nombre, i.codigo
             LIMIT ' . Paginacion::POR_PAGINA . ' OFFSET ' . $offset
        );
        $sql->execute([(int) $admin, $usuarioId]);

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

    /** @return list<array<string, mixed>> */
    public static function deCaso(int $casoId): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT id, codigo, titulo, severidad, estado, es_stopper FROM incidentes WHERE caso_id = ? ORDER BY codigo'
        );
        $sql->execute([$casoId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Dentro de una transacción.
    public static function siguienteNumero(int $proyectoId): int
    {
        $sql = Conexion::pdo()->prepare(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)), 0) + 1
             FROM incidentes WHERE proyecto_id = ? AND codigo LIKE 'BUG-%' FOR UPDATE"
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
}
