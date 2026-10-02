<?php

declare(strict_types=1);

namespace App\Helpers;

final class Html
{
    // Escapa texto para imprimirlo en HTML. Usar en todo dato que venga del usuario o de la BD.
    public static function e(?string $texto): string
    {
        return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // Iniciales para el avatar: "Juan Garcia" -> "JG"
    public static function iniciales(string $nombre): string
    {
        $palabras = preg_split('/\s+/', trim($nombre), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
        $letras = array_map(fn (string $p): string => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($palabras, 0, 2));

        return implode('', $letras);
    }
}
