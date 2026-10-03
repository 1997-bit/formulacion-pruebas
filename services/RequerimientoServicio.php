<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Paginacion;
use App\Core\Validador;
use App\Models\RequerimientoModelo;

final class RequerimientoServicio
{
    /**
     * RNF-xx es no funcional.
     *
     * @param array<string, string> $datos
     * @param array{id: int, rol: int} $usuario
     */
    public static function registrar(array $datos, array $usuario): string
    {
        $proyectoId = trim($datos['proyecto_id']);
        $codigo = strtoupper(trim($datos['codigo']));
        $descripcion = trim($datos['descripcion']);
        $proyectos = array_column(self::proyectos($usuario), null, 'id');
        $formato = (bool) preg_match('/^RN?F-\d{2,3}$/', $codigo);

        (new Validador())
            ->requerido('proyecto_id', $proyectoId)
            ->regla('proyecto_id', $proyectoId === '' || isset($proyectos[$proyectoId]), 'Valor no válido.')
            ->requerido('codigo', $codigo)
            ->regla('codigo', $codigo === '' || $formato, 'Use RF-01 o RNF-01.')
            ->regla('codigo', !$formato || !isset($proyectos[$proyectoId]) || !RequerimientoModelo::existe((int) $proyectoId, $codigo), 'Ese código ya existe en el proyecto.')
            ->requerido('descripcion', $descripcion)
            ->comprobar();

        RequerimientoModelo::crear((int) $proyectoId, $codigo, $descripcion, str_starts_with($codigo, 'RNF') ? 1 : 0);

        return $codigo;
    }

    /**
     * @param array{id: int, rol: int} $usuario
     * @return list<array<string, mixed>>
     */
    public static function listar(array $usuario): array
    {
        return RequerimientoModelo::listar($usuario['id'], $usuario['rol'] === 1);
    }

    /**
     * RNF-09
     *
     * @param array{id: int, rol: int} $usuario
     * @return array{0: list<array<string, mixed>>, 1: Paginacion}
     */
    public static function pagina(array $usuario, int $pagina): array
    {
        $admin = $usuario['rol'] === 1;
        $paginacion = new Paginacion(RequerimientoModelo::contar($usuario['id'], $admin), $pagina);

        return [RequerimientoModelo::listar($usuario['id'], $admin, $paginacion->offset()), $paginacion];
    }

    /**
     * @param array{id: int, rol: int} $usuario
     * @return list<array<string, mixed>>
     */
    public static function proyectos(array $usuario): array
    {
        return RequerimientoModelo::proyectosPermitidos($usuario['id'], $usuario['rol'] === 1);
    }
}
