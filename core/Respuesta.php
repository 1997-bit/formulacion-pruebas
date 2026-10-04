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
        $_SESSION['errores'] = $errores;
        $_SESSION['datos'] = $datos;
        self::redirigir($ruta);
    }

    // nosniff: el navegador no adivina otro tipo (RNF-03).
    public static function archivo(string $ruta, string $tipo, string $nombre): never
    {
        header('Content-Type: ' . $tipo);
        header('Content-Length: ' . filesize($ruta));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . str_replace(['"', '\\', "\r", "\n"], '_', $nombre) . '"');
        readfile($ruta);
        exit;
    }

    public static function error(int $codigo): never
    {
        http_response_code($codigo);
        echo Vista::capturar('error', ['codigo' => $codigo]);
        exit;
    }
}
