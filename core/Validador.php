<?php

declare(strict_types=1);

namespace App\Core;

// Junta los errores por campo y lanza ErrorValidacion si hay alguno.
final class Validador
{
    public function requerido(string $campo, mixed $valor): self
    {
        throw new \LogicException('Pendiente');
    }

    public function fecha(string $campo, ?string $valor): self
    {
        throw new \LogicException('Pendiente');
    }

    public function catalogo(string $campo, string $catalogo, int|string|null $valor): self
    {
        throw new \LogicException('Pendiente');
    }

    public function regla(string $campo, bool $cumple, string $mensaje): self
    {
        throw new \LogicException('Pendiente');
    }

    public function comprobar(): void
    {
        throw new \LogicException('Pendiente');
    }
}
