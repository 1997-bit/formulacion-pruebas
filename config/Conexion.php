<?php

declare(strict_types=1);

namespace App\Config;

// Conexión PDO única, con los datos del .env.
final class Conexion
{
    public static function pdo(): \PDO
    {
        throw new \LogicException('Pendiente');
    }
}
