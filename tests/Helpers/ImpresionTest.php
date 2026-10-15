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
                ['titulo' => 'Formulario 2 · Matriz de clases de equivalencia · RF-01', 'proyecto' => 'Biblioteca'],
                ['titulo' => 'Reporte de cierre', 'proyecto' => 'Biblioteca'],
            ],
            [Impresion::documento(2, 'Biblioteca', 'RF-01'), Impresion::documento(0, 'Biblioteca')],
        );
    }

    // Una comilla del nombre no cierra la cadena CSS.
    public function testVariablesEscapanComillas(): void
    {
        $css = Impresion::variables(['titulo' => 'T', 'proyecto' => "O'Hara"], 'Ana', new \DateTimeImmutable('2026-10-15 09:05'), 'abc123');

        $this->assertSame("--doc-titulo:'T';--doc-proyecto:'O\\'Hara';--doc-pie:'Impreso el 15/10/2026 09:05 por Ana · ID abc123'", $css);
    }

    // Postmortem: el ID de la petición cruza la hoja con los logs. Sin .git ni navegador: "sin dato".
    public function testMetadatosLlevanPeticionYVersion(): void
    {
        $m = Impresion::metadatos(
            ['titulo' => 'T', 'proyecto' => 'P'],
            ['nombre' => 'Ana', 'usuario' => 'ana', 'rol' => 0],
            new \DateTimeImmutable('2026-10-15T09:05:00-04:00'),
            '/casos/resultado?id=1',
            'abc123',
            Impresion::version('/no/existe'),
            '',
        );

        $this->assertSame(
            ['Ana (ana, tester)', '2026-10-15T09:05:00-04:00', 'abc123', 'sin dato', 'sin dato'],
            [$m['Usuario'], $m['Generado'], $m['ID de la petición'], $m['Versión de la app'], $m['Navegador']],
        );
    }
}
