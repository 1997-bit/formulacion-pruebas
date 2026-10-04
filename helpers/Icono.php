<?php

declare(strict_types=1);

namespace App\Helpers;

// SVG de Lucide en línea, no <img>: toma el color del texto.
final class Icono
{
    /** @var array<string, string> Cada SVG se lee una vez por petición (#124). */
    private static array $leidos = [];

    public static function svg(string $nombre, string $clase = 'icono'): string
    {
        $svg = self::$leidos[$nombre] ??= self::leer($nombre);

        return (string) preg_replace(
            '/class="[^"]*"/',
            'class="' . htmlspecialchars($clase) . '" aria-hidden="true" focusable="false"',
            $svg,
            1
        );
    }

    private static function leer(string $nombre): string
    {
        $archivo = RAIZ . '/public/assets/icons/' . basename($nombre) . '.svg';
        if (!is_file($archivo)) {
            throw new \InvalidArgumentException("Icono no encontrado: {$nombre}");
        }

        return (string) preg_replace('/<!--.*?-->\s*/s', '', (string) file_get_contents($archivo));
    }
}
