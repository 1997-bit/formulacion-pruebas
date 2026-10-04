<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorNoEncontrado;
use App\Core\ErrorPermiso;
use App\Models\ProyectoModelo;

// Matriz 7.1 del IR.
final class Permisos
{
    /** @var array<int, array<int, true>> usuario => proyectos. Una consulta por petición (#109). */
    private static array $proyectos = [];

    /** @param array<string, mixed> $usuario */
    public static function exigirAdmin(array $usuario): void
    {
        if (($usuario['rol'] ?? null) !== 1) {
            throw new ErrorPermiso();
        }
    }

    // 404 y no 403: así no se sabe qué ids existen en otros proyectos (BUG-026).
    /** @param array<string, mixed> $usuario */
    public static function exigirMiembro(array $usuario, int $proyectoId): void
    {
        if ($usuario['rol'] !== 1 && !isset(self::de($usuario['id'])[$proyectoId])) {
            throw new ErrorNoEncontrado();
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
        return $usuario['rol'] === 1 ? null : array_keys(self::de($usuario['id']));
    }

    /** @return array<int, true> */
    private static function de(int $usuarioId): array
    {
        return self::$proyectos[$usuarioId] ??= array_fill_keys(ProyectoModelo::deUsuario($usuarioId), true);
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
