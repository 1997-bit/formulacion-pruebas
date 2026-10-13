<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Entrada;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(Entrada::class)]
#[Small]
final class EntradaTest extends TestCase
{
    protected function tearDown(): void
    {
        $_POST = [];
    }

    public function testTextosVaciaLoQueNoEsTexto(): void
    {
        $_POST = ['nombre' => 'Ana', 'rol' => ['1']];

        $this->assertSame(['nombre' => 'Ana', 'rol' => '', 'falta' => ''], Entrada::textos(['nombre', 'rol', 'falta']));
    }

    public function testFilasEnElOrdenDeLaPantalla(): void
    {
        $_POST = ['campo' => ['edad', 'nombre'], 'valido' => ['18', ['x']]];

        $this->assertSame(
            [['campo' => 'edad', 'valido' => '18'], ['campo' => 'nombre', 'valido' => '']],
            Entrada::filas(['campo', 'valido']),
        );
    }

    public function testFilasSinPrimeraColumnaDaVacio(): void
    {
        $_POST = ['valido' => ['18']];

        $this->assertSame([], Entrada::filas(['campo', 'valido']));
    }
}
