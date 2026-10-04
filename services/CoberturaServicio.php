<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Conexion;
use App\Core\ErrorNoEncontrado;
use App\Core\Paginacion;
use App\Core\Validador;
use App\Models\CoberturaModelo;
use App\Models\FilasModelo;

// RF-12. Formulario 5: una fila por métrica de caja blanca, de un requerimiento.
final class CoberturaServicio
{
    private const TABLA = 'cobertura_blanca';

    // Sub-técnicas del catálogo que se miden.
    public const METRICAS = [11, 12, 13, 16, 18];

    /**
     * El mismo redondeo que la pantalla (app.js), en enteros para no depender de flotantes.
     */
    public static function porcentaje(int $total, int $cubiertos): int
    {
        return intdiv(200 * $cubiertos + $total, 2 * $total);
    }

    /**
     * @param array{id: int, rol: int} $usuario
     * @return array<int, array<string, mixed>> metrica => fila
     */
    public static function filas(int $requerimientoId, array $usuario): array
    {
        RequerimientoServicio::ver($requerimientoId, $usuario) ?? throw new ErrorNoEncontrado();

        return CoberturaModelo::deRequerimiento($requerimientoId);
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
     * Requerimientos del usuario, paginados, con cuántas métricas tiene su cobertura (RNF-09).
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
     * Una métrica vacía no se mide. Todas o ninguna: valida antes de escribir y reemplaza en una transacción.
     * Errores por celda con clave "metrica.columna". El porcentaje se calcula aquí, no se recibe.
     *
     * @param array<int, array{total: string, cubiertos: string, herramienta: string}> $filas  metrica => fila
     * @param array{id: int, rol: int} $usuario
     */
    public static function guardar(int $requerimientoId, array $filas, array $usuario): void
    {
        RequerimientoServicio::ver($requerimientoId, $usuario) ?? throw new ErrorNoEncontrado();

        $v = new Validador();
        $guardar = [];
        $llenas = 0;
        foreach (self::METRICAS as $m) {
            $fila = array_map(trim(...), $filas[$m] ?? ['total' => '', 'cubiertos' => '', 'herramienta' => '']);
            if (implode('', $fila) === '') {
                continue;
            }
            $llenas++;
            $total = preg_match('/^\d{1,9}$/', $fila['total']) ? (int) $fila['total'] : null;
            $cubiertos = preg_match('/^\d{1,9}$/', $fila['cubiertos']) ? (int) $fila['cubiertos'] : null;
            $v->requerido("{$m}.total", $fila['total'])
                ->regla("{$m}.total", $total !== null, 'Número entero.')
                ->regla("{$m}.total", $total !== 0, 'Debe ser mayor que 0.')
                ->requerido("{$m}.cubiertos", $fila['cubiertos'])
                ->regla("{$m}.cubiertos", $cubiertos !== null, 'Número entero.')
                ->regla("{$m}.cubiertos", $total === null || $cubiertos === null || $cubiertos <= $total, 'No puede ser mayor que el total.')
                ->requerido("{$m}.herramienta", $fila['herramienta'])
                ->regla("{$m}.herramienta", mb_strlen($fila['herramienta']) <= 100, 'Máximo 100 caracteres.');
            if ($total && $cubiertos !== null && $cubiertos <= $total) {
                $guardar[$m] = [
                    'total' => $total,
                    'cubiertos' => $cubiertos,
                    'porcentaje' => self::porcentaje($total, $cubiertos),
                    'herramienta' => $fila['herramienta'],
                ];
            }
        }
        $v->regla('general', $llenas > 0, 'Llene al menos una métrica.')->comprobar();

        $pdo = Conexion::pdo();
        $pdo->beginTransaction();
        try {
            CoberturaModelo::reemplazar($requerimientoId, $guardar, $usuario['id']);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
