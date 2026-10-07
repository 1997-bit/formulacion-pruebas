<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\ErrorValidacion;
use App\Core\Validador;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validador::class)]
#[CoversClass(ErrorValidacion::class)]
#[Small]
final class ValidadorTest extends TestCase
{
    /** @return array<string, array{mixed, array<string, string>}> */
    public static function requeridos(): array
    {
        return [
            'texto' => ['a', []],
            'cero' => ['0', []],
            'entero cero' => [0, []],
            'vacío' => ['', ['campo' => 'Es obligatorio.']],
            'solo espacios' => ['  ', ['campo' => 'Es obligatorio.']],
            'null' => [null, ['campo' => 'Es obligatorio.']],
        ];
    }

    /** @param array<string, string> $esperado */
    #[DataProvider('requeridos')]
    public function testRequerido(mixed $valor, array $esperado): void
    {
        $errores = $this->errores((new Validador())->requerido('campo', $valor));

        $this->assertSame($esperado, $errores);
    }

    /** @return array<string, array{?string, array<string, string>}> */
    public static function fechas(): array
    {
        return [
            'ISO válida' => ['2026-12-31', []],
            'bisiesto' => ['2028-02-29', []],
            'vacía pasa' => ['', []],
            'null pasa' => [null, []],
            'formato local' => ['31/12/2026', ['fecha' => 'Fecha inválida.']],
            '30 de febrero' => ['2026-02-30', ['fecha' => 'Fecha inválida.']],
            '29 de febrero no bisiesto' => ['2026-02-29', ['fecha' => 'Fecha inválida.']],
            'mes 13' => ['2026-13-01', ['fecha' => 'Fecha inválida.']],
            'sin ceros' => ['2026-1-5', ['fecha' => 'Fecha inválida.']],
            'texto' => ['mañana', ['fecha' => 'Fecha inválida.']],
        ];
    }

    // RNF-06
    /** @param array<string, string> $esperado */
    #[DataProvider('fechas')]
    public function testFecha(?string $valor, array $esperado): void
    {
        $errores = $this->errores((new Validador())->fecha('fecha', $valor));

        $this->assertSame($esperado, $errores);
    }

    /** @return array<string, array{int|string|null, array<string, string>}> */
    public static function claves(): array
    {
        return [
            'entero válido' => [2, []],
            'texto numérico válido' => ['2', []],
            'vacío pasa' => ['', []],
            'null pasa' => [null, []],
            'fuera de catálogo' => [9, ['estado' => 'Valor no válido.']],
            'texto' => ['OK', ['estado' => 'Valor no válido.']],
        ];
    }

    /** @param array<string, string> $esperado */
    #[DataProvider('claves')]
    public function testCatalogo(int|string|null $valor, array $esperado): void
    {
        $errores = $this->errores((new Validador())->catalogo('estado', 'estado_caso', $valor));

        $this->assertSame($esperado, $errores);
    }

    public function testReglaCumplidaNoAgregaError(): void
    {
        $errores = $this->errores((new Validador())->regla('campo', true, 'Mal.'));

        $this->assertSame([], $errores);
    }

    public function testReglaIncumplidaAgregaMensaje(): void
    {
        $errores = $this->errores((new Validador())->regla('campo', false, 'Mal.'));

        $this->assertSame(['campo' => 'Mal.'], $errores);
    }

    public function testPrimerErrorDelCampoGana(): void
    {
        $validador = (new Validador())
            ->regla('campo', false, 'Primero.')
            ->regla('campo', false, 'Segundo.');

        $errores = $this->errores($validador);

        $this->assertSame(['campo' => 'Primero.'], $errores);
    }

    public function testComprobarSinErroresNoLanza(): void
    {
        $validador = (new Validador())->requerido('nombre', 'Ana');

        $validador->comprobar();

        $this->addToAssertionCount(1);
    }

    public function testComprobarLanzaConTodosLosErrores(): void
    {
        $validador = (new Validador())
            ->requerido('nombre', '')
            ->fecha('fecha', '2026-02-30');

        $errores = $this->errores($validador);

        $this->assertSame(['nombre' => 'Es obligatorio.', 'fecha' => 'Fecha inválida.'], $errores);
    }

    /** @return array<string, string> */
    private function errores(Validador $validador): array
    {
        try {
            $validador->comprobar();
        } catch (ErrorValidacion $e) {
            return $e->errores;
        }

        return [];
    }
}
