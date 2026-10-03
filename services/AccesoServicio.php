<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorValidacion;
use App\Models\UsuarioModelo;

// RF-01, RF-02
final class AccesoServicio
{
    // No dice si el usuario existe (RNF-02).
    private const ERROR_ENTRAR = 'Usuario o contraseña incorrectos.';

    /** @return array{id: int, nombre: string, usuario: string, rol: int} */
    public static function entrar(string $usuario, string $clave): array
    {
        $fila = UsuarioModelo::porUsuario(trim($usuario));
        if ($fila === null || !password_verify($clave, $fila['clave'])) {
            throw new ErrorValidacion(['general' => self::ERROR_ENTRAR]);
        }

        return ['id' => $fila['id'], 'nombre' => $fila['nombre'], 'usuario' => $fila['usuario'], 'rol' => $fila['rol']];
    }

    // Siempre tester. Un admin solo se crea en UsuarioServicio.
    public static function registrar(string $nombre, string $usuario, string $clave): int
    {
        $d = UsuarioServicio::validar(['nombre' => $nombre, 'usuario' => $usuario, 'clave' => $clave, 'rol' => '0'], null);

        return UsuarioModelo::crear($d['nombre'], $d['usuario'], password_hash($clave, PASSWORD_ARGON2ID), 0);
    }
}
