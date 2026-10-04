<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

final class ProyectoModelo
{
    /** @return list<array<string, mixed>> */
    public static function listar(): array
    {
        return Conexion::pdo()->query(
            'SELECT p.id, p.nombre, p.descripcion, p.creado_en,
                    GROUP_CONCAT(u.usuario ORDER BY u.usuario SEPARATOR \', \') AS miembros
             FROM proyectos p
             LEFT JOIN proyecto_miembros m ON m.proyecto_id = p.id
             LEFT JOIN usuarios u ON u.id = m.usuario_id
             GROUP BY p.id
             ORDER BY p.nombre'
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public static function porId(int $id): ?array
    {
        $sql = Conexion::pdo()->prepare('SELECT id, nombre, descripcion FROM proyectos WHERE id = ?');
        $sql->execute([$id]);

        return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public static function idPorNombre(string $nombre): ?int
    {
        $sql = Conexion::pdo()->prepare('SELECT id FROM proyectos WHERE nombre = ?');
        $sql->execute([$nombre]);
        $id = $sql->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /** @return list<int> */
    public static function miembros(int $id): array
    {
        $sql = Conexion::pdo()->prepare('SELECT usuario_id FROM proyecto_miembros WHERE proyecto_id = ?');
        $sql->execute([$id]);

        return array_map('intval', $sql->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Condición del permiso de lista (RF-05). null es admin: TRUE, que el optimizador descarta.
     * Una lista de ids da range sobre el índice; EXISTS o "? = 1 OR" obligan a leer toda la tabla.
     *
     * @param list<int>|null $proyectos
     * @return array{0: string, 1: list<int>} condición y valores
     */
    public static function permitidos(string $columna, ?array $proyectos): array
    {
        return match (true) {
            $proyectos === null => ['TRUE', []],
            $proyectos === [] => ['FALSE', []],
            default => [$columna . ' IN (' . implode(', ', array_fill(0, count($proyectos), '?')) . ')', $proyectos],
        };
    }

    public static function esMiembro(int $id, int $usuarioId): bool
    {
        $sql = Conexion::pdo()->prepare('SELECT 1 FROM proyecto_miembros WHERE proyecto_id = ? AND usuario_id = ?');
        $sql->execute([$id, $usuarioId]);

        return (bool) $sql->fetchColumn();
    }

    /**
     * $id null: crea. Los miembros se reemplazan.
     *
     * @param list<int> $miembros
     */
    public static function guardar(?int $id, string $nombre, ?string $descripcion, array $miembros): void
    {
        $pdo = Conexion::pdo();
        $pdo->beginTransaction();
        try {
            if ($id === null) {
                $pdo->prepare('INSERT INTO proyectos (nombre, descripcion) VALUES (?, ?)')->execute([$nombre, $descripcion]);
                $id = (int) $pdo->lastInsertId();
            } else {
                $pdo->prepare('UPDATE proyectos SET nombre = ?, descripcion = ? WHERE id = ?')->execute([$nombre, $descripcion, $id]);
                $pdo->prepare('DELETE FROM proyecto_miembros WHERE proyecto_id = ?')->execute([$id]);
            }
            $sql = $pdo->prepare('INSERT INTO proyecto_miembros (proyecto_id, usuario_id) VALUES (?, ?)');
            foreach ($miembros as $usuarioId) {
                $sql->execute([$id, $usuarioId]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // False si tiene requerimientos o casos.
    public static function eliminar(int $id): bool
    {
        try {
            Conexion::pdo()->prepare('DELETE FROM proyectos WHERE id = ?')->execute([$id]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }

        return true;
    }
}
