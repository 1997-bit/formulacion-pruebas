<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Conexion;
use App\Core\ErrorPermiso;
use App\Core\Validador;
use App\Helpers\Catalogo;
use App\Models\AutoevaluacionModelo;
use App\Models\ProyectoModelo;
use App\Models\RequerimientoModelo;

// RF-15. Formulario 8: cada miembro se evalúa y evalúa a un compañero, de 1 a 5 por aspecto.
final class AutoevaluacionServicio
{
    /**
     * Proyectos del usuario con cuántas personas ya guardaron.
     *
     * @param array{id: int, rol: int} $usuario
     * @return list<array<string, mixed>>
     */
    public static function proyectos(array $usuario): array
    {
        $proyectos = RequerimientoModelo::proyectosPermitidos($usuario['id'], $usuario['rol'] === 1);
        $guardadas = AutoevaluacionModelo::contar();
        foreach ($proyectos as &$p) {
            $p['guardadas'] = $guardadas[$p['id']] ?? 0;
        }
        unset($p);

        return $proyectos;
    }

    /**
     * Null si no existe.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array<string, mixed>|null
     */
    public static function proyecto(int $id, array $usuario): ?array
    {
        $proyecto = ProyectoModelo::porId($id);
        if ($proyecto !== null) {
            Permisos::exigirMiembro($usuario, $id);
        }

        return $proyecto;
    }

    // El admin ve los promedios pero no llena: no es miembro.
    public static function puedeLlenar(int $proyectoId, int $usuarioId): bool
    {
        return ProyectoModelo::esMiembro($proyectoId, $usuarioId);
    }

    /**
     * Lo que guardó el usuario, por aspecto, con sus promedios. Null si no ha guardado.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array{evaluado_id: int, evaluado: string, auto: array<int, int>, co: array<int, int>, comentario: array<int, string>, promedio_auto: float, promedio_co: float}|null
     */
    public static function evaluacion(int $proyectoId, array $usuario): ?array
    {
        Permisos::exigirMiembro($usuario, $proyectoId);
        $filas = AutoevaluacionModelo::deEvaluador($proyectoId, $usuario['id']);
        if ($filas === []) {
            return null;
        }

        $e = ['evaluado_id' => 0, 'evaluado' => '', 'auto' => [], 'co' => [], 'comentario' => []];
        foreach ($filas as $f) {
            if ($f['evaluado_id'] === $usuario['id']) {
                $e['auto'][$f['aspecto']] = $f['puntos'];
                $e['comentario'][$f['aspecto']] = (string) $f['comentario'];
            } else {
                $e['co'][$f['aspecto']] = $f['puntos'];
                $e['evaluado_id'] = $f['evaluado_id'];
                $e['evaluado'] = $f['evaluado'];
            }
        }

        return $e + ['promedio_auto' => self::promedio($e['auto']), 'promedio_co' => self::promedio($e['co'])];
    }

    /**
     * Cada miembro con su autoevaluación y la coevaluación que recibió, promediadas.
     *
     * @param array{id: int, rol: int} $usuario
     * @return list<array{id: int, nombre: string, auto: ?float, co: ?float}>
     */
    public static function promedios(int $proyectoId, array $usuario): array
    {
        Permisos::exigirMiembro($usuario, $proyectoId);

        return AutoevaluacionModelo::promedios($proyectoId);
    }

    /**
     * Todos los aspectos, auto y co, o nada. Errores con clave "auto.aspecto", "co.aspecto" y "comentario.aspecto".
     *
     * @param array{evaluado_id: string, auto: array<int|string, string>, co: array<int|string, string>, comentario: array<int|string, string>} $datos
     * @param array{id: int, rol: int} $usuario
     */
    public static function guardar(int $proyectoId, array $datos, array $usuario): void
    {
        if (!self::puedeLlenar($proyectoId, $usuario['id'])) {
            throw new ErrorPermiso();
        }

        $evaluado = (int) $datos['evaluado_id'];
        $companeros = array_diff(array_column(AutoevaluacionModelo::promedios($proyectoId), 'id'), [$usuario['id']]);
        $v = (new Validador())
            ->regla('evaluado_id', $companeros !== [], 'El proyecto no tiene otro miembro que evaluar.')
            ->requerido('evaluado_id', $datos['evaluado_id'])
            ->regla('evaluado_id', in_array($evaluado, $companeros, true), 'Elija un compañero del proyecto.');

        $filas = [];
        foreach (array_keys(Catalogo::valores('aspecto_evaluacion')) as $aspecto) {
            $comentario = trim($datos['comentario'][$aspecto] ?? '');
            $v->regla("comentario.{$aspecto}", mb_strlen($comentario) <= 500, 'Máximo 500 caracteres.');
            foreach (['auto' => $usuario['id'], 'co' => $evaluado] as $tipo => $evaluadoId) {
                $puntos = trim($datos[$tipo][$aspecto] ?? '');
                $v->requerido("{$tipo}.{$aspecto}", $puntos)
                    ->regla("{$tipo}.{$aspecto}", $puntos === '' || in_array($puntos, ['1', '2', '3', '4', '5'], true), 'Entre 1 y 5.');
                $filas[] = [
                    'evaluado_id' => $evaluadoId,
                    'aspecto' => $aspecto,
                    'puntos' => (int) $puntos,
                    'comentario' => $tipo === 'auto' && $comentario !== '' ? $comentario : null,
                ];
            }
        }
        $v->comprobar();

        $pdo = Conexion::pdo();
        $pdo->beginTransaction();
        try {
            AutoevaluacionModelo::reemplazar($proyectoId, $usuario['id'], $filas);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @param array<int, int> $puntos */
    private static function promedio(array $puntos): float
    {
        return $puntos === [] ? 0.0 : round(array_sum($puntos) / count($puntos), 1);
    }
}
