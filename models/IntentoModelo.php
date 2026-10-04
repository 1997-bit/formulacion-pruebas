<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

// Contador de intentos por cuenta o por IP (RNF-02).
final class IntentoModelo
{
    // Los primeros fallos no bloquean. Después: 1 s, 2 s, 4 s… hasta 2^12 s.
    private const LIBRES = 3;

    // Con milisegundos: en DATETIME, un bloqueo de 1 s podía durar 1 ms.
    public static function bloqueado(string $clave): bool
    {
        $sql = Conexion::pdo()->prepare('SELECT 1 FROM intentos_acceso WHERE clave = ? AND bloqueado_hasta > NOW(3)');
        $sql->execute([$clave]);

        return (bool) $sql->fetchColumn();
    }

    // En MySQL y MariaDB, el UPDATE asigna de izquierda a derecha: fallos ya es el valor nuevo.
    public static function fallar(string $clave): void
    {
        Conexion::pdo()->prepare(
            'INSERT INTO intentos_acceso (clave, fallos) VALUES (?, 1) ON DUPLICATE KEY UPDATE
                fallos = fallos + 1,
                bloqueado_hasta = IF(fallos >= ' . self::LIBRES . ', NOW(3) + INTERVAL POW(2, LEAST(fallos - ' . self::LIBRES . ', 12)) SECOND, NULL)'
        )->execute([$clave]);
    }

    public static function borrar(string $clave): void
    {
        Conexion::pdo()->prepare('DELETE FROM intentos_acceso WHERE clave = ?')->execute([$clave]);
    }
}
