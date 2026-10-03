<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

final class CasoModelo
{
    // Admin ve todo; tester, solo lo de los proyectos donde es miembro (RF-05).
    private const PERMITIDO = '(? = 1 OR EXISTS (SELECT 1 FROM proyecto_miembros m WHERE m.proyecto_id = p.id AND m.usuario_id = ?))';

    /** @return list<array<string, mixed>> */
    public static function listar(int $usuarioId, bool $admin): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT c.id, c.codigo, c.modulo, c.objetivo, c.tipo_prueba, c.subtecnica, c.estado, c.fecha_inicio, c.fecha_fin,
                    p.nombre AS proyecto, r.codigo AS requerimiento
             FROM casos_prueba c
             JOIN proyectos p ON p.id = c.proyecto_id
             JOIN requerimientos r ON r.id = c.requerimiento_id
             WHERE ' . self::PERMITIDO . '
             ORDER BY p.nombre, c.codigo'
        );
        $sql->execute([(int) $admin, $usuarioId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    public static function requerimientosPermitidos(int $usuarioId, bool $admin): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT r.id, r.codigo, r.descripcion, r.proyecto_id, p.nombre AS proyecto
             FROM requerimientos r
             JOIN proyectos p ON p.id = r.proyecto_id
             WHERE ' . self::PERMITIDO . '
             ORDER BY p.nombre, r.codigo'
        );
        $sql->execute([(int) $admin, $usuarioId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Siguiente consecutivo de la sigla en el proyecto. Llamar dentro de una transacción.
    public static function siguienteNumero(int $proyectoId, string $sigla): int
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, ?) AS UNSIGNED)), 0) + 1
             FROM casos_prueba WHERE proyecto_id = ? AND codigo LIKE ? FOR UPDATE'
        );
        $sql->execute([strlen($sigla) + 2, $proyectoId, $sigla . '-%']);

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
}
