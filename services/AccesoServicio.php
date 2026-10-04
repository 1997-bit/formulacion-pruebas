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

    // Clave al azar con UsuarioServicio::ARGON. Si el usuario no existe, se verifica contra este
    // hash: la respuesta tarda lo mismo y el tiempo no dice si existe.
    private const HASH_FALSO = '$argon2id$v=19$m=19456,t=2,p=1$bWNzL3R6ZksvY0hJejJ1VA$PRg4SvgQEEZ1uei56DJDKihodqvTtRg5/1TkrmdqpZo';

    /** @return array{id: int, nombre: string, usuario: string, rol: int, sesion_version: int} */
    public static function entrar(string $usuario, string $clave): array
    {
        $usuario = trim($usuario);
        $llave = mb_substr('u:' . $usuario, 0, 64);
        // Antes del hash: un intento bloqueado no gasta CPU.
        self::frenar($llave);

        $fila = UsuarioModelo::porUsuario($usuario);
        if (!password_verify($clave, $fila['clave'] ?? self::HASH_FALSO) || $fila === null) {
            IntentoModelo::fallar($llave);
            throw new ErrorValidacion(['general' => self::ERROR_ENTRAR]);
        }
        IntentoModelo::borrar($llave);
        // Las claves con parámetros viejos se actualizan al entrar.
        if (password_needs_rehash($fila['clave'], PASSWORD_ARGON2ID, UsuarioServicio::ARGON)) {
            UsuarioModelo::cambiarClave($fila['id'], password_hash($clave, PASSWORD_ARGON2ID, UsuarioServicio::ARGON));
        }

        return ['id' => $fila['id'], 'nombre' => $fila['nombre'], 'usuario' => $fila['usuario'], 'rol' => $fila['rol'], 'sesion_version' => $fila['sesion_version']];
    }

    /**
     * Cada petición: el usuario con sus proyectos, o null si ya no existe o cambió su clave o su rol (BUG-003, BUG-023).
     *
     * @param array<string, mixed> $sesion
     * @return array{id: int, nombre: string, usuario: string, rol: int, sesion_version: int, proyectos: list<int>}|null
     */
    public static function vigente(array $sesion): ?array
    {
        $fila = UsuarioModelo::deSesion((int) $sesion['id']);

        return $fila !== null && $fila['sesion_version'] === ($sesion['sesion_version'] ?? null) ? $fila : null;
    }

    // Mensaje genérico: no dice cuánto falta.
    private static function frenar(string $llave): void
    {
        if (IntentoModelo::bloqueado($llave)) {
            throw new ErrorValidacion(['general' => 'Demasiados intentos. Espere un momento.']);
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
