<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Models\FilasModelo;
use App\Models\HistorialModelo;

// RF-20
final class Historial
{
    // Campo => etiqueta y catálogo. Solo estos campos dejan rastro.
    private const CAMPOS = [
        'requerimiento' => ['Requerimiento', null],
        'tipo_prueba' => ['Tipo de prueba', 'tipo_prueba'],
        'subtecnica' => ['Sub-técnica', 'subtecnica'],
        'modulo' => ['Módulo', null],
        'plataforma' => ['Plataforma', 'plataforma'],
        'entorno' => ['Entorno', null],
        'objetivo' => ['Objetivo', null],
        'precondiciones' => ['Precondiciones', null],
        'entrada' => ['Datos de entrada', null],
        'pasos' => ['Pasos', null],
        'resultado_esperado' => ['Resultado esperado', null],
        'fecha_inicio' => ['Fecha de inicio', null],
        'fecha_fin' => ['Fecha final', null],
        'estado' => ['Estado', 'estado_caso'],
        'resultado_obtenido' => ['Resultado obtenido', null],
        'observaciones' => ['Observaciones', null],
    ];

    /**
     * Una fila por campo cambiado, en un INSERT. Va en la transacción del UPDATE: si falla, el caso no cambia.
     *
     * @param array<string, mixed> $antes
     * @param array<string, mixed> $despues
     */
    public static function registrar(int $casoId, array $antes, array $despues, int $usuarioId): void
    {
        $filas = [];
        foreach (array_intersect_key($despues, self::CAMPOS) as $campo => $valor) {
            $a = $antes[$campo] === null ? null : (string) $antes[$campo];
            $b = $valor === null ? null : (string) $valor;
            if ($a !== $b) {
                $filas[] = [$casoId, $usuarioId, $campo, $a, $b];
            }
        }
        FilasModelo::insertar('logs_cambios', ['caso_id', 'usuario_id', 'campo', 'antes', 'despues'], $filas);
    }

    /**
     * Con etiqueta y valores legibles.
     *
     * @return list<array<string, mixed>>
     */
    public static function deCaso(int $casoId): array
    {
        return array_map(static function (array $fila): array {
            [$etiqueta, $catalogo] = self::CAMPOS[$fila['campo']] ?? [$fila['campo'], null];
            $texto = static fn (?string $valor): string => match (true) {
                $valor === null => '',
                $catalogo !== null => Catalogo::texto($catalogo, (int) $valor),
                str_starts_with($fila['campo'], 'fecha_') => Fecha::legible(new \DateTime($valor)),
                default => $valor,
            };

            return ['etiqueta' => $etiqueta, 'antes' => $texto($fila['antes']), 'despues' => $texto($fila['despues'])] + $fila;
        }, HistorialModelo::deCaso($casoId));
    }
}
