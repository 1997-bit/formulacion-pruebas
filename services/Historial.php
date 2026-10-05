<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Models\FilasModelo;
use App\Models\HistorialModelo;
use App\Models\UsuarioModelo;

// RF-20
final class Historial
{
    // Campo del caso => etiqueta y catálogo. En el caso, solo estos campos dejan rastro.
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
        'solicitado_por' => ['Solicitado por', 'usuario'],
        'aprobado_por' => ['Aprobado por', 'usuario'],
        'resultado_obtenido' => ['Resultado obtenido', null],
        'observaciones' => ['Observaciones', null],
    ];

    /**
     * Una fila por campo cambiado, en un INSERT. Va en la transacción del UPDATE: si falla, el registro no cambia.
     * Casos: los campos de CAMPOS. Incidentes y plan: los que trae $despues.
     *
     * @param array<string, mixed> $antes
     * @param array<string, mixed> $despues
     */
    public static function registrar(string $tabla, int $id, array $antes, array $despues, int $usuarioId): void
    {
        $filas = [];
        foreach (array_intersect_key($despues, $tabla === 'casos_prueba' ? self::CAMPOS : $antes) as $campo => $valor) {
            $a = $antes[$campo] === null ? null : (string) $antes[$campo];
            $b = $valor === null ? null : (string) $valor;
            if ($a !== $b) {
                $filas[] = [$tabla, $id, $usuarioId, $campo, $a, $b];
            }
        }
        FilasModelo::insertar('logs_cambios', ['tabla', 'registro_id', 'usuario_id', 'campo', 'antes', 'despues'], $filas);
    }

    /**
     * Con etiqueta y valores legibles.
     *
     * @return list<array<string, mixed>>
     */
    public static function deCaso(int $casoId): array
    {
        // 'usuario' no es un catálogo: guarda el id y el historial muestra el nombre.
        $nombres = [];
        $nombreUsuario = static function (?string $valor) use (&$nombres): string {
            if ($valor === null) {
                return '';
            }
            $id = (int) $valor;

            return $nombres[$id] ??= (string) (UsuarioModelo::porId($id)['nombre'] ?? '');
        };

        return array_map(static function (array $fila) use ($nombreUsuario): array {
            [$etiqueta, $catalogo] = self::CAMPOS[$fila['campo']] ?? [$fila['campo'], null];
            $texto = static fn (?string $valor): string => match (true) {
                $valor === null => '',
                $catalogo === 'usuario' => $nombreUsuario($valor),
                $catalogo !== null => Catalogo::texto($catalogo, (int) $valor),
                str_starts_with($fila['campo'], 'fecha_') => Fecha::legible(new \DateTime($valor)),
                default => $valor,
            };

            return ['etiqueta' => $etiqueta, 'antes' => $texto($fila['antes']), 'despues' => $texto($fila['despues'])] + $fila;
        }, HistorialModelo::de('casos_prueba', $casoId));
    }
}
