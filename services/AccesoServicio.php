<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorValidacion;
use App\Models\IntentoModelo;
use App\Models\UsuarioModelo;

// RF-01, RF-02
final class AccesoServicio
{
    // No dice si el usuario existe (RNF-02).
    private const ERROR_ENTRAR = 'Usuario o contraseña incorrectos.';

    /** @return array{id: int, nombre: string, usuario: string, rol: int} */
    public static function entrar(string $usuario, string $clave): array
    {
        $usuario = trim($usuario);
        $llave = mb_substr('u:' . $usuario, 0, 64);
        // Antes del hash: un intento bloqueado no gasta CPU.
        self::frenar($llave);

        $fila = UsuarioModelo::porUsuario($usuario);
        if ($fila === null || !password_verify($clave, $fila['clave'])) {
            IntentoModelo::fallar($llave);
            throw new ErrorValidacion(['general' => self::ERROR_ENTRAR]);
        }
        IntentoModelo::borrar($llave);
        // Las claves con parámetros viejos se actualizan al entrar.
        if (password_needs_rehash($fila['clave'], PASSWORD_ARGON2ID, UsuarioServicio::ARGON)) {
            UsuarioModelo::cambiarClave($fila['id'], password_hash($clave, PASSWORD_ARGON2ID, UsuarioServicio::ARGON));
        }

        return ['id' => $fila['id'], 'nombre' => $fila['nombre'], 'usuario' => $fila['usuario'], 'rol' => $fila['rol']];
    }

    private static function frenar(string $llave): void
    {
        $espera = IntentoModelo::espera($llave);
        if ($espera > 0) {
            throw new ErrorValidacion(['general' => "Demasiados intentos. Espere {$espera} s."]);
        }
    }

    // Siempre tester. Un admin solo se crea en UsuarioServicio.
    // Cada cuenta creada cuenta como un intento de la IP.
    public static function registrar(string $nombre, string $usuario, string $clave, string $ip): int
    {
        $llave = 'ip:' . $ip;
        self::frenar($llave);
        $d = UsuarioServicio::validar(['nombre' => $nombre, 'usuario' => $usuario, 'clave' => $clave, 'rol' => '0'], null);

        $id = UsuarioModelo::crear($d['nombre'], $d['usuario'], password_hash($clave, PASSWORD_ARGON2ID, UsuarioServicio::ARGON), 0);
        IntentoModelo::fallar($llave);

        return $id;
    }
}
