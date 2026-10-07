<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\TablaDecisionServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

// RF-11. Agregar o quitar filas sin guardar.
#[CoversClass(TablaDecisionServicio::class)]
#[Small]
final class TablaDecisionCambiarTest extends TestCase
{
    /** @return array{condiciones: list<array{texto: string, reglas: list<string>}>, acciones: list<array{texto: string, reglas: list<string>}>} */
    private static function tabla(): array
    {
        return [
            'condiciones' => [
                ['texto' => 'a', 'reglas' => ['V', 'V', 'F', 'F']],
                ['texto' => 'b', 'reglas' => ['V', 'F', 'V', 'F']],
            ],
            'acciones' => [
                ['texto' => 'x', 'reglas' => ['X', '-', '-', 'X']],
            ],
        ];
    }

    public function testAgregarCondicionDuplicaLasReglasYDesmarcaAcciones(): void
    {
        $tabla = TablaDecisionServicio::cambiar(self::tabla(), 'condicion');

        $this->assertSame([
            'condiciones' => [
                ['texto' => 'a', 'reglas' => ['V', 'V', 'V', 'V', 'F', 'F', 'F', 'F']],
                ['texto' => 'b', 'reglas' => ['V', 'V', 'F', 'F', 'V', 'V', 'F', 'F']],
                ['texto' => '', 'reglas' => ['V', 'F', 'V', 'F', 'V', 'F', 'V', 'F']],
            ],
            'acciones' => [
                ['texto' => 'x', 'reglas' => ['-', '-', '-', '-', '-', '-', '-', '-']],
            ],
        ], $tabla);
    }

    public function testNoPasaDelMaximoDeCondiciones(): void
    {
        $tabla = self::tabla();
        $tabla['condiciones'] = array_fill(0, TablaDecisionServicio::MAX_CONDICIONES, ['texto' => 'a', 'reglas' => array_fill(0, 16, 'V')]);

        $this->assertSame($tabla, TablaDecisionServicio::cambiar($tabla, 'condicion'));
    }

    public function testNoQuitaLaUltimaCondicion(): void
    {
        $tabla = ['condiciones' => [['texto' => 'a', 'reglas' => ['V', 'F']]], 'acciones' => [['texto' => 'x', 'reglas' => ['X', 'X']]]];

        $this->assertSame($tabla, TablaDecisionServicio::cambiar($tabla, 'quitar-condicion-0'));
    }
}
