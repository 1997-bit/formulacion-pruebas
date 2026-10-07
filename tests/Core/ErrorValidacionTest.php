<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\ErrorValidacion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

// #101
#[CoversClass(ErrorValidacion::class)]
#[Small]
final class ErrorValidacionTest extends TestCase
{
    public function testDuplicadoSeVuelveErrorDelCampo(): void
    {
        $pdo = $this->pdoException(1062);

        $error = ErrorValidacion::siDuplicado($pdo, ['usuario' => 'Ya existe.']);

        $this->assertInstanceOf(ErrorValidacion::class, $error);
        $this->assertSame(['usuario' => 'Ya existe.'], $error->errores);
    }

    public function testOtroCodigoDevuelveLaMismaExcepcion(): void
    {
        $pdo = $this->pdoException(1452);

        $error = ErrorValidacion::siDuplicado($pdo, ['usuario' => 'Ya existe.']);

        $this->assertSame($pdo, $error);
    }

    public function testSinErrorInfoDevuelveLaMismaExcepcion(): void
    {
        $pdo = new \PDOException('sin conexión');

        $error = ErrorValidacion::siDuplicado($pdo, ['usuario' => 'Ya existe.']);

        $this->assertSame($pdo, $error);
    }

    private function pdoException(int $codigo): \PDOException
    {
        $e = new \PDOException('SQL');
        $e->errorInfo = ['23000', $codigo, 'detalle'];

        return $e;
    }
}
