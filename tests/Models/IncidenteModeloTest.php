<?php

declare(strict_types=1);

namespace Tests\Models;

use App\Models\IncidenteModelo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// Estado 2 es Cerrado.
#[CoversClass(IncidenteModelo::class)]
#[Medium]
final class IncidenteModeloTest extends BaseDatos
{
    // BUG-048
    public function testAbiertosDeCasoCuentaSoloLosNoCerrados(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto();
        $caso = $this->caso($proyecto, $this->requerimiento($proyecto), $usuario);
        $this->incidente($proyecto, $caso, $usuario, 0);
        $this->incidente($proyecto, $caso, $usuario, 1);
        $this->incidente($proyecto, $caso, $usuario, 2);

        $abiertos = IncidenteModelo::abiertosDeCaso($caso);

        $this->assertSame(2, $abiertos);
    }

    // BUG-035
    public function testStoppersAbiertosCuentaSoloStoppersNoCerrados(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto();
        $caso = $this->caso($proyecto, $this->requerimiento($proyecto), $usuario);
        $this->incidente($proyecto, $caso, $usuario, 0, 1);
        $this->incidente($proyecto, $caso, $usuario, 2, 1);
        $this->incidente($proyecto, $caso, $usuario, 0, 0);

        $stoppers = IncidenteModelo::stoppersAbiertos($proyecto);

        $this->assertSame(1, $stoppers);
    }
}
