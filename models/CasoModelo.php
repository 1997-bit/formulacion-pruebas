<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;
use App\Core\Paginacion;

final class CasoModelo
{
    // Tester: solo sus proyectos (RF-05).
    private const PERMITIDO = '(? = 1 OR EXISTS (SELECT 1 FROM proyecto_miembros m WHERE m.proyecto_id = p.id AND m.usuario_id = ?))';

    public static function contar(int $usuarioId, bool $admin): int
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT COUNT(*) FROM casos_prueba c JOIN proyectos p ON p.id = c.proyecto_id WHERE ' . self::PERMITIDO
        );
        $sql->execute([(int) $admin, $usuarioId]);

        return (int) $sql->fetchColumn();
    }

    /**
     * $offset null: todos.
     *
     * @return list<array<string, mixed>>
     */
    public static function listar(int $usuarioId, bool $admin, ?int $offset = null): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT c.id, c.codigo, c.objetivo, c.tipo_prueba, c.estado, p.nombre AS proyecto, u.nombre AS autor
             FROM casos_prueba c
             JOIN proyectos p ON p.id = c.proyecto_id
             JOIN usuarios u ON u.id = c.creado_por
             WHERE ' . self::PERMITIDO . '
             ORDER BY p.nombre, c.codigo'
             . ($offset === null ? '' : ' LIMIT ' . Paginacion::POR_PAGINA . ' OFFSET ' . $offset)
        );
        $sql->execute([(int) $admin, $usuarioId]);

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
