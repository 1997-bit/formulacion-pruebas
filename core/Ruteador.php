<?php

declare(strict_types=1);

namespace App\Core;

// Ignora la cadena de consulta.
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
