<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Csrf;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

// RNF-02
#[CoversClass(Csrf::class)]
#[Small]
final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testTokenTiene64Hex(): void
    {
        $token = Csrf::token();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function testTokenEsElMismoEnLaMismaSesion(): void
    {
        $primero = Csrf::token();

        $segundo = Csrf::token();

        $this->assertSame($primero, $segundo);
    }

    // #102
    public function testEnvioTiene32Hex(): void
    {
        $envio = Csrf::envio();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $envio);
    }

    public function testEnvioEsDistintoCadaVez(): void
    {
        $primero = Csrf::envio();

        $segundo = Csrf::envio();

        $this->assertNotSame($primero, $segundo);
    }

    public function testTokenIgualEsValido(): void
    {
        $_SESSION['csrf'] = 'abc123';

        $valido = Csrf::valido('abc123');

        $this->assertTrue($valido);
    }

    /** @return array<string, array{?string}> */
    public static function tokensInvalidos(): array
    {
        return [
            'distinto' => ['abc124'],
            'vacío' => [''],
            'null' => [null],
        ];
    }

    #[DataProvider('tokensInvalidos')]
    public function testTokenInvalidoSeRechaza(?string $token): void
    {
        $_SESSION['csrf'] = 'abc123';

        $valido = Csrf::valido($token);

        $this->assertFalse($valido);
    }

    public function testSinTokenEnSesionSeRechaza(): void
    {
        $valido = Csrf::valido('abc123');

        $this->assertFalse($valido);
    }
}
