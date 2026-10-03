<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

final class UsuarioModelo
{
    /** @return array<string, mixed>|null */
    public static function porUsuario(string $usuario): ?array
    {
        $sql = Conexion::pdo()->prepare('SELECT id, nombre, usuario, clave, rol FROM usuarios WHERE usuario = ?');
        $sql->execute([$usuario]);

        return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public static function crear(string $nombre, string $usuario, string $clave, int $rol): int
    {
        $sql = Conexion::pdo()->prepare('INSERT INTO usuarios (nombre, usuario, clave, rol) VALUES (?, ?, ?, ?)');
        $sql->execute([$nombre, $usuario, $clave, $rol]);

        return (int) Conexion::pdo()->lastInsertId();
    }
}
