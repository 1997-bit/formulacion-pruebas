<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Conexion;
use App\Core\Validador;
use App\Helpers\Catalogo;
use App\Models\ProyectoModelo;
use App\Models\RubricaModelo;

// RF-14. Formulario 7: una rúbrica por proyecto, solo admin. 6 criterios de 1 a 5, total sobre 30.
final class RubricaServicio
{
    public const MAXIMO = 30;

    /**
     * Todos los proyectos con su total. Null si no tiene rúbrica.
     *
     * @param array{id: int, rol: int} $usuario
     * @return list<array<string, mixed>>
     */
    public static function proyectos(array $usuario): array
    {
        Permisos::exigirAdmin($usuario);
        $totales = RubricaModelo::totales();

        return array_map(fn (array $p): array => $p + ['total' => $totales[$p['id']] ?? null], ProyectoModelo::listar());
    }

    /**
     * Null si no existe.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array<string, mixed>|null
     */
    public static function proyecto(int $id, array $usuario): ?array
    {
        Permisos::exigirAdmin($usuario);

        return ProyectoModelo::porId($id);
    }

    /**
     * Null si el proyecto no tiene rúbrica.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array{puntos: array<int, int>, total: int, autor: string, guardado_en: string}|null
     */
    public static function rubrica(int $proyectoId, array $usuario): ?array
    {
        Permisos::exigirAdmin($usuario);

        return RubricaModelo::deProyecto($proyectoId);
    }

    /**
     * Todos los criterios o nada. Errores con clave "puntos.criterio".
     *
     * @param array<int|string, string> $puntos criterio => puntos
     * @param array{id: int, rol: int} $usuario
     */
    public static function guardar(int $proyectoId, array $puntos, array $usuario): void
    {
        Permisos::exigirAdmin($usuario);

        $v = new Validador();
        $filas = [];
        foreach (array_keys(Catalogo::valores('criterio_rubrica')) as $criterio) {
            $p = trim($puntos[$criterio] ?? '');
            $v->requerido("puntos.{$criterio}", $p)
                ->regla("puntos.{$criterio}", $p === '' || in_array($p, ['1', '2', '3', '4', '5'], true), 'Entre 1 y 5.');
            $filas[$criterio] = (int) $p;
        }
        $v->comprobar();

        $pdo = Conexion::pdo();
        $pdo->beginTransaction();
        try {
            RubricaModelo::reemplazar($proyectoId, $usuario['id'], $filas);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
