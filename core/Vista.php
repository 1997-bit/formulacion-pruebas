<?php

declare(strict_types=1);

namespace App\Core;

// Vista dentro de un layout.
final class Vista
{
    /** @param array<string, mixed> $datos */
    public static function pagina(string $vista, array $datos = [], string $layout = 'layouts/app'): void
    {
        $datos['usuario'] ??= Sesion::usuario();
        $datos['contenido'] = self::capturar($vista, $datos);
        echo self::capturar($layout, $datos);
    }

    /** @param array<string, mixed> $datos */
    public static function capturar(string $vista, array $datos = []): string
    {
        $archivo = RAIZ . '/views/' . $vista . '.php';
        if (!is_file($archivo)) {
            throw new \RuntimeException("Vista no encontrada: {$vista}");
        }

        ob_start();
        (static function (string $__archivo, array $__datos): void {
            extract($__datos, EXTR_SKIP);
            require $__archivo;
        })($archivo, $datos);

        return (string) ob_get_clean();
    }
}
