<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorPermiso;
use App\Models\ProyectoModelo;

// Matriz 7.1 del IR.
final class Permisos
{
    /** @param array<string, mixed> $usuario */
    public static function exigirAdmin(array $usuario): void
    {
        if (($usuario['rol'] ?? null) !== 1) {
            throw new ErrorPermiso();
        }
    }

    /** @param array<string, mixed> $usuario */
    public static function exigirMiembro(array $usuario, int $proyectoId): void
    {
        if ($usuario['rol'] !== 1 && !ProyectoModelo::esMiembro($proyectoId, $usuario['id'])) {
            throw new ErrorPermiso();
        }
    }

    /**
     * Proyectos que el usuario puede ver en una lista. null: admin, todos.
     *
     * @param array<string, mixed> $usuario
     * @return list<int>|null
     */
    public static function proyectos(array $usuario): ?array
    {
        return $usuario['rol'] === 1 ? null : ProyectoModelo::deUsuario($usuario['id']);
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $caso
     */
    public static function exigirEditarCaso(array $usuario, array $caso): void
    {
        self::exigirMiembro($usuario, $caso['proyecto_id']);
        if (!self::puedeEditarCaso($usuario, $caso)) {
            throw new ErrorPermiso();
        }
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $caso
     */
    public static function puedeEditarCaso(array $usuario, array $caso): bool
    {
        return $usuario['rol'] === 1 || $caso['creado_por'] === $usuario['id'];
    }
}
