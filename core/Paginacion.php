<?php

declare(strict_types=1);

namespace App\Core;

// RNF-09
final class Paginacion
{
    public const POR_PAGINA = 20;
    // Enlaces de página: primera, última y las de alrededor de la actual (BUG-049).
    private const VENTANA = 7;

    public readonly int $total;
    public readonly int $pagina;

    // Fuera de rango: primera o última.
    public function __construct(int $total, int $pagina)
    {
        $this->total = max(0, $total);
        $this->pagina = min(max(1, $pagina), $this->paginas());
    }

    public function offset(): int
    {
        return ($this->pagina - 1) * self::POR_PAGINA;
    }

    public function paginas(): int
    {
        return max(1, (int) ceil($this->total / self::POR_PAGINA));
    }

    /**
     * Páginas a enlazar. null: salto (…). Ej.: 1, null, 48, 49, 50, 51, 52, null, 1000.
     *
     * @return list<int|null>
     */
    public function ventana(): array
    {
        $ultima = $this->paginas();
        if ($ultima <= self::VENTANA) {
            return range(1, $ultima);
        }
        $centro = self::VENTANA - 2;
        $desde = max(2, min($this->pagina - intdiv($centro, 2), $ultima - $centro));
        $hasta = $desde + $centro - 1;

        return [1, ...($desde > 2 ? [null] : []), ...range($desde, $hasta), ...($hasta < $ultima - 1 ? [null] : []), $ultima];
    }
}
