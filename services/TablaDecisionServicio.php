<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Conexion;
use App\Core\ErrorNoEncontrado;
use App\Core\Paginacion;
use App\Core\Validador;
use App\Models\FilasModelo;
use App\Models\TablaDecisionModelo;

/**
 * RF-11. Formulario 4: condiciones y acciones de un requerimiento, con una columna por regla.
 * Las reglas salen de las condiciones: n condiciones, 2^n reglas.
 * Celdas: condición V, F o -; acción X o -.
 *
 * @phpstan-type Fila array{texto: string, reglas: list<string>}
 * @phpstan-type Tabla array{condiciones: list<Fila>, acciones: list<Fila>}
 */
final class TablaDecisionServicio
{
    private const TABLA = 'decision_filas';

    // 4 condiciones son 16 reglas: más ya no cabe en pantalla.
    public const MAX_CONDICIONES = 4;
    public const MAX_ACCIONES = 20;

    /**
     * Guardada, o una vacía de 2 condiciones y 2 acciones si no tiene.
     *
     * @param array{id: int, rol: int} $usuario
     * @return Tabla
     */
    public static function tabla(int $requerimientoId, array $usuario): array
    {
        RequerimientoServicio::ver($requerimientoId, $usuario) ?? throw new ErrorNoEncontrado();

        $filas = FilasModelo::deRequerimiento(self::TABLA, $requerimientoId);
        $condiciones = array_values(array_filter($filas, fn (array $f): bool => !$f['es_accion']));
        $reglas = 2 ** count($condiciones);
        $marcas = [];
        foreach (TablaDecisionModelo::celdas($requerimientoId) as $c) {
            $marcas[$c['fila_orden']][$c['regla']] = $c['valor'];
        }

        $tabla = ['condiciones' => [], 'acciones' => []];
        foreach ($filas as $f) {
            $celdas = [];
            for ($r = 1; $r <= $reglas; $r++) {
                $valor = $marcas[$f['orden']][$r] ?? null;
                $celdas[] = match (true) {
                    $valor === null => '-',
                    (bool) $f['es_accion'] => 'X',
                    default => $valor ? 'V' : 'F',
                };
            }
            $tabla[$f['es_accion'] ? 'acciones' : 'condiciones'][] = ['texto' => $f['texto'], 'reglas' => $celdas];
        }

        return $filas ? $tabla : self::rearmar(['condiciones' => [], 'acciones' => []], 2, 2);
    }

