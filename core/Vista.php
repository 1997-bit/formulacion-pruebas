<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\Impresion;
use App\Services\Pdf;

// Vista dentro de un layout.
final class Vista
{
    /** @param array<string, mixed> $datos */
    public static function pagina(string $vista, array $datos = [], string $layout = 'layouts/app'): void
    {
        $datos['usuario'] ??= Sesion::usuario();
        // Documento imprimible con ?formato=pdf: el mismo HTML como PDF con metadatos.
        if (isset($datos['documento']) && ($_GET['formato'] ?? '') === 'pdf') {
            $metadatos = Impresion::metadatos($datos['documento'], $datos['usuario'], new \DateTimeImmutable(), $_SERVER['REQUEST_URI'] ?? '', Log::uid(), Impresion::version(RAIZ), (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
            $pdf = Pdf::generar(self::capturar($vista, $datos), $datos['documento'], $metadatos);
            Respuesta::pdf($pdf['archivo'], $pdf['nombre'], $pdf['sha256']);
        }
        $datos['contenido'] = self::capturar($vista, $datos);
        echo self::capturar($layout, $datos);
    }

    /**
     * Formularios > formulario > elemento. Con $paso, el elemento es un enlace a $ruta . $consulta y $paso va al final.
     *
     * @return list<array{texto: string, ruta?: string}>
     */
    public static function migas(string $formulario, string $ruta, string $elemento, string $paso = '', string $consulta = ''): array
    {
        $migas = [['texto' => 'Formularios'], ['texto' => $formulario, 'ruta' => $ruta], ['texto' => $elemento]];
        if ($paso !== '') {
            $migas[2]['ruta'] = $ruta . $consulta;
            $migas[] = ['texto' => $paso];
        }

        return $migas;
    }

    // CSS o JS con su versión en la URL: un cambio en el archivo cambia la URL (#122).
    public static function estatico(string $ruta): string
    {
        return $ruta . '?v=' . filemtime(RAIZ . '/public' . $ruta);
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
