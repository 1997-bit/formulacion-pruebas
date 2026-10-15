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

    // Hace cuánto: "hace 5 min", "hace 2 h", "ayer", "hace 3 d". Desde 7 días: la fecha legible.
    public static function relativa(\DateTimeInterface $fecha, \DateTimeInterface $ahora): string
    {
        $segundos = $ahora->getTimestamp() - $fecha->getTimestamp();
        $dias = (int) (new \DateTimeImmutable($ahora->format('Y-m-d')))->diff(new \DateTimeImmutable($fecha->format('Y-m-d')))->days;

        return match (true) {
            $segundos < 60 => 'hace un momento',
            $segundos < 3600 => 'hace ' . intdiv($segundos, 60) . ' min',
            $dias === 0 => 'hace ' . intdiv($segundos, 3600) . ' h',
            $dias === 1 => 'ayer',
            $dias < 7 => "hace {$dias} d",
            default => self::legible($fecha),
        };
    }
}
