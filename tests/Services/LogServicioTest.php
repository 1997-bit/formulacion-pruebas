<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Core\ErrorPermiso;
use App\Core\Log;
use App\Services\LogServicio;
use Monolog\Handler\NullHandler;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

// RNF-11. Log de un día viejo, borrado al final
#[CoversClass(LogServicio::class)]
#[Small]
final class LogServicioTest extends TestCase
{
    private const DIA = '2000-01-01';

    private const ADMIN = ['id' => 1, 'rol' => 1];

    private string $archivo;

    protected function setUp(): void
    {
        $this->archivo = RAIZ . '/storage/logs/app-' . self::DIA . '.log';
        $linea = fn (string $canal, int $nivel, string $mensaje, string $uid, array $contexto = [], string $ip = '10.0.0.1'): string => json_encode([
            'message' => $mensaje, 'context' => $contexto, 'level' => $nivel, 'level_name' => 'X', 'channel' => $canal,
            'datetime' => self::DIA . 'T10:00:00-05:00', 'extra' => ['uid' => $uid, 'ip' => $ip, 'user_id' => null],
        ]);
        $lineas = [];
        foreach ([10, 20, 30, 40] as $i => $ms) {
            $lineas[] = $linea('http', 200, 'GET /a 200', "0000000{$i}", ['ruta' => '/a', 'codigo' => 200, 'duracion_ms' => $ms]);
        }
        $lineas[] = $linea('seguridad', 300, 'authn_login_fail:pan', 'aaaaaaa1', ip: '10.0.0.9');
        $lineas[] = $linea('seguridad', 300, 'authn_login_fail:pan', 'aaaaaaa2', ip: '10.0.0.9');
        $lineas[] = $linea('app', 500, 'sys_crash', 'be45d3e3');
        $lineas[] = $linea('http', 200, 'POST /b 500', 'be45d3e3', ['ruta' => '/b', 'codigo' => 500, 'duracion_ms' => 5]);
        file_put_contents($this->archivo, implode("\n", $lineas) . "\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->archivo);
        Log::usar(new NullHandler());
    }

    public function testTesterNoEntra(): void
    {
        $this->expectException(ErrorPermiso::class);

        LogServicio::listar(['id' => 2, 'rol' => 0], [], 1);
    }

    // OWASP Logging Cheat Sheet: leer logs queda registrado
    public function testLeerDejaSensitiveRead(): void
    {
        $log = new TestHandler();
        Log::usar($log);

        LogServicio::listar(self::ADMIN, ['dia' => self::DIA], 1);

        $this->assertTrue($log->hasInfoThatContains('sensitive_read:'));
    }

    // El código de la página 500 lleva a su petición, la más nueva primero
    public function testUidDaLaPeticionEntera(): void
    {
        [$lineas] = LogServicio::listar(self::ADMIN, ['uid' => 'be45d3e3'], 1);

        $this->assertSame(['POST /b 500', 'sys_crash'], array_column($lineas, 'message'));
    }

    public function testFiltraPorCanalYNivel(): void
    {
        [$lineas] = LogServicio::listar(self::ADMIN, ['dia' => self::DIA, 'canal' => 'seguridad', 'nivel' => '300'], 1);

        $this->assertCount(2, $lineas);
    }

    public function testDiaConRutaEsHoy(): void
    {
        $this->assertSame(date('Y-m-d'), LogServicio::filtros(['dia' => '../../.env'])['dia']);
    }

    public function testSeguridadCuentaEventosEIps(): void
    {
        $seguridad = LogServicio::seguridad(self::ADMIN, self::DIA);

        $this->assertSame(['eventos' => ['authn_login_fail' => 2], 'ips' => ['10.0.0.9' => 2]], $seguridad);
    }

    public function testRendimientoDaP50P95YTasa5xx(): void
    {
        $filas = LogServicio::rendimiento(self::ADMIN, self::DIA);

        $this->assertSame([
            ['ruta' => '/a', 'peticiones' => 4, 'p50' => 30.0, 'p95' => 40.0, 'errores' => 0.0],
            ['ruta' => '/b', 'peticiones' => 1, 'p50' => 5.0, 'p95' => 5.0, 'errores' => 100.0],
        ], $filas);
    }
}
