<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Config\Conexion;
use App\Core\ErrorNoEncontrado;
use App\Models\EvidenciaModelo;
use App\Services\ReporteServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-23
#[CoversClass(ReporteServicio::class)]
#[Medium]
final class ReporteServicioTest extends BaseDatos
{
    // Caso del tester con estado; con evidencia (enlace) y con incidente (null: sin incidente).
    private function casoCon(int $proyecto, int $tester, int $estado, bool $evidencia, ?int $stopper, string $codigo = 'SIS-001'): int
    {
        $caso = $this->caso($proyecto, $this->requerimiento($proyecto, 'RF-' . substr($codigo, -2)), $tester, $codigo);
        Conexion::pdo()->prepare('UPDATE casos_prueba SET estado = ? WHERE id = ?')->execute([$estado, $caso]);
        if ($evidencia) {
            EvidenciaModelo::crear($caso, 4, null, null, 'https://ejemplo.com', 'Enlace', $tester);
        }
        if ($stopper !== null) {
            $this->incidente($proyecto, $caso, $tester, 0, $stopper);
        }

        return $caso;
    }

    // Casos con evidencia y sin incidente, uno por estado.
    private function casosCon(int $proyecto, int $tester, int ...$estados): void
    {
        foreach ($estados as $i => $estado) {
            $this->casoCon($proyecto, $tester, $estado, true, null, sprintf('SIS-%03d', $i + 1));
        }
    }

    /** @return array<string, array{int, bool, ?int, list<bool>, bool}> */
    public static function criterios(): array
    {
        return [
            'OK con evidencia: Go' => [1, true, null, [true, true, true, true], true],
            'pendiente' => [0, false, null, [false, true, true, true], false],
            'OK sin evidencia' => [1, false, null, [true, false, true, true], false],
            'FAULT sin incidente' => [2, true, null, [true, true, false, true], false],
            'FAULT con incidente no stopper: Go' => [2, true, 0, [true, true, true, true], true],
            'stopper abierto' => [2, true, 1, [true, true, true, false], false],
        ];
    }

    /** @param list<bool> $cumple */
    #[DataProvider('criterios')]
    public function testGoSoloSiCumpleLosCuatroCriterios(int $estado, bool $evidencia, ?int $stopper, array $cumple, bool $go): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);
        $this->casoCon($proyecto, $tester, $estado, $evidencia, $stopper);

        $p = ReporteServicio::avance($this->sesion($tester))['proyectos'][0];

        $this->assertSame([$cumple, $go], [array_column($p['criterios'], 'cumple'), $p['go']]);
    }

    public function testProyectoSinCasosNoEsGo(): void
    {
        $tester = $this->usuario();
        $this->proyecto($tester);

        $p = ReporteServicio::avance($this->sesion($tester))['proyectos'][0];

        $this->assertSame([[true, true, true, true], false], [array_column($p['criterios'], 'cumple'), $p['go']]);
    }

    public function testCriterioQueFallaDiceCuantos(): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);
        $this->casoCon($proyecto, $tester, 0, false, null, 'SIS-001');
        $this->casoCon($proyecto, $tester, 0, false, null, 'SIS-002');

        $p = ReporteServicio::avance($this->sesion($tester))['proyectos'][0];

        $this->assertSame('2 pendiente(s)', $p['criterios'][0]['detalle']);
    }

    /** @return array<string, array{list<int>, int}> */
    public static function aprobaciones(): array
    {
        return [
            '0 ejecutados' => [[0], 0],
            '1 OK de 2' => [[1, 2], 50],
            '2 OK de 3 redondea' => [[1, 1, 2], 67],
        ];
    }

    /** @param list<int> $estados */
    #[DataProvider('aprobaciones')]
    public function testAprobacionEsOkSobreEjecutados(array $estados, int $esperado): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);
        $this->casosCon($proyecto, $tester, ...$estados);

        $total = ReporteServicio::avance($this->sesion($tester))['total'];

        $this->assertSame($esperado, $total['aprobacion']);
    }

    // RF-05
    public function testTesterSoloVeSusProyectos(): void
    {
        $tester = $this->usuario();
        $suyo = $this->proyecto($tester);
        $this->proyecto($this->usuario());

        $avance = ReporteServicio::avance($this->sesion($tester));

        $this->assertSame([$suyo], array_column($avance['proyectos'], 'id'));
    }

    public function testCierreListaLoQueImpideElGo(): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);
        $this->casoCon($proyecto, $tester, 1, false, null, 'SIS-001');
        $this->casoCon($proyecto, $tester, 2, true, null, 'SIS-002');
        $this->casoCon($proyecto, $tester, 2, true, 1, 'SIS-003');

        $cierre = ReporteServicio::cierre($proyecto, $this->sesion($tester));

        $this->assertSame(
            [['SIS-001'], ['SIS-002'], ['SIS-003']],
            [array_column($cierre['casos_sin_evidencia'], 'codigo'), array_column($cierre['casos_fault_sin_incidente'], 'codigo'), array_column($cierre['stoppers_abiertos'], 'caso')],
        );
    }

    public function testCierreDeProyectoInexistenteEsNull(): void
    {
        $tester = $this->usuario();

        $cierre = ReporteServicio::cierre(999999999, $this->sesion($tester));

        $this->assertNull($cierre);
    }

    // BUG-026
    public function testCierreDeProyectoAjenoNoSeEncuentra(): void
    {
        $proyecto = $this->proyecto($this->usuario());
        $tester = $this->usuario();

        $this->expectException(ErrorNoEncontrado::class);

        ReporteServicio::cierre($proyecto, $this->sesion($tester));
    }
}
