<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Config\Conexion;
use App\Core\ErrorNoEncontrado;
use App\Core\ErrorPermiso;
use App\Core\ErrorValidacion;
use App\Models\FilasModelo;
use App\Services\RequerimientoServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-04, RF-20
#[CoversClass(RequerimientoServicio::class)]
#[Medium]
final class RequerimientoServicioTest extends BaseDatos
{
    /** @return array<string, string> */
    private static function datos(int $proyecto, string $codigo = 'RF-01'): array
    {
        return ['proyecto_id' => (string) $proyecto, 'codigo' => $codigo, 'descripcion' => 'Descripción'];
    }

    private function requerimientos(int $proyecto, int $cuantos): void
    {
        foreach (range(1, $cuantos) as $n) {
            $this->requerimiento($proyecto, sprintf('RF-%02d', $n));
        }
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function codigos(): array
    {
        return [
            'RF-01' => ['RF-01', []],
            'RNF-01' => ['RNF-01', []],
            'minúscula y 3 dígitos' => ['rf-001', []],
            'un dígito' => ['RF-1', ['codigo' => 'Use RF-01 o RNF-01.']],
            'otro prefijo' => ['X-01', ['codigo' => 'Use RF-01 o RNF-01.']],
        ];
    }

    /** @param array<string, string> $esperado */
    #[DataProvider('codigos')]
    public function testFormatoDelCodigo(string $codigo, array $esperado): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);

        $errores = $this->errores(fn () => RequerimientoServicio::registrar(self::datos($proyecto, $codigo), $this->sesion($tester)));

