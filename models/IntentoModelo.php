<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

// Contador de intentos por cuenta o por IP (RNF-02).
final class IntentoModelo
{
    // Los primeros fallos no bloquean. Después: 1 s, 2 s, 4 s… hasta 2^12 s.
    private const LIBRES = 3;

    // Segundos que faltan, o 0 si no está bloqueado.
    public static function espera(string $clave): int
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT TIMESTAMPDIFF(SECOND, NOW(), bloqueado_hasta) FROM intentos_acceso WHERE clave = ? AND bloqueado_hasta > NOW()'
        );
        $sql->execute([$clave]);

        return (int) $sql->fetchColumn();
    }

    // En MySQL y MariaDB, el UPDATE asigna de izquierda a derecha: fallos ya es el valor nuevo.
    public static function fallar(string $clave): void
    {
        Conexion::pdo()->prepare(
            'INSERT INTO intentos_acceso (clave, fallos) VALUES (?, 1) ON DUPLICATE KEY UPDATE
                fallos = fallos + 1,
                bloqueado_hasta = IF(fallos >= ' . self::LIBRES . ', NOW() + INTERVAL POW(2, LEAST(fallos - ' . self::LIBRES . ', 12)) SECOND, NULL)'
        )->execute([$clave]);
    }

    public static function borrar(string $clave): void
    {
        Conexion::pdo()->prepare('DELETE FROM intentos_acceso WHERE clave = ?')->execute([$clave]);
    }
}
