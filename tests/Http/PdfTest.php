<?php

declare(strict_types=1);

namespace Tests\Http;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;
use Tests\Apoyo\PruebaHttp;

// Documento imprimible con ?formato=pdf: archivo PDF con metadatos XMP para un postmortem.
#[CoversNothing]
#[Large]
final class PdfTest extends PruebaHttp
{
    public function testCasoSaleComoPdfConPeticionYHash(): void
    {
        $tester = $this->usuario();
        $caso = $this->caso($this->proyecto($tester), $tester);

        $r = $this->entrar($tester)->get('/casos/resultado?id=' . $caso . '&formato=pdf');
        $peticion = $r['cabeceras']['x-request-id'] ?? '';

        $this->assertSame(
            [200, 'application/pdf', '%PDF', true, true, hash('sha256', $r['cuerpo'])],
            [
                $r['codigo'],
                $r['cabeceras']['content-type'] ?? null,
                substr($r['cuerpo'], 0, 4),
                str_contains($r['cuerpo'], '<dc:identifier>' . $peticion . '</dc:identifier>'),
                str_contains($r['cuerpo'], '<cp:documento>Formulario 1 · Registro de caso de prueba · SIS-001</cp:documento>'),
                $r['cabeceras']['x-content-sha256'] ?? null,
            ],
        );
    }

    // Sin documento, como la lista de casos: ?formato=pdf no cambia nada.
    public function testPaginaSinDocumentoIgnoraFormato(): void
    {
        $tester = $this->usuario();

        $r = $this->entrar($tester)->get('/casos/listar?formato=pdf');

        $this->assertStringStartsWith('text/html', $r['cabeceras']['content-type'] ?? '');
    }
}
