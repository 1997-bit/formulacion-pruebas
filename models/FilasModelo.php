<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

// Filas de los formularios 2 a 5: (requerimiento_id, orden), sus columnas y guardado_por/guardado_en. $tabla y las columnas vienen del servicio.
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
     * Quién guardó la matriz y cuándo. Null si no tiene filas.
     *
     * @return array{autor: string, guardado_en: string}|null
     */
    public static function guardado(string $tabla, int $requerimientoId): ?array
    {
        $sql = Conexion::pdo()->prepare(
            "SELECT u.nombre AS autor, f.guardado_en FROM {$tabla} f JOIN usuarios u ON u.id = f.guardado_por WHERE f.requerimiento_id = ? LIMIT 1"
        );
        $sql->execute([$requerimientoId]);

        return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** @return array<int, int> requerimiento_id => filas */
    public static function contar(string $tabla): array
    {
        $sql = Conexion::pdo()->query("SELECT requerimiento_id, COUNT(*) FROM {$tabla} GROUP BY requerimiento_id");

        return array_map(intval(...), $sql->fetchAll(\PDO::FETCH_KEY_PAIR));
    }

    /**
     * Borra y vuelve a insertar con orden 1, 2, 3… Dentro de una transacción.
     *
     * @param list<string> $columnas
     * @param list<array<string, string>> $filas
     */
    public static function reemplazar(string $tabla, int $requerimientoId, array $columnas, array $filas, int $usuarioId): void
    {
        $pdo = Conexion::pdo();
        $pdo->prepare("DELETE FROM {$tabla} WHERE requerimiento_id = ?")->execute([$requerimientoId]);
        $sql = $pdo->prepare(
            "INSERT INTO {$tabla} (requerimiento_id, orden, guardado_por, " . implode(', ', $columnas) . ')
             VALUES (?, ?, ?' . str_repeat(', ?', count($columnas)) . ')'
        );
        foreach ($filas as $i => $fila) {
            $valores = [$requerimientoId, $i + 1, $usuarioId];
            foreach ($columnas as $columna) {
                $valores[] = $fila[$columna];
            }
            $sql->execute($valores);
        }
    }
}
