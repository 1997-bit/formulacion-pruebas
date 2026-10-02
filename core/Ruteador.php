<?php

declare(strict_types=1);

namespace App\Core;

// Busca la ruta de una petición en config/rutas.php. La cadena de consulta no cuenta.
final class Ruteador
{
    /** @return array{controlador: string, accion: string, rol: ?int, rf: string}|null */
    public static function buscar(string $metodo, string $uri): ?array
    {
        $rutas = require RAIZ . '/config/rutas.php';
        $camino = parse_url($uri, PHP_URL_PATH) ?: '/';

        return $rutas[$metodo . ' ' . $camino] ?? null;
    }
}
