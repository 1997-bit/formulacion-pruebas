<?php

declare(strict_types=1);

namespace App\Config;

use App\Core\Env;

// Una conexión PDO por petición
final class Conexion
{
    private static ?\PDO $pdo = null;

    public static function pdo(): \PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                Env::get('DB_HOST', '127.0.0.1'),
                Env::get('DB_PORT', '3306'),
                Env::get('DB_NAME'),
            );
            self::$pdo = new \PDO($dsn, Env::get('DB_USER'), Env::get('DB_PASS'), [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_EMULATE_PREPARES => false,
                // Zona horaria de PHP al conectar. Un viaje menos, #124
                // Pdo\Mysql desde PHP 8.4. En 8.5 la constante vieja da warning. RNF-08
                (PHP_VERSION_ID >= 80400 ? \Pdo\Mysql::ATTR_INIT_COMMAND : \PDO::MYSQL_ATTR_INIT_COMMAND) => "SET time_zone = '" . date('P') . "'",
            ]);
        }

        return self::$pdo;
    }

    /**
     * Transacción: commit si termina, rollBack si lanza
     *
     * @template T
     * @param callable(): T $hacer
     * @return T
     */
    public static function transaccion(callable $hacer): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $resultado = $hacer();
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $resultado;
    }
}
