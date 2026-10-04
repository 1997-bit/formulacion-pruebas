<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Paginacion;
use PHPUnit\Framework\TestCase;

final class PaginacionTest extends TestCase
{
    public function testCuentaPaginas(): void
    {
        $this->assertSame(1, (new Paginacion(0, 1))->paginas());
        $this->assertSame(1, (new Paginacion(20, 1))->paginas());
        $this->assertSame(2, (new Paginacion(21, 1))->paginas());
        $this->assertSame(3, (new Paginacion(57, 1))->paginas());
    }

    public function testOffset(): void
    {
        $this->assertSame(0, (new Paginacion(57, 1))->offset());
        $this->assertSame(20, (new Paginacion(57, 2))->offset());
        $this->assertSame(40, (new Paginacion(57, 3))->offset());
    }

    public function testVentanaCorta(): void
    {
        $this->assertSame([1, 2, 3], (new Paginacion(57, 2))->ventana());
        $this->assertSame([1, 2, 3, 4, 5, 6, 7], (new Paginacion(140, 7))->ventana());
    }

    public function testVentanaLarga(): void
    {
        $this->assertSame([1, 2, 3, 4, 5, 6, null, 1000], (new Paginacion(20000, 1))->ventana());
        $this->assertSame([1, null, 48, 49, 50, 51, 52, null, 1000], (new Paginacion(20000, 50))->ventana());
        $this->assertSame([1, null, 995, 996, 997, 998, 999, 1000], (new Paginacion(20000, 1000))->ventana());
        $this->assertSame([1, 2, 3, 4, 5, 6, null, 8], (new Paginacion(160, 3))->ventana());
    }

    public function testPaginaFueraDeRango(): void
    {
        $this->assertSame(1, (new Paginacion(57, 0))->pagina);
        $this->assertSame(1, (new Paginacion(57, -4))->pagina);
        $this->assertSame(3, (new Paginacion(57, 99))->pagina);
        $this->assertSame(1, (new Paginacion(0, 5))->pagina);
    }
}
