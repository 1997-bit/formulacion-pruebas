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

    public static function error(int $codigo): never
    {
        http_response_code($codigo);
        echo Vista::capturar('error', ['codigo' => $codigo]);
        exit;
    }
}
