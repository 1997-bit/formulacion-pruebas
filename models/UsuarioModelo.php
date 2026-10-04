<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

// Solo porUsuario() lee la clave: es para entrar.
final class UsuarioModelo
{
    /** @return array<string, mixed>|null */
    public static function porUsuario(string $usuario): ?array
    {
        $sql = Conexion::pdo()->prepare('SELECT id, nombre, usuario, clave, rol FROM usuarios WHERE usuario = ?');
        $sql->execute([$usuario]);

        return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** @return array<string, mixed>|null */
    public static function porId(int $id): ?array
    {
        $sql = Conexion::pdo()->prepare('SELECT id, nombre, usuario, rol FROM usuarios WHERE id = ?');
        $sql->execute([$id]);

        return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** @return list<array<string, mixed>> */
    public static function listar(): array
    {
        return Conexion::pdo()->query('SELECT id, nombre, usuario, rol, creado_en FROM usuarios ORDER BY nombre')->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    public static function testers(): array
    {
        return Conexion::pdo()->query('SELECT id, nombre, usuario FROM usuarios WHERE rol = 0 ORDER BY nombre')->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    public static function deProyecto(int $proyectoId): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT u.id, u.nombre FROM usuarios u JOIN proyecto_miembros m ON m.usuario_id = u.id WHERE m.proyecto_id = ? ORDER BY u.nombre'
        );
        $sql->execute([$proyectoId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function crear(string $nombre, string $usuario, string $clave, int $rol): int
    {
        $sql = Conexion::pdo()->prepare('INSERT INTO usuarios (nombre, usuario, clave, rol) VALUES (?, ?, ?, ?)');
        $sql->execute([$nombre, $usuario, $clave, $rol]);

        return (int) Conexion::pdo()->lastInsertId();
    }

    // Clave null: no la cambia. Una clave o un rol nuevo sube sesion_version; va primero porque compara el rol de antes.
    public static function actualizar(int $id, string $nombre, string $usuario, ?string $clave, int $rol): void
    {
        $sql = Conexion::pdo()->prepare(
            'UPDATE usuarios SET sesion_version = sesion_version + (? IS NOT NULL OR rol <> ?),
                nombre = ?, usuario = ?, rol = ?, clave = COALESCE(?, clave) WHERE id = ?'
        );
        $sql->execute([$clave, $rol, $nombre, $usuario, $rol, $clave, $id]);
    }

    public static function cambiarClave(int $id, string $clave): void
    {
        Conexion::pdo()->prepare('UPDATE usuarios SET clave = ? WHERE id = ?')->execute([$clave, $id]);
    }

    // False si tiene casos, evidencias u otros registros a su nombre.
    public static function eliminar(int $id): bool
    {
        try {
            Conexion::pdo()->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }

        return true;
    }
}
