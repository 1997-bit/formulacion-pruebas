<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Conexion;
use App\Core\ErrorNoEncontrado;
use App\Core\ErrorValidacion;
use App\Core\Paginacion;
use App\Core\Validador;
use App\Models\FilasModelo;

// RF-09, formulario 2. Filas por requerimiento
final class ClasesEquivalenciaServicio
{
    // Lo que resume la huella, #100
    public const TABLAS = ['clases_equivalencia'];

    private const TABLA = 'clases_equivalencia';

    // Columna => [etiqueta, largo máximo]. null = TEXT
    public const COLUMNAS = [
        'campo' => ['Campo', 100],
        'clase_valida' => ['Clase válida', null],
        'clases_invalidas' => ['Clases inválidas', null],
        'valores_representativos' => ['Valores representativos', 255],
        'resultado_esperado' => ['Resultado esperado', null],
    ];

    /**
     * @param array{id: int, rol: int} $usuario
     * @return list<array<string, mixed>>
     */
    public static function filas(int $requerimientoId, array $usuario): array
    {
        RequerimientoServicio::ver($requerimientoId, $usuario) ?? throw new ErrorNoEncontrado();

        return FilasModelo::deRequerimiento(self::TABLA, $requerimientoId);
    }

    /**
     * @param array{id: int, rol: int} $usuario
     * @return array{autor: string, guardado_en: string}|null
     */
    public static function guardado(int $requerimientoId, array $usuario): ?array
    {
        RequerimientoServicio::ver($requerimientoId, $usuario) ?? throw new ErrorNoEncontrado();

        return FilasModelo::guardado(self::TABLA, $requerimientoId);
    }

    /**
     * Requerimientos del usuario paginados, con filas de su matriz, RNF-09
     *
     * @param array{id: int, rol: int} $usuario
     * @return array{0: list<array<string, mixed>>, 1: Paginacion}
     */
    public static function pagina(array $usuario, int $pagina): array
    {
        [$requerimientos, $paginacion] = RequerimientoServicio::pagina($usuario, $pagina);
        $filas = FilasModelo::contar(self::TABLA, array_column($requerimientos, 'id'));
        foreach ($requerimientos as &$r) {
            $r['filas'] = $filas[$r['id']] ?? 0;
        }
        unset($r);

        return [$requerimientos, $paginacion];
    }

    /**
     * Todo o nada: valida cada fila y reemplaza en una transacción
     * Keys de error: "fila.columna"
     *
     * @param list<array<string, string>> $filas
     * @param array{id: int, rol: int} $usuario
     */
    public static function guardar(int $requerimientoId, array $filas, string $huella, array $usuario): void
    {
        RequerimientoServicio::ver($requerimientoId, $usuario) ?? throw new ErrorNoEncontrado();

        $filas = array_map(fn (array $fila): array => array_map(trim(...), $fila), $filas);
        $v = (new Validador())
            ->regla('general', $filas !== [], 'Agregue al menos una fila.')
            ->regla('general', count($filas) <= 255, 'Máximo 255 filas.');
        foreach ($filas as $i => $fila) {
            foreach (self::COLUMNAS as $columna => [, $maximo]) {
                $v->requerido("{$i}.{$columna}", $fila[$columna])
                    ->regla("{$i}.{$columna}", $maximo === null || mb_strlen($fila[$columna]) <= $maximo, "Máximo {$maximo} caracteres.")
                    ->texto("{$i}.{$columna}", $fila[$columna]);
            }
        }
        $v->comprobar();

        Conexion::transaccion(function () use ($requerimientoId, $huella, $filas, $usuario): void {
            if (FilasModelo::huella(self::TABLAS, 'requerimiento_id', $requerimientoId) !== $huella) {
                throw new ErrorValidacion(ErrorValidacion::OTRO_GUARDO);
            }
            FilasModelo::guardar(self::TABLA, $requerimientoId, array_keys(self::COLUMNAS), $filas, $usuario['id']);
        });
    }
}
