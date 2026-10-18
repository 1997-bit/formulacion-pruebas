<?php

declare(strict_types=1);

namespace Tests\Http;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Large;
use Tests\Apoyo\PruebaHttp;

// RF-25. Artículos de config/ayuda.php
#[CoversNothing]
#[Large]
final class AyudaTest extends PruebaHttp
{
    /** @return array<string, array{string}> */
    public static function slugs(): array
    {
        $titulos = array_merge(...array_values((require RAIZ . '/config/ayuda.php')['articulos']));

        return array_combine(array_keys($titulos), array_map(fn (string $s): array => [$s], array_keys($titulos)));
    }

    #[DataProvider('slugs')]
    public function testCadaArticuloAbre(string $slug): void
    {
        $respuesta = $this->entrar($this->usuario())->get('/ayuda?a=' . $slug);

        $this->assertSame(200, $respuesta['codigo']);
    }

    public function testCadaPantallaApuntaAUnArticulo(): void
    {
        $this->assertSame([], array_diff((require RAIZ . '/config/ayuda.php')['pantallas'], array_keys(self::slugs())));
    }

    // Así ?a no abre otra vista
    public function testSlugFueraDeLaListaDa404(): void
    {
        $cliente = $this->entrar($this->usuario());

        $codigos = array_map(fn (string $a): int => $cliente->get('/ayuda?' . $a)['codigo'], ['a=index', 'a=../dashboard', 'a[]=mapa']);

        $this->assertSame([404, 404, 404], $codigos);
    }
}
