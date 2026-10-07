<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Ruteador;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

// RF-21
#[CoversClass(Ruteador::class)]
#[Small]
final class RuteadorTest extends TestCase
{
    public function testRutaExistenteDevuelveControladorAccionRolYRf(): void
    {
        $ruta = Ruteador::buscar('POST', '/casos/eliminar');

        $this->assertSame(['controlador' => 'Casos', 'accion' => 'eliminar', 'rol' => 1, 'rf' => 'RF-07'], $ruta);
    }

    public function testRaizEsLaPaginaDeLogin(): void
    {
        $ruta = Ruteador::buscar('GET', '/');

        $this->assertSame(['controlador' => 'Acceso', 'accion' => 'login', 'rol' => null, 'rf' => 'RF-01'], $ruta);
    }

    public function testIgnoraCadenaDeConsulta(): void
    {
        $ruta = Ruteador::buscar('GET', '/casos/editar?id=5');

        $this->assertSame('editar', $ruta['accion'] ?? null);
    }

    /** @return array<string, array{string, string}> */
    public static function rutasInexistentes(): array
    {
        return [
            'camino no existe' => ['GET', '/no-existe'],
            'método distinto' => ['GET', '/casos/eliminar'],
            'barra final' => ['GET', '/dashboard/'],
        ];
    }

    #[DataProvider('rutasInexistentes')]
    public function testRutaInexistenteDevuelveNull(string $metodo, string $uri): void
    {
        $ruta = Ruteador::buscar($metodo, $uri);

        $this->assertNull($ruta);
    }
}
