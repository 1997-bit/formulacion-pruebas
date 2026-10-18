<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorValidacion;
use App\Core\Log;
use App\Models\IntentoModelo;
use App\Models\UsuarioModelo;

// RF-01. Solo el admin crea cuentas: UsuarioServicio
final class AccesoServicio
{
    // No dice si el usuario existe, RNF-02
    private const ERROR_ENTRAR = 'Usuario o contraseña incorrectos.';

    // Clave al azar con ARGON. Usuario inexistente: verifica contra este hash
    // Mismo tiempo: no revela si existe
    private const HASH_FALSO = '$argon2id$v=19$m=19456,t=2,p=1$bWNzL3R6ZksvY0hJejJ1VA$PRg4SvgQEEZ1uei56DJDKihodqvTtRg5/1TkrmdqpZo';

    /** @return array{id: int, nombre: string, usuario: string, rol: int, sesion_version: int} */
    public static function entrar(string $usuario, string $clave): array
    {
        $usuario = trim($usuario);
        $llave = mb_substr('u:' . $usuario, 0, 64);
        // Antes del hash: bloqueado no gasta CPU
        self::frenar($llave);

        $fila = UsuarioModelo::porUsuario($usuario);
        if (!password_verify($clave, $fila['clave'] ?? self::HASH_FALSO) || $fila === null) {
            IntentoModelo::fallar($llave);
            Log::evento('warning', 'authn_login_fail:{usuario}', ['usuario' => mb_substr($usuario, 0, 64)]);
            throw new ErrorValidacion(['general' => self::ERROR_ENTRAR]);
        }
        IntentoModelo::borrar($llave);
        Log::evento('info', 'authn_login_success:{usuario}', ['usuario' => $usuario]);
        // Parámetros viejos: rehash al entrar
        if (password_needs_rehash($fila['clave'], PASSWORD_ARGON2ID, UsuarioServicio::ARGON)) {
            UsuarioModelo::cambiarClave($fila['id'], password_hash($clave, PASSWORD_ARGON2ID, UsuarioServicio::ARGON));
        }

        return ['id' => $fila['id'], 'nombre' => $fila['nombre'], 'usuario' => $fila['usuario'], 'rol' => $fila['rol'], 'sesion_version' => $fila['sesion_version']];
    }

    /**
     * Usuario con proyectos. Null si no existe o cambió clave o rol. BUG-003, BUG-023
     *
     * @param array<string, mixed> $sesion
     * @return array{id: int, nombre: string, usuario: string, rol: int, sesion_version: int, proyectos: list<int>}|null
     */
    public static function vigente(array $sesion): ?array
    {
        $fila = UsuarioModelo::deSesion((int) $sesion['id']);

        return $fila !== null && $fila['sesion_version'] === ($sesion['sesion_version'] ?? null) ? $fila : null;
    }

    // Genérico: no dice cuánto falta
    private static function frenar(string $llave): void
    {
        if (IntentoModelo::bloqueado($llave)) {
            Log::evento('warning', 'authn_login_lock:{llave}', ['llave' => $llave]);
            throw new ErrorValidacion(['general' => 'Demasiados intentos. Espere un momento.']);
        }
    }
}
