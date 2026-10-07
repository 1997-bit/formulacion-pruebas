<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Vista;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vista::class)]
#[Small]
final class VistaTest extends TestCase
{
    private const ARCHIVO = '/assets/css/prueba-vista.css';

    protected function setUp(): void
    {
        touch(RAIZ . '/public' . self::ARCHIVO, 1000);
    }

    protected function tearDown(): void
    {
        unlink(RAIZ . '/public' . self::ARCHIVO);
    }

    // #122
    public function testEstaticoLlevaVersion(): void
    {
        $url = Vista::estatico(self::ARCHIVO);

        $this->assertSame(self::ARCHIVO . '?v=1000', $url);
    }

    public function testEstaticoCambiaSiCambiaElArchivo(): void
    {
        touch(RAIZ . '/public' . self::ARCHIVO, 2000);
        clearstatcache();

        $url = Vista::estatico(self::ARCHIVO);

        $this->assertSame(self::ARCHIVO . '?v=2000', $url);
    }

    // RNF-01
    public function testCapturarEscapaLosDatos(): void
    {
        $html = Vista::capturar('partials/mensajes', ['flash' => '<script>x</script>']);

        $this->assertStringContainsString('&lt;script&gt;x&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testCapturarVistaInexistenteLanzaError(): void
    {
        $this->expectException(\RuntimeException::class);

        Vista::capturar('no/existe');
    }
}
