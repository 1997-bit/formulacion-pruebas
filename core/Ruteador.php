<?php

declare(strict_types=1);

namespace App\Core;

// Busca la ruta de la petición en config/rutas.php.
final class Ruteador
{
    /** @return array{0: class-string, 1: string, 2: int|null, 3: string}|null controlador, acción, rol y RF */
    public static function resolver(string $metodo, string $ruta): ?array
    {
        throw new \LogicException('Pendiente');
    }
}
