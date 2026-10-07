<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Core\ErrorNoEncontrado;
use App\Services\PlanServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-13
#[CoversClass(PlanServicio::class)]
#[Medium]
final class PlanServicioTest extends BaseDatos
{
    /**
     * @param array<string, string> $cambios
     * @return array<string, string>
     */
    private static function datos(int $responsable, array $cambios = []): array
    {
        return $cambios + [
            'version' => '1.0',
            'responsable_id' => (string) $responsable,
            'fecha' => '2026-10-06',
            'alcance' => 'Alcance',
            'objetivos' => 'Objetivos',
            'estrategia' => '1',
            'recursos' => '',
            'cronograma' => '',
            'criterios_aceptacion' => 'Criterios',
            'riesgos' => '',
            'estado' => '0',
        ];
    }

    /** @return array<string, array{array<string, string>, array<string, string>}> */
    public static function largos(): array
    {
        return [
            'versión 20' => [['version' => str_repeat('1', 20)], []],
            'versión 21' => [['version' => str_repeat('1', 21)], ['version' => 'Máximo 20 caracteres.']],
            'alcance 5000' => [['alcance' => str_repeat('a', 5000)], []],
            'alcance 5001' => [['alcance' => str_repeat('a', 5001)], ['alcance' => 'Máximo 5000 caracteres.']],
        ];
    }

    /**
     * @param array<string, string> $cambios
     * @param array<string, string> $esperado
     */
    #[DataProvider('largos')]
    public function testLargoDeLosCampos(array $cambios, array $esperado): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto($usuario);

        $errores = $this->errores(fn () => PlanServicio::guardar($proyecto, self::datos($usuario, $cambios), $this->sesion($usuario)));

        $this->assertSame($esperado, $errores);
    }

    public function testResponsableDebeSerMiembro(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto($usuario);
        $ajeno = $this->usuario();

        $errores = $this->errores(fn () => PlanServicio::guardar($proyecto, self::datos($ajeno), $this->sesion($usuario)));

        $this->assertSame(['responsable_id' => 'Elija un miembro del proyecto.'], $errores);
    }

    public function testCrearNoDejaHistorial(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto($usuario);

        PlanServicio::guardar($proyecto, self::datos($usuario), $this->sesion($usuario));

        $this->assertSame(0, $this->contar("SELECT COUNT(*) FROM logs_cambios WHERE tabla = 'plan_pruebas' AND registro_id = ?", $proyecto));
    }

    // #115
    public function testEditarDejaHistorialDeLoCambiado(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto($usuario);
        PlanServicio::guardar($proyecto, self::datos($usuario), $this->sesion($usuario));

        PlanServicio::guardar($proyecto, self::datos($usuario, ['version' => '2.0']), $this->sesion($usuario));

        $this->assertSame(1, $this->contar("SELECT COUNT(*) FROM logs_cambios WHERE tabla = 'plan_pruebas' AND registro_id = ? AND campo = 'version'", $proyecto));
    }

    /** @return array<string, array{callable(int, array{id: int, rol: int}): mixed}> */
    public static function lecturas(): array
    {
        return [
            'proyecto' => [fn (int $p, array $u) => PlanServicio::proyecto($p, $u)],
            'plan' => [fn (int $p, array $u) => PlanServicio::plan($p, $u)],
        ];
    }

    // BUG-026
    #[DataProvider('lecturas')]
    public function testProyectoAjenoEsNoEncontrado(callable $accion): void
    {
        $proyecto = $this->proyecto();
        $tester = $this->sesion($this->usuario());

        $this->expectException(ErrorNoEncontrado::class);

        $accion($proyecto, $tester);
    }

    public function testProyectosSoloLosDelTester(): void
    {
        $usuario = $this->usuario();
        $mio = $this->proyecto($usuario);
        $this->proyecto();

        $proyectos = PlanServicio::proyectos($this->sesion($usuario));

        $this->assertSame([$mio], array_column($proyectos, 'id'));
    }
}
