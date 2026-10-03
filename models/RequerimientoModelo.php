<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;
use App\Core\Paginacion;

final class RequerimientoModelo
{
    // Tester: solo sus proyectos.
    private const PERMITIDO = '(? = 1 OR EXISTS (SELECT 1 FROM proyecto_miembros m WHERE m.proyecto_id = p.id AND m.usuario_id = ?))';

    /**
     * $offset null: todos.
     *
     * @return list<array<string, mixed>>
     */
    public static function listar(int $usuarioId, bool $admin, ?int $offset = null): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT r.id, r.proyecto_id, r.codigo, r.descripcion, r.no_funcional, p.nombre AS proyecto,
                    (SELECT COUNT(*) FROM casos_prueba c WHERE c.requerimiento_id = r.id) AS casos
             FROM requerimientos r
             JOIN proyectos p ON p.id = r.proyecto_id
             WHERE ' . self::PERMITIDO . '
             ORDER BY p.nombre, r.no_funcional, CAST(SUBSTRING_INDEX(r.codigo, \'-\', -1) AS UNSIGNED)'
             . ($offset === null ? '' : ' LIMIT ' . Paginacion::POR_PAGINA . ' OFFSET ' . $offset)
        );
        $sql->execute([(int) $admin, $usuarioId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function contar(int $usuarioId, bool $admin): int
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT COUNT(*) FROM requerimientos r JOIN proyectos p ON p.id = r.proyecto_id WHERE ' . self::PERMITIDO
        );
        $sql->execute([(int) $admin, $usuarioId]);

        return (int) $sql->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    public static function proyectosPermitidos(int $usuarioId, bool $admin): array
    {
        $sql = Conexion::pdo()->prepare('SELECT p.id, p.nombre FROM proyectos p WHERE ' . self::PERMITIDO . ' ORDER BY p.nombre');
        $sql->execute([(int) $admin, $usuarioId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public static function porId(int $id): ?array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT r.*, p.nombre AS proyecto FROM requerimientos r JOIN proyectos p ON p.id = r.proyecto_id WHERE r.id = ?'
        );
        $sql->execute([$id]);

        return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public static function existe(int $proyectoId, string $codigo): bool
    {
        $sql = Conexion::pdo()->prepare('SELECT 1 FROM requerimientos WHERE proyecto_id = ? AND codigo = ?');
        $sql->execute([$proyectoId, $codigo]);

        return (bool) $sql->fetchColumn();
    }

    public static function crear(int $proyectoId, string $codigo, string $descripcion, int $noFuncional): int
    {
        $sql = Conexion::pdo()->prepare(
            'INSERT INTO requerimientos (proyecto_id, codigo, descripcion, no_funcional) VALUES (?, ?, ?, ?)'
        );
        $sql->execute([$proyectoId, $codigo, $descripcion, $noFuncional]);

        return (int) Conexion::pdo()->lastInsertId();
    }
}
