<?php

declare(strict_types=1);

namespace App\Core;

// Un token por sesión.
final class Csrf
{
    public static function token(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    // Uno por formulario mostrado. Ver Respuesta::exito().
    public static function envio(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function valido(?string $token): bool
    {
        return $token !== null && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }
}
