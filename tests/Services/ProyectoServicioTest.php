<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Config\Conexion;
use App\Core\ErrorPermiso;
use App\Services\ProyectoServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-21
#[CoversClass(ProyectoServicio::class)]
#[Medium]
final class ProyectoServicioTest extends BaseDatos
{
    /**
     * @param list<string> $miembros
     * @return array{nombre: string, descripcion: string, miembros: list<string>}
     */
    private static function datos(string $nombre, array $miembros = []): array
    {
        return ['nombre' => $nombre, 'descripcion' => '', 'miembros' => $miembros];
    }

    /** @return list<int> */
    private function miembros(int $proyecto): array
    {
        $sql = Conexion::pdo()->prepare('SELECT usuario_id FROM proyecto_miembros WHERE proyecto_id = ? ORDER BY usuario_id');
        $sql->execute([$proyecto]);

        return array_map(intval(...), $sql->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** @return array<string, array{int, array<string, string>}> */
    public static function nombres(): array
    {
        return [
            'nombre 100' => [100, []],
            'nombre 101' => [101, ['nombre' => 'Máximo 100 caracteres.']],
        ];
    }

    /** @param array<string, string> $esperado */
    #[DataProvider('nombres')]
    public function testNombreHasta100(int $largo, array $esperado): void
    {
        $admin = $this->sesion($this->usuario(1));
        $nombre = substr(uniqid('', true) . str_repeat('a', 101), 0, $largo);

        $errores = $this->errores(fn () => ProyectoServicio::guardar(null, self::datos($nombre), $admin));

        $this->assertSame($esperado, $errores);
    }

    // Miembros: solo testers.
    public function testMiembroQueNoEsTesterDaError(): void
    {
        $admin = $this->sesion($this->usuario(1));

        $errores = $this->errores(fn () => ProyectoServicio::guardar(null, self::datos(uniqid('p', true), [(string) $this->usuario(1)]), $admin));

        $this->assertSame(['miembros' => 'Valor no válido.'], $errores);
    }

    public function testCrearGuardaLosMiembros(): void
    {
        $admin = $this->sesion($this->usuario(1));
        $tester = $this->usuario();
        $nombre = uniqid('p', true);

        ProyectoServicio::guardar(null, self::datos($nombre, [(string) $tester]), $admin);

        $proyecto = $this->contar('SELECT id FROM proyectos WHERE nombre = ?', $nombre);
        $this->assertSame([$tester], $this->miembros($proyecto));
    }

    public function testEditarCambiaElNombre(): void
    {
        $admin = $this->sesion($this->usuario(1));
        $proyecto = $this->proyecto();
        $nombre = uniqid('nuevo', true);

        ProyectoServicio::guardar($proyecto, self::datos($nombre), $admin);

        $this->assertSame($nombre, ProyectoServicio::ver($proyecto, $admin)['nombre']);
    }

    // BUG-007: solo sale quien se quitó, con su autoevaluación.
    public function testQuitarMiembroSoloSacaAEseYSusEvaluaciones(): void
    {
        $admin = $this->sesion($this->usuario(1));
        [$queda, $sale] = [$this->usuario(), $this->usuario()];
        $proyecto = $this->proyecto($queda, $sale);
        $sql = Conexion::pdo()->prepare('INSERT INTO autoevaluaciones (proyecto_id, evaluador_id, evaluado_id, aspecto, puntos) VALUES (?, ?, ?, 1, 3)');
        $sql->execute([$proyecto, $queda, $queda]);
        $sql->execute([$proyecto, $sale, $queda]);

        ProyectoServicio::guardar($proyecto, self::datos(uniqid('p', true), [(string) $queda]), $admin);

        $this->assertSame(
            [[$queda], 1],
            [$this->miembros($proyecto), $this->contar('SELECT COUNT(*) FROM autoevaluaciones WHERE proyecto_id = ?', $proyecto)],
        );
    }

    public function testEliminarConRequerimientosDaError(): void
    {
        $proyecto = $this->proyecto();
        $this->requerimiento($proyecto);

        $errores = $this->errores(fn () => ProyectoServicio::eliminar($proyecto, $this->sesion($this->usuario(1))));

        $this->assertSame(['general' => 'Tiene requerimientos o casos: no se puede eliminar.'], $errores);
    }

    public function testEliminarVacio(): void
    {
        $proyecto = $this->proyecto();

        ProyectoServicio::eliminar($proyecto, $this->sesion($this->usuario(1)));

        $this->assertSame(0, $this->contar('SELECT COUNT(*) FROM proyectos WHERE id = ?', $proyecto));
    }

    /** @return array<string, array{string}> */
    public static function acciones(): array
    {
        return ['listar' => ['listar'], 'ver' => ['ver'], 'testers' => ['testers'], 'guardar' => ['guardar'], 'eliminar' => ['eliminar']];
    }

    #[DataProvider('acciones')]
    public function testSoloAdmin(string $accion): void
    {
        $tester = $this->sesion($this->usuario());
        $proyecto = $this->proyecto();
        $llamadas = [
            'listar' => fn () => ProyectoServicio::listar($tester),
            'ver' => fn () => ProyectoServicio::ver($proyecto, $tester),
            'testers' => fn () => ProyectoServicio::testers($tester),
            'guardar' => fn () => ProyectoServicio::guardar(null, self::datos(uniqid('p', true)), $tester),
            'eliminar' => fn () => ProyectoServicio::eliminar($proyecto, $tester),
        ];

        $this->expectException(ErrorPermiso::class);

        $llamadas[$accion]();
    }
}
