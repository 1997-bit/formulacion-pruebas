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
     * Por diferencia. Dentro de una transacción.
     *
     * @param array<int, array{total: int, cubiertos: int, porcentaje: int, herramienta: string}> $filas  metrica => fila
     */
    public static function guardar(int $requerimientoId, array $filas, int $usuarioId): void
    {
        $datos = [];
        foreach ($filas as $metrica => $f) {
            $datos[] = ['metrica' => $metrica] + $f;
        }
        FilasModelo::sincronizar(
            'cobertura_blanca',
            ['requerimiento_id' => $requerimientoId],
            ['metrica'],
            $datos,
            ['guardado_por' => $usuarioId, 'guardado_en' => date('Y-m-d H:i:s')]
        );
    }
}
