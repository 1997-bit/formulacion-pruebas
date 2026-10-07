<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Env;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(Env::class)]
#[Small]
final class EnvTest extends TestCase
{
    private string $archivo;

    /** @var array<string, mixed> */
    private array $env;

    protected function setUp(): void
    {
        $this->env = $_ENV;
        $this->archivo = (string) tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($this->archivo, <<<'ENV'
            # comentario
            PRUEBA_SIMPLE=valor

            PRUEBA_ESPACIOS = con espacios 
            PRUEBA_DOBLES="entre comillas"
            PRUEBA_SIMPLES='simples'
            PRUEBA_IGUAL=a=b
            #PRUEBA_COMENTADA=no
            linea sin igual
            ENV);
    }

    protected function tearDown(): void
    {
        $_ENV = $this->env;
        unlink($this->archivo);
    }

    public function testLeeClaveSimple(): void
    {
        Env::cargar($this->archivo);

        $this->assertSame('valor', Env::get('PRUEBA_SIMPLE'));
    }

    public function testQuitaEspaciosAlrededor(): void
    {
        Env::cargar($this->archivo);

        $this->assertSame('con espacios', Env::get('PRUEBA_ESPACIOS'));
    }

    public function testQuitaComillasDoblesYSimples(): void
    {
        Env::cargar($this->archivo);

        $this->assertSame(['entre comillas', 'simples'], [Env::get('PRUEBA_DOBLES'), Env::get('PRUEBA_SIMPLES')]);
    }

    public function testSoloElPrimerIgualSepara(): void
    {
        Env::cargar($this->archivo);

        $this->assertSame('a=b', Env::get('PRUEBA_IGUAL'));
    }

    public function testIgnoraComentarios(): void
    {
        Env::cargar($this->archivo);

        $this->assertNull(Env::get('#PRUEBA_COMENTADA'));
    }

    public function testClaveInexistenteDevuelveDefecto(): void
    {
        Env::cargar($this->archivo);

        $this->assertSame('defecto', Env::get('PRUEBA_NO_EXISTE', 'defecto'));
    }

    public function testSinArchivoLanzaError(): void
    {
        $this->expectException(\RuntimeException::class);

        Env::cargar($this->archivo . '.no-existe');
    }
}
