<?php

declare(strict_types=1);

namespace App\Core;

final class Respuesta
{
    public static function redirigir(string $ruta): never
    {
        header('Location: ' . $ruta);
        exit;
    }

    public static function exito(string $mensaje, string $ruta): never
    {
        // Envío de un solo uso (#102): un segundo envío igual vuelve aquí sin repetir el guardado.
        if (is_string($_POST['envio'] ?? null)) {
            $_SESSION['envios'] = array_slice([$_POST['envio'] => $ruta] + ($_SESSION['envios'] ?? []), 0, 20, true);
        }
        Sesion::flash($mensaje);
        self::redirigir($ruta);
    }

    /**
     * @param array<string, string> $errores
     * @param array<string, mixed>  $datos
     */
    public static function errores(array $errores, array $datos, string $ruta): never
    {
        // Por ruta (#103): otro formulario no los toma.
        $_SESSION['errores'] = [$ruta => $errores];
        $_SESSION['datos'] = [$ruta => $datos];
        self::redirigir($ruta);
    }

    // nosniff: el navegador no adivina otro tipo (RNF-03).
    public static function archivo(string $ruta, string $tipo, string $nombre): never
    {
        // La descarga ya no toca la sesión: se libera y otra pestaña no espera a que termine (#120).
        session_write_close();
        // El nombre guardado no cambia con el contenido: sirve de ETag. private: solo el navegador la guarda;
        // no-cache: pregunta cada vez, así el permiso se revisa siempre (#121).
        $etag = '"' . pathinfo($ruta, PATHINFO_FILENAME) . '"';
        header('Cache-Control: private, no-cache');
        header('ETag: ' . $etag);
        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);
            exit;
        }
        header('Content-Type: ' . $tipo);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . str_replace(['"', '\\', "\r", "\n"], '_', $nombre) . '"');
        // Con Apache y mod_xsendfile, Apache envía el archivo y PHP queda libre. Si no, lo envía PHP (php -S, XAMPP).
        if (function_exists('apache_get_modules') && in_array('mod_xsendfile', apache_get_modules(), true)) {
            header('X-Sendfile: ' . $ruta);
            exit;
        }
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }

    // $referencia: el código del error en storage/logs (solo 500).
    public static function error(int $codigo, ?string $referencia = null): never
    {
        http_response_code($codigo);
        echo Vista::capturar('error', ['codigo' => $codigo, 'referencia' => $referencia]);
        exit;
    }
}
