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

    /**
     * Un UNIQUE repetido (1062) se muestra como error del campo, no como 500 (#101). Lo demás sigue igual.
     *
     * @param array<string, string> $errores
     */
    public static function siDuplicado(\PDOException $e, array $errores): \Throwable
    {
        return ($e->errorInfo[1] ?? null) === 1062 ? new self($errores) : $e;
    }
}
