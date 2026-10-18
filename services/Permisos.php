<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorNoEncontrado;
use App\Core\ErrorPermiso;

// Matriz 7.1 del IR
final class Permisos
{
    /** @param array<string, mixed> $usuario */
    public static function exigirAdmin(array $usuario): void
    {
        if (($usuario['rol'] ?? null) !== 1) {
            throw new ErrorPermiso();
        }
    }

    // 404 y no 403: no revela ids de otros proyectos, BUG-026
    /** @param array<string, mixed> $usuario */
    public static function exigirMiembro(array $usuario, int $proyectoId): void
    {
        if ($usuario['rol'] !== 1 && !in_array($proyectoId, $usuario['proyectos'], true)) {
            throw new ErrorNoEncontrado();
        }
    }

    /**
     * Proyectos visibles. null: admin, todos
     * Del usuario leído en cada petición, #109. Con proyecto activo: solo ese
     *
     * @param array<string, mixed> $usuario
     * @return list<int>|null
     */
    public static function proyectos(array $usuario): ?array
    {
        if (($usuario['activo'] ?? null) !== null) {
            return [$usuario['activo']];
        }

        return $usuario['rol'] === 1 ? null : $usuario['proyectos'];
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
