<?php

declare(strict_types=1);

namespace App\Helpers;

// SVG de Lucide en línea, no <img>: toma el color del texto.
final class Icono
{
    public static function svg(string $nombre, string $clase = 'icono'): string
    {
        $archivo = RAIZ . '/public/assets/icons/' . basename($nombre) . '.svg';
        if (!is_file($archivo)) {
            throw new \InvalidArgumentException("Icono no encontrado: {$nombre}");
        }

        $svg = preg_replace('/<!--.*?-->\s*/s', '', (string) file_get_contents($archivo));

        return (string) preg_replace(
            '/class="[^"]*"/',
            'class="' . htmlspecialchars($clase) . '" aria-hidden="true" focusable="false"',
            (string) $svg,
            1
        );
    }
}