        $this->assertSame($esperado, $errores);
    }

    public function testRnfQuedaComoNoFuncional(): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);

        RequerimientoServicio::registrar(self::datos($proyecto, 'rnf-02'), $this->sesion($tester));

        $this->assertSame(1, $this->contar("SELECT no_funcional FROM requerimientos WHERE proyecto_id = ? AND codigo = 'RNF-02'", $proyecto));
    }

    public function testProyectoAjenoDaError(): void
    {
        $proyecto = $this->proyecto($this->usuario());

        $errores = $this->errores(fn () => RequerimientoServicio::registrar(self::datos($proyecto), $this->sesion($this->usuario())));

        $this->assertSame(['proyecto_id' => 'Valor no válido.'], $errores);
    }

    // #101
    public function testCodigoRepetidoEnElProyectoDaErrorDeCampo(): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);
        $this->requerimiento($proyecto, 'RF-01');

        $errores = $this->errores(fn () => RequerimientoServicio::registrar(self::datos($proyecto), $this->sesion($tester)));

        $this->assertSame(['codigo' => 'Ese código ya existe en el proyecto.'], $errores);
    }

    // RF-21
    public function testTesterSoloListaSusProyectos(): void
    {
        $tester = $this->usuario();
        $suyo = $this->requerimiento($this->proyecto($tester));
        $this->requerimiento($this->proyecto($this->usuario()));

        $lista = RequerimientoServicio::listar($this->sesion($tester));

        $this->assertSame([$suyo], array_map(intval(...), array_column($lista, 'id')));
    }

    public function testPaginaDelTesterCuentaSoloLosSuyos(): void
    {
        $tester = $this->usuario();
        $this->requerimiento($this->proyecto($tester));
        $this->requerimiento($this->proyecto($this->usuario()));

        [$filas, $paginacion] = RequerimientoServicio::pagina($this->sesion($tester), 1);

        $this->assertSame([1, 1], [count($filas), $paginacion->paginas()]);
    }

    // RNF-09
    public function testVeintiunoDejanUnoEnLaPagina2(): void
    {
        $tester = $this->usuario();
        $this->requerimientos($this->proyecto($tester), 21);

        [$filas, $paginacion] = RequerimientoServicio::pagina($this->sesion($tester), 2);

        $this->assertSame([1, 2], [count($filas), $paginacion->paginas()]);
    }

    public function testProyectosDelTesterSonLosSuyos(): void
    {
        $tester = $this->usuario();
        $suyo = $this->proyecto($tester);
        $this->proyecto($this->usuario());

        $proyectos = RequerimientoServicio::proyectos($this->sesion($tester));

        $this->assertSame([$suyo], array_map(intval(...), array_column($proyectos, 'id')));
    }

    public function testVerInexistenteEsNull(): void
    {
        $ver = RequerimientoServicio::ver(999999999, $this->sesion($this->usuario()));

        $this->assertNull($ver);
    }

    // BUG-026
    public function testVerAjenoNoSeEncuentra(): void
    {
        $requerimiento = $this->requerimiento($this->proyecto($this->usuario()));

        $this->expectException(ErrorNoEncontrado::class);

        RequerimientoServicio::ver($requerimiento, $this->sesion($this->usuario()));
    }

    // RF-01 con 2 casos tipo 1, uno FAULT. RF-02 sin casos: 1 de 2 celdas
    public function testMatrizCuentaCasosPorTipoYHuecos(): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);
        $rf1 = $this->requerimiento($proyecto, 'RF-01');
        $this->requerimiento($proyecto, 'RF-02');
        $this->caso($proyecto, $rf1, $tester, 'UNI-001');
        $fault = $this->caso($proyecto, $rf1, $tester, 'UNI-002');
        \App\Config\Conexion::pdo()->prepare('UPDATE casos_prueba SET estado = 2 WHERE id = ?')->execute([$fault]);

        $p = RequerimientoServicio::matriz($this->sesion($tester))[0];

        $this->assertSame(
            [[1], ['casos' => 2, 'fault' => 1], 1, 2, ['RF-02']],
            [$p['tipos'], $p['filas'][0]['celdas'][1], $p['cubiertas'], $p['total'], $p['sin_casos']],
        );
    }

    /** @return array<string, string> */
    private static function cambio(string $codigo = 'RF-01', string $descripcion = 'Nueva', string $motivo = 'Cambió el alcance'): array
    {
        return ['codigo' => $codigo, 'descripcion' => $descripcion, 'motivo' => $motivo];
    }

    private static function huella(int $id): string
    {
        return FilasModelo::huella(['requerimientos'], 'id', $id);
    }

    public function testEditarGuardaCambiosYMotivoEnElHistorial(): void
    {
        $tester = $this->usuario();
        $id = $this->requerimiento($this->proyecto($tester));

        RequerimientoServicio::editar($id, self::cambio('RNF-01'), self::huella($id), $this->sesion($tester));

        $sql = Conexion::pdo()->prepare("SELECT campo, antes, despues FROM logs_cambios WHERE tabla = 'requerimientos' AND registro_id = ? ORDER BY id");
        $sql->execute([$id]);
        $this->assertSame([
            ['campo' => 'codigo', 'antes' => 'RF-01', 'despues' => 'RNF-01'],
            ['campo' => 'descripcion', 'antes' => 'Requerimiento', 'despues' => 'Nueva'],
            ['campo' => 'motivo', 'antes' => null, 'despues' => 'Cambió el alcance'],
        ], $sql->fetchAll(\PDO::FETCH_ASSOC));
        $this->assertSame(1, $this->contar('SELECT no_funcional FROM requerimientos WHERE id = ?', $id));
    }

    public function testEditarSinMotivoDaError(): void
    {
        $tester = $this->usuario();
        $id = $this->requerimiento($this->proyecto($tester));

        $errores = $this->errores(fn () => RequerimientoServicio::editar($id, self::cambio(motivo: ' '), self::huella($id), $this->sesion($tester)));

        $this->assertArrayHasKey('motivo', $errores);
    }

    public function testEditarSinCambiosNoEscribe(): void
    {
        $tester = $this->usuario();
        $id = $this->requerimiento($this->proyecto($tester));

        RequerimientoServicio::editar($id, self::cambio(descripcion: 'Requerimiento'), self::huella($id), $this->sesion($tester));

        $this->assertSame(0, $this->contar("SELECT COUNT(*) FROM logs_cambios WHERE tabla = 'requerimientos' AND registro_id = ?", $id));
    }

    public function testEditarACodigoRepetidoDaErrorDeCampo(): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);
        $this->requerimiento($proyecto, 'RF-01');
        $id = $this->requerimiento($proyecto, 'RF-02');

        $errores = $this->errores(fn () => RequerimientoServicio::editar($id, self::cambio('RF-01'), self::huella($id), $this->sesion($tester)));

        $this->assertSame(['codigo' => 'Ese código ya existe en el proyecto.'], $errores);
    }

    // #100
    public function testEditarConHuellaViejaDaError(): void
    {
        $tester = $this->usuario();
        $id = $this->requerimiento($this->proyecto($tester));

        $errores = $this->errores(fn () => RequerimientoServicio::editar($id, self::cambio(), 'vieja', $this->sesion($tester)));

        $this->assertSame(ErrorValidacion::OTRO_GUARDO, $errores);
    }

    // BUG-026
    public function testEditarAjenoNoSeEncuentra(): void
    {
        $id = $this->requerimiento($this->proyecto($this->usuario()));

        $this->expectException(ErrorNoEncontrado::class);

        RequerimientoServicio::editar($id, self::cambio(), self::huella($id), $this->sesion($this->usuario()));
    }

    // ASVS 16.4.2
    public function testEliminarDejaElHistorialYSuBorrado(): void
    {
        $admin = $this->usuario(1);
        $id = $this->requerimiento($this->proyecto());
        RequerimientoServicio::editar($id, self::cambio(), self::huella($id), $this->sesion($admin));
        $antes = $this->contar("SELECT COUNT(*) FROM logs_cambios WHERE tabla = 'requerimientos' AND registro_id = ?", $id);

        RequerimientoServicio::eliminar($id, $this->sesion($admin));

        $this->assertSame([0, $antes + 1, 1], [
            $this->contar('SELECT COUNT(*) FROM requerimientos WHERE id = ?', $id),
            $this->contar("SELECT COUNT(*) FROM logs_cambios WHERE tabla = 'requerimientos' AND registro_id = ?", $id),
            $this->contar("SELECT COUNT(*) FROM logs_cambios WHERE tabla = 'requerimientos' AND registro_id = ? AND campo = 'eliminado'", $id),
        ]);
    }

    public function testEliminarConCasosDaErrorYNoBorra(): void
    {
        $admin = $this->usuario(1);
        $proyecto = $this->proyecto($admin);
        $id = $this->requerimiento($proyecto);
        $this->caso($proyecto, $id, $admin);

        $errores = $this->errores(fn () => RequerimientoServicio::eliminar($id, $this->sesion($admin)));

        $this->assertSame([['general' => 'Tiene casos o formularios de técnicas: no se puede eliminar.'], 1], [
            $errores, $this->contar('SELECT COUNT(*) FROM requerimientos WHERE id = ?', $id),
        ]);
    }

    public function testEliminarConFormularioDaError(): void
    {
        $admin = $this->usuario(1);
        $id = $this->requerimiento($this->proyecto());
        $columnas = ['campo', 'clase_valida', 'clases_invalidas', 'valores_representativos', 'resultado_esperado'];
        FilasModelo::guardar('clases_equivalencia', $id, $columnas, [array_combine($columnas, ['Edad', '18 a 60', 'Menor de 18', '17, 18', 'Acepta'])], $admin);

        $errores = $this->errores(fn () => RequerimientoServicio::eliminar($id, $this->sesion($admin)));

        $this->assertArrayHasKey('general', $errores);
    }

    public function testTesterNoElimina(): void
    {
        $tester = $this->usuario();
        $id = $this->requerimiento($this->proyecto($tester));

        $this->expectException(ErrorPermiso::class);

        RequerimientoServicio::eliminar($id, $this->sesion($tester));
    }
}
