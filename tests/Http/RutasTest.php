<?php

declare(strict_types=1);

namespace Tests\Http;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Large;
use Tests\Apoyo\PruebaHttp;

// RF-21, RNF-02: matriz rol × ruta de config/rutas.php.
#[CoversNothing]
#[Large]
final class RutasTest extends PruebaHttp
{
    /**
     * @param callable(array{rol: ?int}, string): bool $filtro
     * @return array<string, array{string, string}>
     */
    private static function rutas(callable $filtro): array
    {
        $casos = [];
        foreach (require RAIZ . '/config/rutas.php' as $clave => $ruta) {
            if ($filtro($ruta, explode(' ', $clave)[0])) {
                $casos[$clave] = explode(' ', $clave);
            }
        }

        return $casos;
    }

    /** @return array<string, array{string, string}> */
    public static function conRol(): array
    {
        return self::rutas(fn ($ruta) => $ruta['rol'] !== null);
    }

    /** @return array<string, array{string, string}> */
    public static function deAdmin(): array
    {
        return self::rutas(fn ($ruta) => $ruta['rol'] === 1);
    }

    /** @return array<string, array{string, string}> */
    public static function deAdminGet(): array
    {
        return self::rutas(fn ($ruta, $metodo) => $ruta['rol'] === 1 && $metodo === 'GET');
    }

    #[DataProvider('conRol')]
    public function testAnonimoVaAlLoginEnRutaConRol(string $metodo, string $ruta): void
    {
        $cliente = $this->cliente();

        $respuesta = $cliente->pedir($metodo, $ruta);

        $this->assertSame([302, '/'], [$respuesta['codigo'], $respuesta['cabeceras']['location'] ?? null]);
    }

    #[DataProvider('deAdmin')]
    public function testTesterRecibe403EnRutaDeAdmin(string $metodo, string $ruta): void
    {
        $cliente = $this->entrar($this->usuario());

        $respuesta = $cliente->pedir($metodo, $ruta);

        $this->assertSame(403, $respuesta['codigo']);
    }

    #[DataProvider('deAdminGet')]
    public function testAdminPasaEnRutaDeAdmin(string $metodo, string $ruta): void
    {
        $cliente = $this->entrar($this->usuario(1));

        $respuesta = $cliente->pedir($metodo, $ruta);

        $this->assertContains($respuesta['codigo'], [200, 404]);
    }

    public function testRutaInexistenteDa404(): void
    {
        $respuesta = $this->cliente()->get('/no-existe');

        $this->assertSame(404, $respuesta['codigo']);
    }
}
