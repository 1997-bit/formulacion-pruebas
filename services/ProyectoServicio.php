<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorValidacion;
use App\Core\Validador;
use App\Models\ProyectoModelo;
use App\Models\UsuarioModelo;

// Proyectos y miembros (7.1). Miembros: solo testers; el admin ya ve todo.
final class ProyectoServicio
{
    /**
     * @param array{id: int, rol: int} $actor
     * @return list<array<string, mixed>>
     */
    public static function listar(array $actor): array
    {
        Permisos::exigirAdmin($actor);

        return ProyectoModelo::listar();
    }

    /**
     * Con 'miembros': ids de usuario.
     *
     * @param array{id: int, rol: int} $actor
     * @return array<string, mixed>|null
     */
    public static function ver(int $id, array $actor): ?array
    {
        Permisos::exigirAdmin($actor);
        $proyecto = ProyectoModelo::porId($id);

        return $proyecto === null ? null : $proyecto + ['miembros' => ProyectoModelo::miembros($id)];
    }

    /**
     * @param array{id: int, rol: int} $actor
     * @return list<array<string, mixed>>
     */
    public static function testers(array $actor): array
    {
        Permisos::exigirAdmin($actor);

        return UsuarioModelo::testers();
    }

    /**
     * $id null: crea.
     *
     * @param array{nombre: string, descripcion: string, miembros: list<string>} $datos
     * @param array{id: int, rol: int} $actor
     */
    public static function guardar(?int $id, array $datos, array $actor): string
    {
        $nombre = trim($datos['nombre']);
        $descripcion = trim($datos['descripcion']);
        $testers = array_column(self::testers($actor), 'id');
        $miembros = array_map('intval', $datos['miembros']);

        (new Validador())
            ->requerido('nombre', $nombre)
            ->regla('nombre', mb_strlen($nombre) <= 100, 'Máximo 100 caracteres.')
            ->regla('miembros', array_diff($miembros, $testers) === [], 'Valor no válido.')
            ->comprobar();

        try {
            ProyectoModelo::guardar($id, $nombre, $descripcion === '' ? null : $descripcion, array_values(array_unique($miembros)));
        } catch (\PDOException $e) {
            throw ErrorValidacion::siDuplicado($e, ['nombre' => 'Ese proyecto ya existe.']);
        }

        return $nombre;
    }

    /** @param array{id: int, rol: int} $actor */
    public static function eliminar(int $id, array $actor): void
    {
        Permisos::exigirAdmin($actor);
        if (!ProyectoModelo::eliminar($id)) {
            throw new ErrorValidacion(['general' => 'Tiene requerimientos o casos: no se puede eliminar.']);
        }
    }
}
