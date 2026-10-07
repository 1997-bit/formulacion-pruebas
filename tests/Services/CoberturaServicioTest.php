<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Core\ErrorNoEncontrado;
use App\Models\FilasModelo;
use App\Services\CoberturaServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-12
#[CoversClass(CoberturaServicio::class)]
#[Medium]
final class CoberturaServicioTest extends BaseDatos
{
    private static function huella(int $requerimientoId): string
    {
        return FilasModelo::huella(CoberturaServicio::TABLAS, 'requerimiento_id', $requerimientoId);
    }

    /** @return array<string, array{array{total: string, cubiertos: string, herramienta: string}, array<string, string>}> */
    public static function metricas(): array
    {
        return [
            'total 0' => [['total' => '0', 'cubiertos' => '0', 'herramienta' => 'a'], ['11.total' => 'Debe ser mayor que 0.']],
            'total texto' => [['total' => 'diez', 'cubiertos' => '5', 'herramienta' => 'a'], ['11.total' => 'Número entero.']],
            'cubiertos mayor al total' => [['total' => '10', 'cubiertos' => '11', 'herramienta' => 'a'], ['11.cubiertos' => 'No puede ser mayor que el total.']],
            'herramienta 100' => [['total' => '10', 'cubiertos' => '5', 'herramienta' => str_repeat('a', 100)], []],
            'herramienta 101' => [['total' => '10', 'cubiertos' => '5', 'herramienta' => str_repeat('a', 101)], ['11.herramienta' => 'Máximo 100 caracteres.']],
            'ninguna métrica' => [['total' => '', 'cubiertos' => '', 'herramienta' => ''], ['general' => 'Llene al menos una métrica.']],
        ];
    }

    /**
     * @param array{total: string, cubiertos: string, herramienta: string} $metrica
     * @param array<string, string> $esperado
     */
    #[DataProvider('metricas')]
    public function testValidaLaMetrica(array $metrica, array $esperado): void
    {
        $usuario = $this->usuario();
        $req = $this->requerimiento($this->proyecto($usuario));

        $errores = $this->errores(fn () => CoberturaServicio::guardar($req, [11 => $metrica], self::huella($req), $this->sesion($usuario)));

        $this->assertSame($esperado, $errores);
    }

    /** @return array<string, array{callable(int, array{id: int, rol: int}): mixed}> */
    public static function lecturas(): array
    {
        return [
            'filas' => [fn (int $req, array $u) => CoberturaServicio::filas($req, $u)],
            'guardado' => [fn (int $req, array $u) => CoberturaServicio::guardado($req, $u)],
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
        $metrica = ['total' => '10', 'cubiertos' => '5', 'herramienta' => 'a'];
        CoberturaServicio::guardar($req, [11 => $metrica, 12 => $metrica], self::huella($req), $this->sesion($usuario));

        [$requerimientos] = CoberturaServicio::pagina($this->sesion($usuario), 1);

        $this->assertSame([[$req, 2]], array_map(fn (array $r): array => [$r['id'], $r['filas']], $requerimientos));
    }
}
