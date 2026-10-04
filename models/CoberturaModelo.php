<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

// cobertura_blanca: una fila por métrica, sin orden. Quién guardó y el conteo van por FilasModelo.
final class CoberturaModelo
{
    /** @return array<int, array<string, mixed>> metrica => fila */
    public static function deRequerimiento(int $requerimientoId): array
    {
        $sql = Conexion::pdo()->prepare('SELECT * FROM cobertura_blanca WHERE requerimiento_id = ? ORDER BY metrica');
        $sql->execute([$requerimientoId]);

        return array_column($sql->fetchAll(\PDO::FETCH_ASSOC), null, 'metrica');
    }

    /**
     * Borra y vuelve a insertar. Dentro de una transacción.
     *
     * @param array<int, array{total: int, cubiertos: int, porcentaje: int, herramienta: string}> $filas  metrica => fila
     */
    public static function reemplazar(int $requerimientoId, array $filas, int $usuarioId): void
    {
        $pdo = Conexion::pdo();
        $pdo->prepare('DELETE FROM cobertura_blanca WHERE requerimiento_id = ?')->execute([$requerimientoId]);
        $sql = $pdo->prepare(
            'INSERT INTO cobertura_blanca (requerimiento_id, metrica, total, cubiertos, porcentaje, herramienta, guardado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($filas as $metrica => $f) {
            $sql->execute([$requerimientoId, $metrica, $f['total'], $f['cubiertos'], $f['porcentaje'], $f['herramienta'], $usuarioId]);
        }
    }
}
