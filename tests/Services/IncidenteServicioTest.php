<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Core\ErrorNoEncontrado;
use App\Models\PlanModelo;
use App\Services\IncidenteServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-17, RF-19
#[CoversClass(IncidenteServicio::class)]
#[Medium]
final class IncidenteServicioTest extends BaseDatos
{
    /**
     * @param array<string, string> $cambios
     * @return array<string, string>
     */
    private static function datos(array $cambios = []): array
    {
        return $cambios + [
            'titulo' => 'Título', 'modulo' => 'Módulo', 'severidad' => '2', 'prioridad' => '2',
            'descripcion' => 'Descripción', 'pasos' => 'Pasos', 'resultado_esperado' => 'Esperado',
            'resultado_obtenido' => 'Obtenido', 'estado' => '0', 'asignado_id' => '', 'es_stopper' => '0',
        ];
    }

    /** @return array{int, int, int} tester, proyecto, caso */
    private function casoDeTester(): array
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);

        return [$tester, $proyecto, $this->caso($proyecto, $this->requerimiento($proyecto), $tester)];
    }

    private function cerrarPlan(int $proyecto, int $responsable): void
    {
        PlanModelo::guardar($proyecto, [
            'version' => '1.0', 'responsable_id' => $responsable, 'fecha' => '2026-10-06', 'alcance' => 'Alcance',
            'objetivos' => 'Objetivos', 'estrategia' => 1, 'recursos' => null, 'cronograma' => null,
            'criterios_aceptacion' => 'Criterios', 'riesgos' => null, 'estado' => 2,
        ]);
    }

    /** @return array<string, array{array<string, string>, array<string, string>}> */
    public static function campos(): array
    {
        return [
            'título 150' => [['titulo' => str_repeat('a', 150)], []],
            'título 151' => [['titulo' => str_repeat('a', 151)], ['titulo' => 'Máximo 150 caracteres.']],
            'módulo 100' => [['modulo' => str_repeat('a', 100)], []],
            'módulo 101' => [['modulo' => str_repeat('a', 101)], ['modulo' => 'Máximo 100 caracteres.']],
        ];
    }

    /**
     * @param array<string, string> $cambios
     * @param array<string, string> $esperado
     */
    #[DataProvider('campos')]
    public function testLargoDeCampos(array $cambios, array $esperado): void
    {
        [$tester, , $caso] = $this->casoDeTester();

        $errores = $this->errores(fn () => IncidenteServicio::registrar($caso, self::datos($cambios), $this->sesion($tester)));

        $this->assertSame($esperado, $errores);
    }

    public function testCodigoEsConsecutivoDelProyecto(): void
    {
        [$tester, , $caso] = $this->casoDeTester();
        IncidenteServicio::registrar($caso, self::datos(), $this->sesion($tester));

        [, $codigo] = IncidenteServicio::registrar($caso, self::datos(), $this->sesion($tester));

        $this->assertSame('BUG-002', $codigo);
    }

    public function testOtroProyectoEmpiezaEnUno(): void
    {
        [$tester, , $caso] = $this->casoDeTester();
        IncidenteServicio::registrar($caso, self::datos(), $this->sesion($tester));
        [$otro, , $otroCaso] = $this->casoDeTester();

        [, $codigo] = IncidenteServicio::registrar($otroCaso, self::datos(), $this->sesion($otro));

        $this->assertSame('BUG-001', $codigo);
    }

    // BUG-026
    public function testCasoAjenoNoSeEncuentra(): void
    {
        [, , $caso] = $this->casoDeTester();

        $this->expectException(ErrorNoEncontrado::class);

        IncidenteServicio::registrar($caso, self::datos(), $this->sesion($this->usuario()));
    }

    /** @return array<string, array{string, string, array<string, string>}> */
    public static function planCerrado(): array
    {
        $error = ['es_stopper' => 'El plan del proyecto está cerrado: reábralo para registrar un stopper.'];

        return [
            'stopper abierto' => ['1', '0', $error],
            'stopper en progreso' => ['1', '1', $error],
            'stopper cerrado' => ['1', '2', []],
            'no stopper' => ['0', '0', []],
        ];
    }

    /** @param array<string, string> $esperado */
    #[DataProvider('planCerrado')]
    public function testStopperAbiertoNoEntraEnPlanCerrado(string $stopper, string $estado, array $esperado): void
    {
        [$tester, $proyecto, $caso] = $this->casoDeTester();
        $this->cerrarPlan($proyecto, $tester);

        $errores = $this->errores(fn () => IncidenteServicio::registrar($caso, self::datos(['es_stopper' => $stopper, 'estado' => $estado]), $this->sesion($tester)));

        $this->assertSame($esperado, $errores);
    }

    public function testSeguimientoCambiaEstadoYDejaHistorial(): void
    {
        [$tester, $proyecto, $caso] = $this->casoDeTester();
        $incidente = $this->incidente($proyecto, $caso, $tester);

        IncidenteServicio::seguimiento($incidente, ['estado' => '1', 'asignado_id' => (string) $tester, 'es_stopper' => '0'], $this->sesion($tester));

        $this->assertSame(
            [1, 2],
            [$this->contar('SELECT estado FROM incidentes WHERE id = ?', $incidente), $this->contar("SELECT COUNT(*) FROM logs_cambios WHERE tabla = 'incidentes' AND registro_id = ?", $incidente)],
        );
    }

    public function testSeguimientoAsignadoNoMiembroDaError(): void
    {
        [$tester, $proyecto, $caso] = $this->casoDeTester();
        $incidente = $this->incidente($proyecto, $caso, $tester);

        $errores = $this->errores(fn () => IncidenteServicio::seguimiento($incidente, ['estado' => '0', 'asignado_id' => (string) $this->usuario(), 'es_stopper' => '0'], $this->sesion($tester)));

        $this->assertSame(['asignado_id' => 'Valor no válido.'], $errores);
    }

    public function testSeguimientoSinAsignado(): void
    {
        [$tester, $proyecto, $caso] = $this->casoDeTester();
        $incidente = $this->incidente($proyecto, $caso, $tester);

        $errores = $this->errores(fn () => IncidenteServicio::seguimiento($incidente, ['estado' => '0', 'asignado_id' => '', 'es_stopper' => '0'], $this->sesion($tester)));

        $this->assertSame([], $errores);
    }

    public function testSeguimientoEstadoFueraDeCatalogoDaError(): void
    {
        [$tester, $proyecto, $caso] = $this->casoDeTester();
        $incidente = $this->incidente($proyecto, $caso, $tester);

        $errores = $this->errores(fn () => IncidenteServicio::seguimiento($incidente, ['estado' => '9', 'asignado_id' => '', 'es_stopper' => '0'], $this->sesion($tester)));

        $this->assertSame(['estado' => 'Valor no válido.'], $errores);
    }

    public function testVerInexistenteEsNull(): void
    {
        $ver = IncidenteServicio::ver(999999999, $this->sesion($this->usuario()));

        $this->assertNull($ver);
    }

    // BUG-026
    public function testVerAjenoNoSeEncuentra(): void
    {
        [$tester, $proyecto, $caso] = $this->casoDeTester();
        $incidente = $this->incidente($proyecto, $caso, $tester);

        $this->expectException(ErrorNoEncontrado::class);

        IncidenteServicio::ver($incidente, $this->sesion($this->usuario()));
    }

    public function testPaginaDelTesterSoloTraeLosSuyos(): void
    {
        [$tester, $proyecto, $caso] = $this->casoDeTester();
        $suyo = $this->incidente($proyecto, $caso, $tester);
        [$otro, $otroProyecto, $otroCaso] = $this->casoDeTester();
        $this->incidente($otroProyecto, $otroCaso, $otro);

        [$filas] = IncidenteServicio::pagina($this->sesion($tester), 1);

        $this->assertSame([$suyo], array_map(intval(...), array_column($filas, 'id')));
    }

    public function testAsignablesSonLosMiembros(): void
    {
        [$tester, $proyecto] = $this->casoDeTester();

        $asignables = IncidenteServicio::asignables(['proyecto_id' => $proyecto]);

        $this->assertSame([$tester], array_map(intval(...), array_column($asignables, 'id')));
    }
}