    /**
     * Agrega o quita una fila sin guardar. Con otro número de condiciones, las reglas
     * vuelven a la combinación completa y las acciones se desmarcan.
     *
     * @param Tabla $tabla
     * @param string $cambio  condicion, accion, quitar-condicion-N o quitar-accion-N
     * @return Tabla
     */
    public static function cambiar(array $tabla, string $cambio): array
    {
        $condiciones = count($tabla['condiciones']);
        $acciones = count($tabla['acciones']);
        if (preg_match('/^quitar-(condicion|accion)-(\d+)$/', $cambio, $m)) {
            $grupo = $m[1] === 'condicion' ? 'condiciones' : 'acciones';
            if (count($tabla[$grupo]) > 1) {
                array_splice($tabla[$grupo], (int) $m[2], 1);
            }
        } elseif ($cambio === 'condicion' && $condiciones < self::MAX_CONDICIONES) {
            $tabla['condiciones'][] = ['texto' => '', 'reglas' => []];
        } elseif ($cambio === 'accion' && $acciones < self::MAX_ACCIONES) {
            $tabla['acciones'][] = ['texto' => '', 'reglas' => array_fill(0, 2 ** $condiciones, '-')];
        }

        return count($tabla['condiciones']) === $condiciones ? $tabla : self::rearmar($tabla, count($tabla['condiciones']), count($tabla['acciones']));
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
     * Requerimientos del usuario, paginados, con cuántas filas tiene su tabla (RNF-09).
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
     * Todas o ninguna: valida cada celda antes de escribir; filas y celdas en una transacción.
     * Errores con clave "condicion.i", "condicion.i.r", "accion.i", "accion.i.r" y "regla.r".
     *
     * @param Tabla $tabla
     * @param array{id: int, rol: int} $usuario
     */
    public static function guardar(int $requerimientoId, array $tabla, array $usuario): void
    {
        RequerimientoServicio::ver($requerimientoId, $usuario) ?? throw new ErrorNoEncontrado();

        ['condiciones' => $condiciones, 'acciones' => $acciones] = $tabla;
        $reglas = 2 ** count($condiciones);
        $v = (new Validador())
            ->regla('general', $condiciones !== [] && count($condiciones) <= self::MAX_CONDICIONES, 'De 1 a ' . self::MAX_CONDICIONES . ' condiciones.')
            ->regla('general', $acciones !== [] && count($acciones) <= self::MAX_ACCIONES, 'De 1 a ' . self::MAX_ACCIONES . ' acciones.');
        foreach (['condicion' => $condiciones, 'accion' => $acciones] as $grupo => $filas) {
            $permitidos = $grupo === 'condicion' ? ['V', 'F', '-'] : ['X', '-'];
            foreach ($filas as $i => $fila) {
                $v->requerido("{$grupo}.{$i}", $fila['texto'])
                    ->regla("{$grupo}.{$i}", mb_strlen(trim($fila['texto'])) <= 255, 'Máximo 255 caracteres.')
                    ->regla('general', count($fila['reglas']) === $reglas, 'Las reglas no coinciden con las condiciones. Vuelva a abrir la tabla.');
                foreach ($fila['reglas'] as $r => $valor) {
                    $v->regla("{$grupo}.{$i}.{$r}", in_array($valor, $permitidos, true), 'Valor no válido.');
                }
            }
        }
        for ($r = 0; $r < $reglas; $r++) {
            $v->regla("regla.{$r}", in_array('X', array_column(array_map(fn (array $f): array => $f['reglas'], $acciones), $r), true), 'Marque una acción.');
        }
        $v->comprobar();

        $filas = [];
        $celdas = [];
        foreach ([...$condiciones, ...$acciones] as $i => $fila) {
            $esAccion = $i >= count($condiciones);
            $filas[] = ['es_accion' => $esAccion ? '1' : '0', 'texto' => trim($fila['texto'])];
            foreach ($fila['reglas'] as $r => $valor) {
                if ($valor !== '-') {
                    $celdas[] = [$i + 1, $r + 1, $valor === 'F' ? 0 : 1];
                }
            }
        }

        $pdo = Conexion::pdo();
        $pdo->beginTransaction();
        try {
            FilasModelo::reemplazar(self::TABLA, $requerimientoId, ['es_accion', 'texto'], $filas, $usuario['id']);
            TablaDecisionModelo::insertarCeldas($requerimientoId, $celdas);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Combinación completa: la primera condición cambia cada 2^(n-1) reglas, la última en cada una (VVFF, VFVF).
     *
     * @param Tabla $tabla
     * @return Tabla
     */
    private static function rearmar(array $tabla, int $condiciones, int $acciones): array
    {
        $reglas = 2 ** $condiciones;
        for ($i = 0; $i < $condiciones; $i++) {
            $tabla['condiciones'][$i] = [
                'texto' => $tabla['condiciones'][$i]['texto'] ?? '',
                'reglas' => array_map(fn (int $r): string => ($r >> ($condiciones - 1 - $i)) & 1 ? 'F' : 'V', range(0, $reglas - 1)),
            ];
        }
        for ($i = 0; $i < $acciones; $i++) {
            $tabla['acciones'][$i] = ['texto' => $tabla['acciones'][$i]['texto'] ?? '', 'reglas' => array_fill(0, $reglas, '-')];
        }

        return $tabla;
    }
}
