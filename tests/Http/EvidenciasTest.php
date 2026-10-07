<?php

declare(strict_types=1);

namespace Tests\Http;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;
use Tests\Apoyo\PruebaHttp;

// RNF-03, #121: descarga de evidencias.
#[CoversNothing]
#[Large]
final class EvidenciasTest extends PruebaHttp
{
    public function testMiembroDescargaSinAdivinarTipoNiGuardarEnCache(): void
    {
        $tester = $this->usuario();
        $evidencia = $this->evidencia($this->caso($this->proyecto($tester), $tester), $tester);

        $respuesta = $this->entrar($tester)->get('/evidencias/ver?id=' . $evidencia);

        $this->assertSame(
            [200, 'nosniff', 'private, no-cache', 'log de prueba'],
            [$respuesta['codigo'], $respuesta['cabeceras']['x-content-type-options'] ?? null, $respuesta['cabeceras']['cache-control'] ?? null, $respuesta['cuerpo']],
        );
    }

    // BUG-026: ajeno da 404, no 403.
    public function testAjenoRecibe404(): void
    {
        $autor = $this->usuario();
        $evidencia = $this->evidencia($this->caso($this->proyecto($autor), $autor), $autor);

        $respuesta = $this->entrar($this->usuario())->get('/evidencias/ver?id=' . $evidencia);

        $this->assertSame(404, $respuesta['codigo']);
    }
}
