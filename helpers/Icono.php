<?php

declare(strict_types=1);

namespace App\Helpers;

// Pega en el HTML un icono de public/assets/icons/ (Lucide, licencia ISC).
// Se pega en linea, no con <img>, para que tome el color del texto (currentColor) y cambie con el tema.
// Para agregar uno: descargar el .svg desde lucide.dev y guardarlo en esa carpeta.
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
