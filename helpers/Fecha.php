<?php

declare(strict_types=1);

namespace App\Helpers;

// RNF-06
final class Fecha
{
    // 15/05/2026
    public static function legible(\DateTimeInterface $fecha): string
    {
        return $fecha->format('d/m/Y');
    }
}
