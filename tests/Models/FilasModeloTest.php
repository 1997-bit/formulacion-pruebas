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
        Conexion::pdo()->exec('CREATE TEMPORARY TABLE IF NOT EXISTS t_sinc (dueno INT, orden INT, texto VARCHAR(10) NULL, autor INT, PRIMARY KEY (dueno, orden))');
        Conexion::pdo()->exec('DELETE FROM t_sinc');
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

    /** @return list<array<string, mixed>> */
    private function sinc(): array
    {
        return Conexion::pdo()->query('SELECT dueno, orden, texto, autor FROM t_sinc ORDER BY dueno, orden')->fetchAll(\PDO::FETCH_ASSOC);
    }

    // autor marca qué filas se escribieron en cada guardado.
    public function testSincronizarSoloEscribeLaDiferencia(): void
    {
        $filas = [['orden' => 1, 'texto' => 'a'], ['orden' => 2, 'texto' => 'b'], ['orden' => 3, 'texto' => 'c']];
        FilasModelo::sincronizar('t_sinc', ['dueno' => 7], ['orden'], $filas, ['autor' => 1]);
        FilasModelo::insertar('t_sinc', ['dueno', 'orden', 'texto', 'autor'], [[8, 1, 'otro', 1]]);

        // Sin cambios: nada cambia de autor.
        FilasModelo::sincronizar('t_sinc', ['dueno' => 7], ['orden'], $filas, ['autor' => 2]);
        $this->assertSame([1, 1, 1, 1], array_column($this->sinc(), 'autor'));

        // Cambia la 2, quita la 3, agrega la 4 con un 0. El dueño 8 no se toca.
        FilasModelo::sincronizar('t_sinc', ['dueno' => 7], ['orden'], [
            ['orden' => 1, 'texto' => 'a'], ['orden' => 2, 'texto' => 'B'], ['orden' => 4, 'texto' => '0'],
        ], ['autor' => 3]);
        $this->assertSame([
            ['dueno' => 7, 'orden' => 1, 'texto' => 'a', 'autor' => 1],
            ['dueno' => 7, 'orden' => 2, 'texto' => 'B', 'autor' => 3],
            ['dueno' => 7, 'orden' => 4, 'texto' => '0', 'autor' => 3],
            ['dueno' => 8, 'orden' => 1, 'texto' => 'otro', 'autor' => 1],
        ], $this->sinc());

        // Vacío: borra todas las del dueño.
        FilasModelo::sincronizar('t_sinc', ['dueno' => 7], ['orden'], []);
        $this->assertSame([8], array_column($this->sinc(), 'dueno'));
    }
}
