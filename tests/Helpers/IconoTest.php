<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Helpers\Icono;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(Icono::class)]
#[Small]
final class IconoTest extends TestCase
{
    public function testIconoExistenteSinComentarioYOcultoAlLector(): void
    {
        $svg = Icono::svg('check');

        $this->assertStringStartsWith("<svg\n  class=\"icono\" aria-hidden=\"true\" focusable=\"false\"", $svg);
        $this->assertStringNotContainsString('<!--', $svg);
        $this->assertStringNotContainsString('lucide', $svg);
    }

    public function testClasePropiaSeEscapa(): void
    {
        $svg = Icono::svg('check', 'grande"x');

        $this->assertStringContainsString('class="grande&quot;x" aria-hidden="true"', $svg);
    }

    public function testIconoInexistenteLanzaError(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Icono::svg('no-existe');
    }

    public function testNombreConRutaNoSaleDeIconos(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Icono::svg('../css/app');
    }
}
