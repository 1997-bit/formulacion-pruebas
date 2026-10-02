<?php

declare(strict_types=1);

namespace App\Services;

// Permisos sobre datos según la matriz 7.1 del IR. Lanzan ErrorPermiso.
final class Permisos
{
    /** @param array<string, mixed> $usuario */
    public static function exigirAdmin(array $usuario): void
    {
        throw new \LogicException('Pendiente');
    }

    /** @param array<string, mixed> $usuario */
    public static function exigirMiembro(array $usuario, int $proyectoId): void
    {
        throw new \LogicException('Pendiente');
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $caso
     */
    public static function exigirEditarCaso(array $usuario, array $caso): void
    {
        throw new \LogicException('Pendiente');
    }
}
