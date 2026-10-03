<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorValidacion;
use App\Core\Validador;
use App\Models\UsuarioModelo;

// Inicio de sesión (RF-01) y registro público (RF-02).
final class AccesoServicio
{
    // Mismo mensaje si el usuario no existe o la clave está mal (RNF-02).
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

    // Siempre rol tester: el rol que llegue en el POST se ignora.
    public static function registrar(string $nombre, string $usuario, string $clave): int
    {
        $nombre = trim($nombre);
        $usuario = trim($usuario);

        (new Validador())
            ->requerido('nombre', $nombre)
            ->regla('nombre', mb_strlen($nombre) <= 100, 'Máximo 100 caracteres.')
            ->requerido('usuario', $usuario)
            ->regla('usuario', mb_strlen($usuario) <= 30, 'Máximo 30 caracteres.')
            ->regla('usuario', UsuarioModelo::porUsuario($usuario) === null, 'Ese usuario ya existe.')
            ->regla('clave', mb_strlen($clave) >= 8, 'Mínimo 8 caracteres.')
            ->comprobar();

        return UsuarioModelo::crear($nombre, $usuario, password_hash($clave, PASSWORD_ARGON2ID), 0);
    }
}
