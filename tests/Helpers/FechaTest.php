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
}
