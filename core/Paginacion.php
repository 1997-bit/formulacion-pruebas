<?php

declare(strict_types=1);

namespace App\Core;

// Listados de 20 filas por página (RNF-09).
final class Paginacion
{
    public const POR_PAGINA = 20;

    public readonly int $total;
    public readonly int $pagina;

    // Una página fuera de rango se lleva a la primera o a la última.
    public function __construct(int $total, int $pagina)
    {
        $this->total = max(0, $total);
        $this->pagina = min(max(1, $pagina), $this->paginas());
    }

    public function offset(): int
    {
        return ($this->pagina - 1) * self::POR_PAGINA;
    }

    // Una lista vacía tiene una página.
    public function paginas(): int
    {
        return max(1, (int) ceil($this->total / self::POR_PAGINA));
    }
}
