<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

// decision_celdas. Las filas van por FilasModelo.
final class TablaDecisionModelo
{
    /** @return list<array{fila_orden: int, regla: int, valor: int}> */
    public static function celdas(int $requerimientoId): array
    {
        $sql = Conexion::pdo()->prepare('SELECT fila_orden, regla, valor FROM decision_celdas WHERE requerimiento_id = ?');
        $sql->execute([$requerimientoId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Dentro de una transacción, después de FilasModelo::reemplazar().
     *
     * @param list<array{0: int, 1: int, 2: int}> $celdas  [fila_orden, regla, valor]
     */
    public static function insertarCeldas(int $requerimientoId, array $celdas): void
    {
        $sql = Conexion::pdo()->prepare('INSERT INTO decision_celdas (requerimiento_id, fila_orden, regla, valor) VALUES (?, ?, ?, ?)');
        foreach ($celdas as $celda) {
            $sql->execute([$requerimientoId, ...$celda]);
        }
    }
}
