<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Helpers\Catalogo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(Catalogo::class)]
#[Small]
final class CatalogoTest extends TestCase
{
    public function testValoresDevuelveElCatalogo(): void
    {
        $valores = Catalogo::valores('estado_caso');

        $this->assertSame([0, 1, 2], array_keys($valores));
        $this->assertSame('Pendiente', $valores[0]['texto']);
    }

    public function testValoresDeCatalogoInexistenteLanzaError(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Catalogo::valores('no_existe');
    }

    /** @return array<string, array{int|string|null, bool}> */
    public static function claves(): array
    {
        return [
            'entero' => [2, true],
            'cero' => [0, true],
            'texto numérico' => ['2', true],
            'fuera de catálogo' => [9, false],
            'null' => [null, false],
            'vacío' => ['', false],
            'decimal' => ['2.0', false],
            'con espacio' => [' 2', false],
            'texto' => ['OK', false],
        ];
    }

    #[DataProvider('claves')]
    public function testExiste(int|string|null $clave, bool $esperado): void
    {
        $existe = Catalogo::existe('estado_caso', $clave);

        $this->assertSame($esperado, $existe);
    }

    /** @return array<string, array{int|string|null, string}> */
    public static function textos(): array
    {
        return [
            'entero' => [1, 'OK'],
            'texto numérico' => ['1', 'OK'],
            'desconocida tal cual' => [9, '9'],
            'null' => [null, ''],
        ];
    }

    #[DataProvider('textos')]
    public function testTexto(int|string|null $clave, string $esperado): void
    {
        $texto = Catalogo::texto('estado_caso', $clave);

        $this->assertSame($esperado, $texto);
    }

    public function testInsigniaUsaLaVariante(): void
    {
        $html = Catalogo::insignia('estado_caso', 2);

        $this->assertSame('<span class="insignia insignia-peligro">FAULT</span>', $html);
    }

    // RNF-01
    public function testInsigniaDesconocidaEscapaYUsaBorde(): void
    {
        $html = Catalogo::insignia('estado_caso', '<b>');

        $this->assertSame('<span class="insignia insignia-borde">&lt;b&gt;</span>', $html);
    }

    /** @return array<string, array{int|string|null, string}> */
    public static function elegidas(): array
    {
        $ninguna = '<option value="0">Pendiente</option><option value="1">OK</option><option value="2">FAULT</option>';
        $uno = '<option value="0">Pendiente</option><option value="1" selected>OK</option><option value="2">FAULT</option>';
        $cero = '<option value="0" selected>Pendiente</option><option value="1">OK</option><option value="2">FAULT</option>';

        return [
            'ninguna' => [null, $ninguna],
            'entero' => [1, $uno],
            'texto numérico' => ['1', $uno],
            'cero' => [0, $cero],
            'vacío no marca cero' => ['', $ninguna],
            'fuera de catálogo' => [9, $ninguna],
        ];
    }

    #[DataProvider('elegidas')]
    public function testOpcionesMarcaLaElegida(int|string|null $elegida, string $esperado): void
    {
        $html = Catalogo::opciones('estado_caso', $elegida);

        $this->assertSame($esperado, $html);
    }
}
