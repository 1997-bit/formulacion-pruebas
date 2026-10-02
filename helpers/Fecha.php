<?php

declare(strict_types=1);

namespace App\Helpers;

// RNF-06: fecha legible para la interfaz. La base guarda ISO.
final class Fecha
{
    // Para pantallas, formularios y Word: 15/05/2026
    public static function legible(\DateTimeInterface $fecha): string
    {
        return $fecha->format('d/m/Y');
    }

    // Para pantallas con hora: 15/05/2026 14:30
    public static function legibleConHora(\DateTimeInterface $fecha): string
    {
        return $fecha->format('d/m/Y H:i');
    }
}
