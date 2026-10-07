<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\CoberturaServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

// RF-12. Mismo redondeo que la pantalla: medio hacia arriba.
#[CoversClass(CoberturaServicio::class)]
#[Small]
final class CoberturaPorcentajeTest extends TestCase
{
    /** @return array<string, array{int, int, int}> */
    public static function casos(): array
    {
        return [
            'nada' => [10, 0, 0],
            'un tercio baja' => [3, 1, 33],
            'medio sube' => [8, 1, 13],
            'todo' => [10, 10, 100],
        ];
    }

    #[DataProvider('casos')]
    public function testPorcentaje(int $total, int $cubiertos, int $esperado): void
    {
        $this->assertSame($esperado, CoberturaServicio::porcentaje($total, $cubiertos));
    }
}
