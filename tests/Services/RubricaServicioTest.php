<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Core\ErrorPermiso;
use App\Services\RubricaServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-14
#[CoversClass(RubricaServicio::class)]
#[Medium]
final class RubricaServicioTest extends BaseDatos
{
    /** @return array<int, string> criterio => puntos */
    private static function puntos(string $primero = '5'): array
    {
        return [1 => $primero, 2 => '5', 3 => '5', 4 => '5', 5 => '5', 6 => '5'];
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function valores(): array
    {
        return [
            '0' => ['0', ['puntos.1' => 'Entre 1 y 5.']],
            '1' => ['1', []],
            '5' => ['5', []],
            '6' => ['6', ['puntos.1' => 'Entre 1 y 5.']],
        ];
    }

    /** @param array<string, string> $esperado */
    #[DataProvider('valores')]
    public function testPuntosEntre1y5(string $valor, array $esperado): void
    {
        $admin = $this->sesion($this->usuario(1));
        $proyecto = $this->proyecto();

        $errores = $this->errores(fn () => RubricaServicio::guardar($proyecto, self::puntos($valor), $admin));

        $this->assertSame($esperado, $errores);
    }

    // #98
    public function testGuardarSinCambiosNoCambiaElAutor(): void
    {
        $autor = $this->usuario(1);
        $otro = $this->usuario(1);
        $proyecto = $this->proyecto();
        RubricaServicio::guardar($proyecto, self::puntos(), $this->sesion($autor));

        RubricaServicio::guardar($proyecto, self::puntos(), $this->sesion($otro));

        $this->assertSame(6, $this->contar('SELECT COUNT(*) FROM rubrica_evaluaciones WHERE proyecto_id = ? AND evaluado_por = ?', $proyecto, $autor));
    }

    /** @return array<string, array{callable(int, array{id: int, rol: int}): mixed}> */
    public static function acciones(): array
    {
        return [
            'proyectos' => [fn (int $p, array $u) => RubricaServicio::proyectos($u)],
            'proyecto' => [fn (int $p, array $u) => RubricaServicio::proyecto($p, $u)],
            'rubrica' => [fn (int $p, array $u) => RubricaServicio::rubrica($p, $u)],
            'guardar' => [fn (int $p, array $u) => RubricaServicio::guardar($p, self::puntos(), $u)],
        ];
    }

    // RF-21: solo admin, aunque sea miembro.
    #[DataProvider('acciones')]
    public function testTesterNoTienePermiso(callable $accion): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);

        $this->expectException(ErrorPermiso::class);

        $accion($proyecto, $this->sesion($tester));
    }
}
