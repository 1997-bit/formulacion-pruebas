<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Conexion;
use App\Core\ErrorPermiso;
use App\Core\Validador;
use App\Models\FilasModelo;

// RF-09. Formulario 2: las filas pertenecen a un requerimiento.
final class ClasesEquivalenciaServicio
{
    private const TABLA = 'clases_equivalencia';

    // Columna => largo máximo; null es TEXT.
    public const COLUMNAS = [
        'campo' => 100,
        'clase_valida' => null,
        'clases_invalidas' => null,
        'valores_representativos' => 255,
        'resultado_esperado' => null,
    ];

    /**
     * @param array{id: int, rol: int} $usuario
     * @return list<array<string, mixed>>
     */
    public static function filas(int $requerimientoId, array $usuario): array
    {
        RequerimientoServicio::ver($requerimientoId, $usuario) ?? throw new ErrorPermiso();

        return FilasModelo::deRequerimiento(self::TABLA, $requerimientoId);
    }

    /**
     * Todas o ninguna: valida cada fila antes de escribir y reemplaza en una transacción.
     * Errores por celda con clave "fila.columna".
     *
     * @param list<array<string, string>> $filas
     * @param array{id: int, rol: int} $usuario
     */
    public static function guardar(int $requerimientoId, array $filas, array $usuario): void
    {
        RequerimientoServicio::ver($requerimientoId, $usuario) ?? throw new ErrorPermiso();

        $filas = array_map(fn (array $fila): array => array_map(trim(...), $fila), $filas);
        $v = (new Validador())
            ->regla('general', $filas !== [], 'Agregue al menos una fila.')
            ->regla('general', count($filas) <= 255, 'Máximo 255 filas.');
        foreach ($filas as $i => $fila) {
            foreach (self::COLUMNAS as $columna => $maximo) {
                $v->requerido("{$i}.{$columna}", $fila[$columna])
                    ->regla("{$i}.{$columna}", $maximo === null || mb_strlen($fila[$columna]) <= $maximo, "Máximo {$maximo} caracteres.");
            }
        }
        $v->comprobar();

        $pdo = Conexion::pdo();
        $pdo->beginTransaction();
        try {
            FilasModelo::reemplazar(self::TABLA, $requerimientoId, array_keys(self::COLUMNAS), $filas);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
