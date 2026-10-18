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

    // Ajustes: cookie hora. Sin cookie, 24 horas
    public function testHoraSegunAjuste(): void
    {
        $tarde = new \DateTimeImmutable('2026-10-15 14:05:09');

        $this->assertSame('14:05', Fecha::hora($tarde));
        $_COOKIE['hora'] = '12';
        $this->assertSame(['2:05 p. m.', '2:05:09 p. m.', '12:00 a. m.'], [Fecha::hora($tarde), Fecha::hora($tarde, true), Fecha::hora(new \DateTimeImmutable('2026-10-15 00:00'))]);
        unset($_COOKIE['hora']);
    }
}
