<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

// Filas de los formularios 2 a 5: (requerimiento_id, orden) y sus columnas. $tabla y las columnas vienen del servicio.
final class FilasModelo
{
    /** @return list<array<string, mixed>> */
    public static function deRequerimiento(string $tabla, int $requerimientoId): array
    {
        $sql = Conexion::pdo()->prepare("SELECT * FROM {$tabla} WHERE requerimiento_id = ? ORDER BY orden");
        $sql->execute([$requerimientoId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Borra y vuelve a insertar con orden 1, 2, 3… Dentro de una transacción.
     *
     * @param list<string> $columnas
     * @param list<array<string, string>> $filas
     */
    public static function reemplazar(string $tabla, int $requerimientoId, array $columnas, array $filas): void
    {
        $pdo = Conexion::pdo();
        $pdo->prepare("DELETE FROM {$tabla} WHERE requerimiento_id = ?")->execute([$requerimientoId]);
        $sql = $pdo->prepare(
            "INSERT INTO {$tabla} (requerimiento_id, orden, " . implode(', ', $columnas) . ')
             VALUES (?, ?' . str_repeat(', ?', count($columnas)) . ')'
        );
        foreach ($filas as $i => $fila) {
            $valores = [$requerimientoId, $i + 1];
            foreach ($columnas as $columna) {
                $valores[] = $fila[$columna];
            }
            $sql->execute($valores);
        }
    }
}
