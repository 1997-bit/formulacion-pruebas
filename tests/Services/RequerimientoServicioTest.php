<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Core\ErrorNoEncontrado;
use App\Services\RequerimientoServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-04
#[CoversClass(RequerimientoServicio::class)]
#[Medium]
final class RequerimientoServicioTest extends BaseDatos
{
    /** @return array<string, string> */
    private static function datos(int $proyecto, string $codigo = 'RF-01'): array
    {
        return ['proyecto_id' => (string) $proyecto, 'codigo' => $codigo, 'descripcion' => 'Descripción'];
    }

    private function requerimientos(int $proyecto, int $cuantos): void
    {
        foreach (range(1, $cuantos) as $n) {
            $this->requerimiento($proyecto, sprintf('RF-%02d', $n));
        }
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function codigos(): array
    {
        return [
            'RF-01' => ['RF-01', []],
            'RNF-01' => ['RNF-01', []],
            'minúscula y 3 dígitos' => ['rf-001', []],
            'un dígito' => ['RF-1', ['codigo' => 'Use RF-01 o RNF-01.']],
            'otro prefijo' => ['X-01', ['codigo' => 'Use RF-01 o RNF-01.']],
        ];
    }

    /** @param array<string, string> $esperado */
    #[DataProvider('codigos')]
    public function testFormatoDelCodigo(string $codigo, array $esperado): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);

        $errores = $this->errores(fn () => RequerimientoServicio::registrar(self::datos($proyecto, $codigo), $this->sesion($tester)));

        $this->assertSame($esperado, $errores);
    }

    public function testRnfQuedaComoNoFuncional(): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);

        RequerimientoServicio::registrar(self::datos($proyecto, 'rnf-02'), $this->sesion($tester));

        $this->assertSame(1, $this->contar("SELECT no_funcional FROM requerimientos WHERE proyecto_id = ? AND codigo = 'RNF-02'", $proyecto));
    }

    public function testProyectoAjenoDaError(): void
    {
        $proyecto = $this->proyecto($this->usuario());

        $errores = $this->errores(fn () => RequerimientoServicio::registrar(self::datos($proyecto), $this->sesion($this->usuario())));

        $this->assertSame(['proyecto_id' => 'Valor no válido.'], $errores);
    }

    // #101
    public function testCodigoRepetidoEnElProyectoDaErrorDeCampo(): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);
        $this->requerimiento($proyecto, 'RF-01');

        $errores = $this->errores(fn () => RequerimientoServicio::registrar(self::datos($proyecto), $this->sesion($tester)));

        $this->assertSame(['codigo' => 'Ese código ya existe en el proyecto.'], $errores);
    }

    // RF-21
    public function testTesterSoloListaSusProyectos(): void
    {
        $tester = $this->usuario();
        $suyo = $this->requerimiento($this->proyecto($tester));
        $this->requerimiento($this->proyecto($this->usuario()));

        $lista = RequerimientoServicio::listar($this->sesion($tester));

        $this->assertSame([$suyo], array_map(intval(...), array_column($lista, 'id')));
    }

    public function testPaginaDelTesterCuentaSoloLosSuyos(): void
    {
        $tester = $this->usuario();
        $this->requerimiento($this->proyecto($tester));
        $this->requerimiento($this->proyecto($this->usuario()));

        [$filas, $paginacion] = RequerimientoServicio::pagina($this->sesion($tester), 1);

        $this->assertSame([1, 1], [count($filas), $paginacion->paginas()]);
    }

    // RNF-09
    public function testVeintiunoDejanUnoEnLaPagina2(): void
    {
        $tester = $this->usuario();
        $this->requerimientos($this->proyecto($tester), 21);

        [$filas, $paginacion] = RequerimientoServicio::pagina($this->sesion($tester), 2);

        $this->assertSame([1, 2], [count($filas), $paginacion->paginas()]);
    }

    public function testProyectosDelTesterSonLosSuyos(): void
    {
        $tester = $this->usuario();
        $suyo = $this->proyecto($tester);
        $this->proyecto($this->usuario());

        $proyectos = RequerimientoServicio::proyectos($this->sesion($tester));

        $this->assertSame([$suyo], array_map(intval(...), array_column($proyectos, 'id')));
    }

    public function testVerInexistenteEsNull(): void
    {
        $ver = RequerimientoServicio::ver(999999999, $this->sesion($this->usuario()));

        $this->assertNull($ver);
    }

    // BUG-026
    public function testVerAjenoNoSeEncuentra(): void
    {
        $requerimiento = $this->requerimiento($this->proyecto($this->usuario()));

        $this->expectException(ErrorNoEncontrado::class);

        RequerimientoServicio::ver($requerimiento, $this->sesion($this->usuario()));
    }
}
