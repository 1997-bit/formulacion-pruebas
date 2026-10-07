<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Core\ErrorNoEncontrado;
use App\Core\ErrorValidacion;
use App\Models\FilasModelo;
use App\Services\ValorLimiteServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-10
#[CoversClass(ValorLimiteServicio::class)]
#[Medium]
final class ValorLimiteServicioTest extends BaseDatos
{
    /** @return array<string, string> */
    private static function fila(string $campo = 'edad'): array
    {
        return ['campo' => $campo] + array_fill_keys(array_keys(ValorLimiteServicio::COLUMNAS), 'x');
    }

    private static function huella(int $requerimientoId): string
    {
        return FilasModelo::huella(ValorLimiteServicio::TABLAS, 'requerimiento_id', $requerimientoId);
    }

    /** @return array<string, array{int, array<string, string>}> */
    public static function cantidades(): array
    {
        return [
            '0 filas' => [0, ['general' => 'Agregue al menos una fila.']],
            '1 fila' => [1, []],
            '255 filas' => [255, []],
            '256 filas' => [256, ['general' => 'Máximo 255 filas.']],
        ];
    }

    /** @param array<string, string> $esperado */
    #[DataProvider('cantidades')]
    public function testLimiteDeFilas(int $cantidad, array $esperado): void
    {
        $usuario = $this->usuario();
        $req = $this->requerimiento($this->proyecto($usuario));
        $filas = array_fill(0, $cantidad, self::fila());

        $errores = $this->errores(fn () => ValorLimiteServicio::guardar($req, $filas, self::huella($req), $this->sesion($usuario)));

        $this->assertSame($esperado, $errores);
    }

    // #100
    public function testHuellaViejaNoGuarda(): void
    {
        $usuario = $this->usuario();
        $req = $this->requerimiento($this->proyecto($usuario));
        $vieja = self::huella($req);
        ValorLimiteServicio::guardar($req, [self::fila()], $vieja, $this->sesion($usuario));

        $errores = $this->errores(fn () => ValorLimiteServicio::guardar($req, [self::fila('otro')], $vieja, $this->sesion($usuario)));

        $this->assertSame(ErrorValidacion::OTRO_GUARDO, $errores);
    }

    // #98
    public function testGuardarSinCambiosNoCambiaElAutor(): void
    {
        $autor = $this->usuario();
        $otro = $this->usuario();
        $req = $this->requerimiento($this->proyecto($autor, $otro));
        ValorLimiteServicio::guardar($req, [self::fila()], self::huella($req), $this->sesion($autor));

        ValorLimiteServicio::guardar($req, [self::fila()], self::huella($req), $this->sesion($otro));

        $this->assertSame(1, $this->contar('SELECT COUNT(*) FROM valor_limite WHERE requerimiento_id = ? AND guardado_por = ?', $req, $autor));
    }

    /** @return array<string, array{callable(int, array{id: int, rol: int}): mixed}> */
    public static function lecturas(): array
    {
        return [
            'filas' => [fn (int $req, array $u) => ValorLimiteServicio::filas($req, $u)],
            'guardado' => [fn (int $req, array $u) => ValorLimiteServicio::guardado($req, $u)],
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
        ValorLimiteServicio::guardar($req, [self::fila(), self::fila()], self::huella($req), $this->sesion($usuario));

        [$requerimientos] = ValorLimiteServicio::pagina($this->sesion($usuario), 1);

        $this->assertSame([[$req, 2]], array_map(fn (array $r): array => [$r['id'], $r['filas']], $requerimientos));
    }
}
