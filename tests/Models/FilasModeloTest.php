<?php

declare(strict_types=1);

namespace Tests\Models;

use App\Config\Conexion;
use App\Core\Env;
use App\Models\FilasModelo;
use PHPUnit\Framework\TestCase;

// Usa la base del .env con una tabla temporal: no toca los datos.
final class FilasModeloTest extends TestCase
{
    protected function setUp(): void
    {
        Env::cargar(RAIZ . '/.env');
        Conexion::pdo()->exec('CREATE TEMPORARY TABLE IF NOT EXISTS t_insertar (a INT, b VARCHAR(10))');
        Conexion::pdo()->exec('DELETE FROM t_insertar');
    }

    /** @return list<array{a: int, b: string}> */
    private function filas(): array
    {
        return Conexion::pdo()->query('SELECT a, b FROM t_insertar ORDER BY a')->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function testCeroFilasNoEjecuta(): void
    {
        FilasModelo::insertar('t_insertar', ['a', 'b'], []);
        $this->assertSame([], $this->filas());
    }

    public function testUnaFila(): void
    {
        FilasModelo::insertar('t_insertar', ['a', 'b'], [[1, 'x']]);
        $this->assertSame([['a' => 1, 'b' => 'x']], $this->filas());
    }

    public function testVariasFilas(): void
    {
        FilasModelo::insertar('t_insertar', ['a', 'b'], [[1, 'x'], [2, '0'], [3, 'z']]);
        $this->assertSame([['a' => 1, 'b' => 'x'], ['a' => 2, 'b' => '0'], ['a' => 3, 'b' => 'z']], $this->filas());
    }
}
