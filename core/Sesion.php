<?php

declare(strict_types=1);

namespace App\Core;

// Usuario en sesión y mensajes flash.
final class Sesion
{
    public static function iniciar(): void
    {
        throw new \LogicException('Pendiente');
    }

    /** @param array<string, mixed> $usuario */
    public static function entrar(array $usuario): void
    {
        throw new \LogicException('Pendiente');
    }

    public static function salir(): void
    {
        throw new \LogicException('Pendiente');
    }

    /** @return array<string, mixed>|null */
    public static function usuario(): ?array
    {
        throw new \LogicException('Pendiente');
    }

    public static function flash(string $mensaje): void
    {
        throw new \LogicException('Pendiente');
    }

    public static function tomarFlash(): ?string
    {
        throw new \LogicException('Pendiente');
    }
}
