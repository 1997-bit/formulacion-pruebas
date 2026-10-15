<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Helpers\Fecha;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(Fecha::class)]
#[Small]
final class FechaTest extends TestCase
{
    // RNF-06
    public function testLegibleEsDiaMesAnio(): void
    {
        $texto = Fecha::legible(new \DateTimeImmutable('2026-05-15 13:45:00'));

        $this->assertSame('15/05/2026', $texto);
    }

    /** @return array<string, array{string, string}> */
    public static function relativas(): array
    {
        return [
            'segundos' => ['2026-10-15 09:59:30', 'hace un momento'],
            'minutos' => ['2026-10-15 09:35:00', 'hace 25 min'],
            'horas hoy' => ['2026-10-15 07:00:00', 'hace 3 h'],
            'ayer' => ['2026-10-14 23:00:00', 'ayer'],
            'días' => ['2026-10-12 10:00:00', 'hace 3 d'],
            'una semana: fecha' => ['2026-10-08 10:00:00', '08/10/2026'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('relativas')]
    public function testRelativaDiceHaceCuanto(string $fecha, string $esperado): void
    {
        $this->assertSame($esperado, Fecha::relativa(new \DateTimeImmutable($fecha), new \DateTimeImmutable('2026-10-15 10:00:00')));
    }
}
