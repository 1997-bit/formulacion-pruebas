<?php

declare(strict_types=1);

namespace App\Core;

// Mensajes por campo.
final class ErrorValidacion extends \RuntimeException
{
    /** @param array<string, string> $errores */
    public function __construct(public readonly array $errores)
    {
        parent::__construct('Datos inválidos.');
    }
}
