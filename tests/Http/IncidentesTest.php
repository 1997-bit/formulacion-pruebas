<?php

declare(strict_types=1);

namespace Tests\Http;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;
use Tests\Apoyo\PruebaHttp;

// RF-17
#[CoversNothing]
#[Large]
final class IncidentesTest extends PruebaHttp
{
    // BUG-050: sin caso, busca por código; con un solo resultado va al formulario del caso.
    public function testCodigoDeUnSoloCasoVaAlFormulario(): void
    {
        $tester = $this->usuario();
        $caso = $this->caso($this->proyecto($tester), $tester);

        $respuesta = $this->entrar($tester)->get('/formularios/incidentes/registrar?codigo=sis-001');

        $this->assertSame('/formularios/incidentes/registrar?caso=' . $caso, $respuesta['cabeceras']['location'] ?? null);
    }
}
