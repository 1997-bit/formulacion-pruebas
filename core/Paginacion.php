<?php

declare(strict_types=1);

namespace App\Core;

// Listados de 20 filas por página (RNF-09).
final class Paginacion
{
    public const POR_PAGINA = 20;

    public function __construct(public readonly int $total, public readonly int $pagina)
    {
        throw new \LogicException('Pendiente');
    }

    public function offset(): int
    {
        throw new \LogicException('Pendiente');
    }

    public function paginas(): int
    {
        throw new \LogicException('Pendiente');
    }
}
