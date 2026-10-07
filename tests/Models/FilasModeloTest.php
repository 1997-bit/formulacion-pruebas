<?php

declare(strict_types=1);

namespace Tests\Models;

use App\Config\Conexion;
use App\Models\FilasModelo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// Tablas temporales: no hacen COMMIT implícito y el ROLLBACK las vacía.
#[CoversClass(FilasModelo::class)]
#[Medium]
final class FilasModeloTest extends BaseDatos
{
    protected function setUp(): void
    {
        parent::setUp();
        Conexion::pdo()->exec('CREATE TEMPORARY TABLE IF NOT EXISTS t_insertar (a INT, b VARCHAR(10))');
        Conexion::pdo()->exec('CREATE TEMPORARY TABLE IF NOT EXISTS t_sinc (dueno INT, orden INT, texto VARCHAR(10) NULL, autor INT, PRIMARY KEY (dueno, orden))');
    }

    /** @return list<array<string, mixed>> */
    private function insertadas(): array
    {
        return Conexion::pdo()->query('SELECT a, b FROM t_insertar ORDER BY a')->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    private function sinc(): array
    {
        return Conexion::pdo()->query('SELECT dueno, orden, texto, autor FROM t_sinc ORDER BY dueno, orden')->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Dueño 7 con 3 filas del autor 1 y dueño 8 con 1 fila.
    private function sincInicial(): void
    {
        FilasModelo::insertar('t_sinc', ['dueno', 'orden', 'texto', 'autor'], [[7, 1, 'a', 1], [7, 2, 'b', 1], [7, 3, 'c', 1], [8, 1, 'otro', 1]]);
    }

    // #97
    public function testInsertarSinFilasNoEjecuta(): void
    {
        FilasModelo::insertar('t_insertar', ['a', 'b'], []);

        $this->assertSame([], $this->insertadas());
    }

    public function testInsertarUnaFila(): void
    {
        FilasModelo::insertar('t_insertar', ['a', 'b'], [[1, 'x']]);

        $this->assertSame([['a' => 1, 'b' => 'x']], $this->insertadas());
    }

    public function testInsertarVariasFilas(): void
    {
        FilasModelo::insertar('t_insertar', ['a', 'b'], [[1, 'x'], [2, '0'], [3, 'z']]);

        $this->assertSame([['a' => 1, 'b' => 'x'], ['a' => 2, 'b' => '0'], ['a' => 3, 'b' => 'z']], $this->insertadas());
    }

    // #98: autor marca qué filas se escribieron.
    public function testSincronizarSinCambiosNoEscribe(): void
    {
        $this->sincInicial();

        FilasModelo::sincronizar(
            't_sinc',
            ['dueno' => 7],
            ['orden'],
            [['orden' => 1, 'texto' => 'a'], ['orden' => 2, 'texto' => 'b'], ['orden' => 3, 'texto' => 'c']],
            ['autor' => 2]
        );

        $this->assertSame([1, 1, 1, 1], array_column($this->sinc(), 'autor'));
    }

    public function testSincronizarCambiaQuitaYAgrega(): void
    {
        $this->sincInicial();

        FilasModelo::sincronizar(
            't_sinc',
            ['dueno' => 7],
            ['orden'],
            [['orden' => 1, 'texto' => 'a'], ['orden' => 2, 'texto' => 'B'], ['orden' => 4, 'texto' => '0']],
            ['autor' => 3]
        );

        $this->assertSame([
            ['dueno' => 7, 'orden' => 1, 'texto' => 'a', 'autor' => 1],
            ['dueno' => 7, 'orden' => 2, 'texto' => 'B', 'autor' => 3],
            ['dueno' => 7, 'orden' => 4, 'texto' => '0', 'autor' => 3],
            ['dueno' => 8, 'orden' => 1, 'texto' => 'otro', 'autor' => 1],
        ], $this->sinc());
    }

    public function testSincronizarVacioBorraSoloLasDelDueno(): void
    {
        $this->sincInicial();

        FilasModelo::sincronizar('t_sinc', ['dueno' => 7], ['orden'], []);

        $this->assertSame([['dueno' => 8, 'orden' => 1, 'texto' => 'otro', 'autor' => 1]], $this->sinc());
    }

    // #100
    public function testHuellaIgualSiNoCambianLosDatos(): void
    {
        $this->sincInicial();
        $antes = FilasModelo::huella(['t_sinc'], 'dueno', 7);

        $despues = FilasModelo::huella(['t_sinc'], 'dueno', 7);

        $this->assertSame($antes, $despues);
    }

    public function testHuellaCambiaSiCambianLosDatos(): void
    {
        $this->sincInicial();
        $antes = FilasModelo::huella(['t_sinc'], 'dueno', 7);
        Conexion::pdo()->exec("UPDATE t_sinc SET texto = 'z' WHERE dueno = 7 AND orden = 1");

        $despues = FilasModelo::huella(['t_sinc'], 'dueno', 7);

        $this->assertNotSame($antes, $despues);
    }

    public function testHuellaNoCambiaPorOtroDueno(): void
    {
        $this->sincInicial();
        $antes = FilasModelo::huella(['t_sinc'], 'dueno', 7);
        Conexion::pdo()->exec("UPDATE t_sinc SET texto = 'z' WHERE dueno = 8");

        $despues = FilasModelo::huella(['t_sinc'], 'dueno', 7);

        $this->assertSame($antes, $despues);
    }

    // #108
    public function testContarSoloLosRequerimientosPedidos(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto();
        $pedido = $this->requerimiento($proyecto);
        $otro = $this->requerimiento($this->proyecto());
        FilasModelo::guardar(
            'clases_equivalencia',
            $pedido,
            ['campo', 'clase_valida', 'clases_invalidas', 'valores_representativos', 'resultado_esperado'],
            [self::clase(), self::clase()],
            $usuario
        );
        FilasModelo::guardar(
            'clases_equivalencia',
            $otro,
            ['campo', 'clase_valida', 'clases_invalidas', 'valores_representativos', 'resultado_esperado'],
            [self::clase()],
            $usuario
        );

        $cuenta = FilasModelo::contar('clases_equivalencia', [$pedido]);

        $this->assertSame([$pedido => 2], $cuenta);
    }

    public function testContarSinRequerimientosDevuelveVacio(): void
    {
        $cuenta = FilasModelo::contar('clases_equivalencia', []);

        $this->assertSame([], $cuenta);
    }

    /** @return array<string, string> */
    private static function clase(): array
    {
        return ['campo' => 'c', 'clase_valida' => 'v', 'clases_invalidas' => 'i', 'valores_representativos' => 'r', 'resultado_esperado' => 'e'];
    }
}
