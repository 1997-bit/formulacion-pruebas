<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Helpers\Impresion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(Impresion::class)]
#[Small]
final class ImpresionTest extends TestCase
{
    public function testDocumentoLlevaNumeroTituloYCodigo(): void
    {
        $this->assertSame(
            [
                ['titulo' => 'Formulario 2 · Matriz de clases de equivalencia · RF-01', 'proyecto' => 'Biblioteca', 'marca' => ''],
                ['titulo' => 'Reporte de cierre', 'proyecto' => 'Biblioteca', 'marca' => 'NO-GO'],
            ],
            [Impresion::documento(2, 'Biblioteca', 'RF-01'), Impresion::documento(0, 'Biblioteca', '', 'NO-GO')],
        );
    }

    // Una comilla del nombre no cierra la cadena CSS
    public function testVariablesEscapanComillas(): void
    {
        $css = Impresion::variables(['titulo' => 'T', 'proyecto' => "O'Hara", 'marca' => 'BORRADOR'], 'Ana', new \DateTimeImmutable('2026-10-15 09:05'));

        $this->assertSame("--doc-titulo:'T';--doc-proyecto:'O\\'Hara';--doc-marca:'BORRADOR';--doc-pie:'Impreso el 15/10/2026 09:05 por Ana'", $css);
    }
}
