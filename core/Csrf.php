<?php

declare(strict_types=1);

namespace App\Core;

// Token contra CSRF para cada formulario POST.
final class Csrf
{
    public static function token(): string
    {
        throw new \LogicException('Pendiente');
    }

    public static function valido(?string $token): bool
    {
        throw new \LogicException('Pendiente');
    }
}
