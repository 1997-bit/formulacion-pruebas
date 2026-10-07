<?php

declare(strict_types=1);

namespace Tests\Models;

use App\Models\PlanModelo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// BUG-035
#[CoversClass(PlanModelo::class)]
#[Medium]
final class PlanModeloTest extends BaseDatos
{
    /** @return array<string, array{int, bool}> */
    public static function estados(): array
    {
        return [
            'borrador' => [0, false],
            'aprobado' => [1, false],
            'cerrado' => [2, true],
        ];
    }

    #[DataProvider('estados')]
    public function testCerradoSoloConEstado2(int $estado, bool $esperado): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto($usuario);
        PlanModelo::guardar($proyecto, [
            'version' => '1.0', 'responsable_id' => $usuario, 'fecha' => '2026-10-06', 'alcance' => 'Alcance',
            'objetivos' => 'Objetivos', 'estrategia' => 1, 'recursos' => null, 'cronograma' => null,
            'criterios_aceptacion' => 'Criterios', 'riesgos' => null, 'estado' => $estado,
        ]);

        $cerrado = PlanModelo::cerrado($proyecto);

        $this->assertSame($esperado, $cerrado);
    }

    public function testCerradoSinPlanEsFalse(): void
    {
        $proyecto = $this->proyecto();

        $cerrado = PlanModelo::cerrado($proyecto);

        $this->assertFalse($cerrado);
    }
}
