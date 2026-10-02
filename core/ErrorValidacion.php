<?php

declare(strict_types=1);

namespace App\Core;

// Datos inválidos. Lleva los mensajes por campo.
final class ErrorValidacion extends \RuntimeException
{
    /** @param array<string, string> $errores */
    public function __construct(public readonly array $errores)
    {
        throw new \LogicException('Pendiente');
    }
}
