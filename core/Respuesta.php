<?php

declare(strict_types=1);

namespace App\Core;

// Redirecciones, páginas de error y JSON.
final class Respuesta
{
    public static function redirigir(string $ruta): never
    {
        throw new \LogicException('Pendiente');
    }

    public static function error(int $codigo): never
    {
        throw new \LogicException('Pendiente');
    }

    public static function json(mixed $datos, int $codigo = 200): never
    {
        throw new \LogicException('Pendiente');
    }
}
