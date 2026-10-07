<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Core\ErrorNoEncontrado;
use App\Core\ErrorValidacion;
use App\Models\FilasModelo;
use App\Services\TablaDecisionServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-11
#[CoversClass(TablaDecisionServicio::class)]
#[Medium]
final class TablaDecisionServicioTest extends BaseDatos
{
    /** @return array{condiciones: list<array{texto: string, reglas: list<string>}>, acciones: list<array{texto: string, reglas: list<string>}>} */
    private static function tabla(string $condicion = 'a', string $accion = 'X'): array
    {
        return [
            'condiciones' => [
                ['texto' => $condicion, 'reglas' => ['V', 'V', 'F', 'F']],
                ['texto' => 'b', 'reglas' => ['V', 'F', 'V', 'F']],
            ],
            'acciones' => [
                ['texto' => 'x', 'reglas' => [$accion, 'X', 'X', 'X']],
            ],
        ];
    }

    private static function huella(int $requerimientoId): string
    {
        return FilasModelo::huella(TablaDecisionServicio::TABLAS, 'requerimiento_id', $requerimientoId);
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function textos(): array
    {
        return [
            '255 caracteres' => [str_repeat('a', 255), []],
            '256 caracteres' => [str_repeat('a', 256), ['condicion.0' => 'Máximo 255 caracteres.']],
        ];
    }

    /** @param array<string, string> $esperado */
    #[DataProvider('textos')]
    public function testLargoDelTexto(string $texto, array $esperado): void
    {
        $usuario = $this->usuario();
        $req = $this->requerimiento($this->proyecto($usuario));

        $errores = $this->errores(fn () => TablaDecisionServicio::guardar($req, self::tabla($texto), self::huella($req), $this->sesion($usuario)));

        $this->assertSame($esperado, $errores);
    }

    public function testReglaSinAccionNoGuarda(): void
    {
        $usuario = $this->usuario();
        $req = $this->requerimiento($this->proyecto($usuario));

        $errores = $this->errores(fn () => TablaDecisionServicio::guardar($req, self::tabla('a', '-'), self::huella($req), $this->sesion($usuario)));

        $this->assertSame(['regla.0' => 'Marque una acción.'], $errores);
    }

    public function testReglasQueNoCoincidenConLasCondicionesNoGuarda(): void
    {
        $usuario = $this->usuario();
        $req = $this->requerimiento($this->proyecto($usuario));
        $tabla = self::tabla();
        $tabla['condiciones'][] = ['texto' => 'c', 'reglas' => ['V', 'F', 'V', 'F']];

        $errores = $this->errores(fn () => TablaDecisionServicio::guardar($req, $tabla, self::huella($req), $this->sesion($usuario)));

        $this->assertSame('Las reglas no coinciden con las condiciones. Vuelva a abrir la tabla.', $errores['general'] ?? null);
    }

    // #100
    public function testHuellaViejaNoGuarda(): void
    {
        $usuario = $this->usuario();
        $req = $this->requerimiento($this->proyecto($usuario));
        $vieja = self::huella($req);
        TablaDecisionServicio::guardar($req, self::tabla(), $vieja, $this->sesion($usuario));

        $errores = $this->errores(fn () => TablaDecisionServicio::guardar($req, self::tabla('otra'), $vieja, $this->sesion($usuario)));

        $this->assertSame(ErrorValidacion::OTRO_GUARDO, $errores);
    }

    public function testSinDatosDaLaTablaInicial(): void
    {
        $usuario = $this->usuario();
        $req = $this->requerimiento($this->proyecto($usuario));

        $tabla = TablaDecisionServicio::tabla($req, $this->sesion($usuario));

        $this->assertSame([
            'condiciones' => [
                ['texto' => '', 'reglas' => ['V', 'V', 'F', 'F']],
                ['texto' => '', 'reglas' => ['V', 'F', 'V', 'F']],
            ],
            'acciones' => [
                ['texto' => '', 'reglas' => ['-', '-', '-', '-']],
                ['texto' => '', 'reglas' => ['-', '-', '-', '-']],
            ],
        ], $tabla);
    }

    /** @return array<string, array{callable(int, array{id: int, rol: int}): mixed}> */
    public static function lecturas(): array
    {
        return [
            'tabla' => [fn (int $req, array $u) => TablaDecisionServicio::tabla($req, $u)],
            'guardado' => [fn (int $req, array $u) => TablaDecisionServicio::guardado($req, $u)],
        ];
    }

    // BUG-026
    #[DataProvider('lecturas')]
    public function testRequerimientoAjenoEsNoEncontrado(callable $accion): void
    {
        $req = $this->requerimiento($this->proyecto());
        $tester = $this->sesion($this->usuario());

        $this->expectException(ErrorNoEncontrado::class);

        $accion($req, $tester);
    }

    // RNF-09
    public function testPaginaSoloTraeLosDelTesterConSuConteo(): void
    {
        $usuario = $this->usuario();
        $req = $this->requerimiento($this->proyecto($usuario));
        $this->requerimiento($this->proyecto());
        TablaDecisionServicio::guardar($req, self::tabla(), self::huella($req), $this->sesion($usuario));

        [$requerimientos] = TablaDecisionServicio::pagina($this->sesion($usuario), 1);

        $this->assertSame([[$req, 3]], array_map(fn (array $r): array => [$r['id'], $r['filas']], $requerimientos));
    }
}
