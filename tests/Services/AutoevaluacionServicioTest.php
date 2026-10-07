<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Core\ErrorNoEncontrado;
use App\Core\ErrorPermiso;
use App\Services\AutoevaluacionServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-15
#[CoversClass(AutoevaluacionServicio::class)]
#[Medium]
final class AutoevaluacionServicioTest extends BaseDatos
{
    /**
     * Auto 4 y co 2 en los 6 aspectos.
     *
     * @return array{evaluado_id: string, auto: array<int, string>, co: array<int, string>, comentario: array<int, string>}
     */
    private static function datos(int $evaluado, string $auto1 = '4', string $comentario1 = ''): array
    {
        return [
            'evaluado_id' => (string) $evaluado,
            'auto' => [1 => $auto1, 2 => '4', 3 => '4', 4 => '4', 5 => '4', 6 => '4'],
            'co' => [1 => '2', 2 => '2', 3 => '2', 4 => '2', 5 => '2', 6 => '2'],
            'comentario' => [1 => $comentario1],
        ];
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function puntos(): array
    {
        return [
            '0' => ['0', ['auto.1' => 'Entre 1 y 5.']],
            '1' => ['1', []],
            '5' => ['5', []],
            '6' => ['6', ['auto.1' => 'Entre 1 y 5.']],
        ];
    }

    /** @param array<string, string> $esperado */
    #[DataProvider('puntos')]
    public function testPuntosEntre1y5(string $valor, array $esperado): void
    {
        $yo = $this->usuario();
        $companero = $this->usuario();
        $proyecto = $this->proyecto($yo, $companero);

        $errores = $this->errores(fn () => AutoevaluacionServicio::guardar($proyecto, self::datos($companero, $valor), $this->sesion($yo)));

        $this->assertSame($esperado, $errores);
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function comentarios(): array
    {
        return [
            '500 caracteres' => [str_repeat('a', 500), []],
            '501 caracteres' => [str_repeat('a', 501), ['comentario.1' => 'Máximo 500 caracteres.']],
        ];
    }

    /** @param array<string, string> $esperado */
    #[DataProvider('comentarios')]
    public function testLargoDelComentario(string $comentario, array $esperado): void
    {
        $yo = $this->usuario();
        $companero = $this->usuario();
        $proyecto = $this->proyecto($yo, $companero);

        $errores = $this->errores(fn () => AutoevaluacionServicio::guardar($proyecto, self::datos($companero, '4', $comentario), $this->sesion($yo)));

        $this->assertSame($esperado, $errores);
    }

    public function testEvaluadoDebeSerCompanero(): void
    {
        $yo = $this->usuario();
        $ajeno = $this->usuario();
        $proyecto = $this->proyecto($yo, $this->usuario());

        $errores = $this->errores(fn () => AutoevaluacionServicio::guardar($proyecto, self::datos($ajeno), $this->sesion($yo)));

        $this->assertSame(['evaluado_id' => 'Elija un compañero del proyecto.'], $errores);
    }

    public function testProyectoDeUnaPersonaNoGuarda(): void
    {
        $yo = $this->usuario();
        $proyecto = $this->proyecto($yo);

        $errores = $this->errores(fn () => AutoevaluacionServicio::guardar($proyecto, self::datos($yo), $this->sesion($yo)));

        $this->assertSame(['evaluado_id' => 'El proyecto no tiene otro miembro que evaluar.'], $errores);
    }

    public function testNoMiembroNoGuarda(): void
    {
        $companero = $this->usuario();
        $proyecto = $this->proyecto($companero, $this->usuario());
        $ajeno = $this->sesion($this->usuario());

        $this->expectException(ErrorPermiso::class);

        AutoevaluacionServicio::guardar($proyecto, self::datos($companero), $ajeno);
    }

    public function testPromediosPorPersona(): void
    {
        $ana = $this->usuario(0, 'Ana');
        $beto = $this->usuario(0, 'Beto');
        $proyecto = $this->proyecto($ana, $beto);

        AutoevaluacionServicio::guardar($proyecto, self::datos($beto, '1'), $this->sesion($ana));

        $this->assertSame([
            ['id' => $ana, 'nombre' => 'Ana', 'auto' => 3.5, 'co' => null],
            ['id' => $beto, 'nombre' => 'Beto', 'auto' => null, 'co' => 2.0],
        ], AutoevaluacionServicio::promedios($proyecto, $this->sesion($ana)));
    }

    public function testPromediosSinDatos(): void
    {
        $ana = $this->usuario(0, 'Ana');
        $proyecto = $this->proyecto($ana);

        $this->assertSame([['id' => $ana, 'nombre' => 'Ana', 'auto' => null, 'co' => null]], AutoevaluacionServicio::promedios($proyecto, $this->sesion($ana)));
    }

    public function testPuedeLlenarElMiembro(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto($usuario);

        $this->assertTrue(AutoevaluacionServicio::puedeLlenar($proyecto, $usuario));
    }

    public function testNoPuedeLlenarElAjeno(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto();

        $this->assertFalse(AutoevaluacionServicio::puedeLlenar($proyecto, $usuario));
    }

    /** @return array<string, array{callable(int, array{id: int, rol: int}): mixed}> */
    public static function lecturas(): array
    {
        return [
            'proyecto' => [fn (int $p, array $u) => AutoevaluacionServicio::proyecto($p, $u)],
            'evaluacion' => [fn (int $p, array $u) => AutoevaluacionServicio::evaluacion($p, $u)],
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

        $proyectos = AutoevaluacionServicio::proyectos($this->sesion($usuario));

        $this->assertSame([$mio], array_column($proyectos, 'id'));
    }
}
