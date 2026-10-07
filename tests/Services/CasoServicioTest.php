<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Core\ErrorNoEncontrado;
use App\Core\ErrorPermiso;
use App\Core\ErrorValidacion;
use App\Models\CasoModelo;
use App\Models\EvidenciaModelo;
use App\Models\FilasModelo;
use App\Services\CasoServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-04 a RF-07, RF-16, RF-22, RF-24
#[CoversClass(CasoServicio::class)]
#[Medium]
final class CasoServicioTest extends BaseDatos
{
    private const OK = [
        'estado' => '1', 'resultado_obtenido' => 'Obtenido', 'observaciones' => 'Observaciones',
        'evidencia_enlace' => 'https://ejemplo.com', 'descripcion_enlace' => 'Enlace',
    ];

    /**
     * @param array<string, string> $cambios
     * @return array<string, string>
     */
    private static function datos(int $requerimiento, array $cambios = []): array
    {
        return $cambios + [
            'requerimiento_id' => (string) $requerimiento,
            'tipo_prueba' => '3',
            'subtecnica' => '1',
            'modulo' => 'Módulo',
            'plataforma' => '1',
            'entorno' => '',
            'objetivo' => 'Objetivo',
            'precondiciones' => '',
            'entrada' => 'Entrada',
            'pasos' => 'Pasos',
            'resultado_esperado' => 'Resultado',
            'fecha_inicio' => '2026-10-06',
            'fecha_fin' => '2026-10-06',
            'solicitado_por' => '',
            'aprobado_por' => '',
            'estado' => '0',
            'resultado_obtenido' => '',
            'observaciones' => '',
            'evidencia_enlace' => '',
            'descripcion_captura' => '',
            'descripcion_log' => '',
            'descripcion_enlace' => '',
        ];
    }

    /**
     * @param array<string, string> $cambios
     * @param list<string> $marcadas
     * @return array{0: int, 1: string}
     */
    private function registrar(int $usuario, int $requerimiento, array $cambios = [], array $marcadas = []): array
    {
        return CasoServicio::registrar(self::datos($requerimiento, $cambios), $marcadas, [], $this->sesion($usuario));
    }

    private static function huella(int $caso): string
    {
        return FilasModelo::huella(['casos_prueba'], 'id', $caso);
    }

    // Regla 7
    public function testPrimerCodigoEs001(): void
    {
        $usuario = $this->usuario();
        $requerimiento = $this->requerimiento($this->proyecto($usuario));

        [, $codigo] = $this->registrar($usuario, $requerimiento);

        $this->assertSame('SIS-001', $codigo);
    }

    public function testCodigoEsConsecutivoPorProyectoYSigla(): void
    {
        $usuario = $this->usuario();
        $requerimiento = $this->requerimiento($this->proyecto($usuario));
        $this->registrar($usuario, $requerimiento);
        $this->registrar($usuario, $requerimiento, ['tipo_prueba' => '1']);

        [, $codigo] = $this->registrar($usuario, $requerimiento);

        $this->assertSame('SIS-002', $codigo);
    }

    public function testOtroProyectoEmpiezaEn001(): void
    {
        $usuario = $this->usuario();
        $this->registrar($usuario, $this->requerimiento($this->proyecto($usuario)));
        $otro = $this->requerimiento($this->proyecto($usuario));

        [, $codigo] = $this->registrar($usuario, $otro);

        $this->assertSame('SIS-001', $codigo);
    }

    /** @return array<string, array{array<string, string>, array<string, string>}> */
    public static function campos(): array
    {
        return [
            'módulo 100' => [['modulo' => str_repeat('a', 100)], []],
            'módulo 101' => [['modulo' => str_repeat('a', 101)], ['modulo' => 'Máximo 100 caracteres.']],
            'entorno 255' => [['entorno' => str_repeat('a', 255)], []],
            'entorno 256' => [['entorno' => str_repeat('a', 256)], ['entorno' => 'Máximo 255 caracteres.']],
            'fin igual a inicio' => [['fecha_fin' => '2026-10-06'], []],
            'fin antes de inicio' => [['fecha_fin' => '2026-10-05'], ['fecha_fin' => 'No puede ser anterior a la fecha de inicio.']],
        ];
    }

    /**
     * @param array<string, string> $cambios
     * @param array<string, string> $esperado
     */
    #[DataProvider('campos')]
    public function testValidaLosCampos(array $cambios, array $esperado): void
    {
        $usuario = $this->usuario();
        $requerimiento = $this->requerimiento($this->proyecto($usuario));

        $errores = $this->errores(fn () => $this->registrar($usuario, $requerimiento, $cambios));

        $this->assertSame($esperado, $errores);
    }

    public function testRequerimientoAjenoNoVale(): void
    {
        $usuario = $this->usuario();
        $this->proyecto($usuario);
        $ajeno = $this->requerimiento($this->proyecto());

        $errores = $this->errores(fn () => $this->registrar($usuario, $ajeno));

        $this->assertSame(['requerimiento_id' => 'Valor no válido.'], $errores);
    }

    public function testSolicitadoYAprobadoDebenSerMiembros(): void
    {
        $usuario = $this->usuario();
        $requerimiento = $this->requerimiento($this->proyecto($usuario));
        $ajeno = (string) $this->usuario();

        $errores = $this->errores(fn () => $this->registrar($usuario, $requerimiento, ['solicitado_por' => $ajeno, 'aprobado_por' => $ajeno]));

        $this->assertSame(['solicitado_por' => 'No es miembro del proyecto.', 'aprobado_por' => 'No es miembro del proyecto.'], $errores);
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function estadosIniciales(): array
    {
        $faltan = [
            'resultado_obtenido' => 'Es obligatorio con OK o FAULT.',
            'observaciones' => 'Es obligatorio con OK o FAULT.',
            'evidencias' => 'Con OK o FAULT hace falta al menos una.',
        ];

        return [
            'Pendiente sin resultado' => ['0', []],
            'OK sin resultado' => ['1', $faltan],
            'FAULT sin resultado' => ['2', $faltan],
        ];
    }

    /** @param array<string, string> $esperado */
    #[DataProvider('estadosIniciales')]
    public function testEstadoInicial(string $estado, array $esperado): void
    {
        $usuario = $this->usuario();
        $requerimiento = $this->requerimiento($this->proyecto($usuario));

        $errores = $this->errores(fn () => $this->registrar($usuario, $requerimiento, ['estado' => $estado]));

        $this->assertSame($esperado, $errores);
    }

    /** @return array<string, array{list<string>, array<string, string>, array<string, string>}> */
    public static function evidencias(): array
    {
        return [
            'enlace' => [['4'], [], []],
            'captura sin archivo' => [['1'], ['descripcion_captura' => 'Captura'], ['evidencia_captura' => 'Es obligatorio.']],
            'log sin archivo' => [['2'], ['descripcion_log' => 'Log'], ['evidencia_log' => 'Es obligatorio.']],
            'enlace sin http' => [['4'], ['evidencia_enlace' => 'ftp://ejemplo.com'], ['evidencia_enlace' => 'Escriba un enlace que empiece con http:// o https://.']],
            'descripción 255' => [['4'], ['descripcion_enlace' => str_repeat('a', 255)], []],
            'descripción 256' => [['4'], ['descripcion_enlace' => str_repeat('a', 256)], ['descripcion_enlace' => 'Máximo 255 caracteres.']],
            'ninguna con OK' => [[], [], ['evidencias' => 'Con OK o FAULT hace falta al menos una.']],
        ];
    }

    /**
     * @param list<string> $marcadas
     * @param array<string, string> $cambios
     * @param array<string, string> $esperado
     */
    #[DataProvider('evidencias')]
    public function testEvidencias(array $marcadas, array $cambios, array $esperado): void
    {
        $usuario = $this->usuario();
        $requerimiento = $this->requerimiento($this->proyecto($usuario));

        $errores = $this->errores(fn () => $this->registrar($usuario, $requerimiento, $cambios + self::OK, $marcadas));

        $this->assertSame($esperado, $errores);
    }

    public function testRegistrarGuardaElEnlace(): void
    {
        $usuario = $this->usuario();
        $requerimiento = $this->requerimiento($this->proyecto($usuario));

        [$caso] = $this->registrar($usuario, $requerimiento, self::OK, ['4']);

        $this->assertSame(['https://ejemplo.com'], array_column(EvidenciaModelo::deCaso($caso), 'enlace'));
    }

    // RF-06
    public function testAdminEditaCualquierCaso(): void
    {
        $tester = $this->usuario();
        $requerimiento = $this->requerimiento($this->proyecto($tester));
        [$caso] = $this->registrar($tester, $requerimiento);
        $admin = $this->sesion($this->usuario(1));

        CasoServicio::editar($caso, self::datos($requerimiento, ['modulo' => 'Otro']), self::huella($caso), $admin);

        $this->assertSame('Otro', CasoModelo::porId($caso)['modulo'] ?? null);
    }

    public function testTesterNoEditaCasoDeOtro(): void
    {
        $autor = $this->usuario();
        $otro = $this->usuario();
        $requerimiento = $this->requerimiento($this->proyecto($autor, $otro));
        [$caso] = $this->registrar($autor, $requerimiento);

        $this->expectException(ErrorPermiso::class);

        CasoServicio::editar($caso, self::datos($requerimiento), self::huella($caso), $this->sesion($otro));
    }

    // #100
    public function testEditarConHuellaViejaNoGuarda(): void
    {
        $usuario = $this->usuario();
        $requerimiento = $this->requerimiento($this->proyecto($usuario));
        [$caso] = $this->registrar($usuario, $requerimiento);
        $vieja = self::huella($caso);
        CasoServicio::editar($caso, self::datos($requerimiento, ['modulo' => 'Otro']), $vieja, $this->sesion($usuario));

        $errores = $this->errores(fn () => CasoServicio::editar($caso, self::datos($requerimiento), $vieja, $this->sesion($usuario)));

        $this->assertSame(ErrorValidacion::OTRO_GUARDO, $errores);
    }

    // RF-20
    public function testEditarDejaHistorialDeLoCambiado(): void
    {
        $usuario = $this->usuario();
        $requerimiento = $this->requerimiento($this->proyecto($usuario));
        [$caso] = $this->registrar($usuario, $requerimiento);

        CasoServicio::editar($caso, self::datos($requerimiento, ['modulo' => 'Otro']), self::huella($caso), $this->sesion($usuario));

        $this->assertSame(1, $this->contar("SELECT COUNT(*) FROM logs_cambios WHERE tabla = 'casos_prueba' AND registro_id = ?", $caso));
        $this->assertSame(1, $this->contar("SELECT COUNT(*) FROM logs_cambios WHERE tabla = 'casos_prueba' AND registro_id = ? AND campo = 'modulo'", $caso));
    }

    /** @return array<string, array{string, int}> */
    public static function transiciones(): array
    {
        return [
            'Pendiente a OK' => ['1', 1],
            'Pendiente a FAULT' => ['2', 2],
        ];
    }

    // RF-24
    #[DataProvider('transiciones')]
    public function testRegistrarResultadoDesdePendiente(string $estado, int $esperado): void
    {
        $usuario = $this->usuario();
        $requerimiento = $this->requerimiento($this->proyecto($usuario));
        [$caso] = $this->registrar($usuario, $requerimiento);

        CasoServicio::registrarResultado($caso, ['estado' => $estado] + self::OK, ['4'], [], $this->sesion($usuario));

        $this->assertSame($esperado, CasoModelo::porId($caso)['estado'] ?? null);
        $this->assertSame($usuario, CasoModelo::porId($caso)['anotado_por'] ?? null);
    }

    public function testOkVuelveAPendienteSinEvidenciaNueva(): void
    {
        $usuario = $this->usuario();
        $requerimiento = $this->requerimiento($this->proyecto($usuario));
        [$caso] = $this->registrar($usuario, $requerimiento, self::OK, ['4']);

        CasoServicio::registrarResultado($caso, ['estado' => '0', 'resultado_obtenido' => '', 'observaciones' => ''], [], [], $this->sesion($usuario));

        $this->assertSame(0, CasoModelo::porId($caso)['estado'] ?? null);
    }

    // BUG-048
    public function testNoPasaAOkConIncidenteAbierto(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto($usuario);
        [$caso] = $this->registrar($usuario, $this->requerimiento($proyecto));
        $this->incidente($proyecto, $caso, $usuario);

        $errores = $this->errores(fn () => CasoServicio::registrarResultado($caso, self::OK, ['4'], [], $this->sesion($usuario)));

        $this->assertSame(['estado' => 'No pasa a OK: tiene incidentes sin cerrar.'], $errores);
    }

    // RF-07
    public function testAdminEliminaCasoSinEvidenciasNiIncidentes(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto();
        $caso = $this->caso($proyecto, $this->requerimiento($proyecto), $usuario);

        CasoServicio::eliminar($caso, $this->sesion($this->usuario(1)));

        $this->assertNull(CasoModelo::porId($caso));
    }

    public function testNoEliminaCasoConEvidencia(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto();
        $caso = $this->caso($proyecto, $this->requerimiento($proyecto), $usuario);
        EvidenciaModelo::crear($caso, 4, null, null, 'https://ejemplo.com', 'Enlace', $usuario);

        $errores = $this->errores(fn () => CasoServicio::eliminar($caso, $this->sesion($this->usuario(1))));

        $this->assertSame(['general' => 'Tiene evidencias o incidentes: no se puede eliminar.'], $errores);
    }

    public function testNoEliminaCasoConIncidente(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto();
        $caso = $this->caso($proyecto, $this->requerimiento($proyecto), $usuario);
        $this->incidente($proyecto, $caso, $usuario);

        $errores = $this->errores(fn () => CasoServicio::eliminar($caso, $this->sesion($this->usuario(1))));

        $this->assertSame(['general' => 'Tiene evidencias o incidentes: no se puede eliminar.'], $errores);
    }

    public function testTesterNoElimina(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto($usuario);
        $caso = $this->caso($proyecto, $this->requerimiento($proyecto), $usuario);

        $this->expectException(ErrorPermiso::class);

        CasoServicio::eliminar($caso, $this->sesion($usuario));
    }

    /**
     * Tester en dos proyectos. P: SIS-001 (r1, Pendiente), SIS-002 (r2, OK). Q: UNI-001.
     *
     * @return array{usuario: int, p: int, q: int, r2: int}
     */
    private function escenario(): array
    {
        $usuario = $this->usuario();
        $p = $this->proyecto($usuario);
        $q = $this->proyecto($usuario);
        $r1 = $this->requerimiento($p);
        $r2 = $this->requerimiento($p, 'RF-02');
        $this->caso($p, $r1, $usuario, 'SIS-001');
        CasoModelo::anotar($this->caso($p, $r2, $usuario, 'SIS-002'), 1, 'Obtenido', 'Observaciones', $usuario);
        $this->caso($q, $this->requerimiento($q), $usuario, 'UNI-001');

        return ['usuario' => $usuario, 'p' => $p, 'q' => $q, 'r2' => $r2];
    }

    /**
     * @param array<string, string> $filtros
     * @return list<string>
     */
    private function codigos(int $usuario, array $filtros): array
    {
        [$casos] = CasoServicio::listar($this->sesion($usuario), $filtros, 1);

        return array_column($casos, 'codigo');
    }

    // RF-22
    public function testListarSinFiltros(): void
    {
        $e = $this->escenario();

        $codigos = $this->codigos($e['usuario'], []);

        $this->assertSame(['SIS-001', 'SIS-002', 'UNI-001'], $codigos);
    }

    public function testListarPorProyecto(): void
    {
        $e = $this->escenario();

        $codigos = $this->codigos($e['usuario'], ['proyecto' => (string) $e['q']]);

        $this->assertSame(['UNI-001'], $codigos);
    }

    public function testListarPorRequerimiento(): void
    {
        $e = $this->escenario();

        $codigos = $this->codigos($e['usuario'], ['requerimiento' => (string) $e['r2']]);

        $this->assertSame(['SIS-002'], $codigos);
    }

    public function testListarPorEstado(): void
    {
        $e = $this->escenario();

        $codigos = $this->codigos($e['usuario'], ['estado' => '0']);

        $this->assertSame(['SIS-001', 'UNI-001'], $codigos);
    }

    public function testListarConFiltrosCombinados(): void
    {
        $e = $this->escenario();

        $codigos = $this->codigos($e['usuario'], ['proyecto' => (string) $e['p'], 'estado' => '0']);

        $this->assertSame(['SIS-001'], $codigos);
    }

    // RF-21
    public function testListarTesterSoloSusProyectos(): void
    {
        $this->escenario();
        $otro = $this->usuario();
        $suyo = $this->proyecto($otro);
        $this->caso($suyo, $this->requerimiento($suyo), $otro, 'MAN-001');

        $codigos = $this->codigos($otro, []);

        $this->assertSame(['MAN-001'], $codigos);
    }

    public function testListarConProyectoAjenoSaleVacia(): void
    {
        $e = $this->escenario();
        $otro = $this->usuario();

        $codigos = $this->codigos($otro, ['proyecto' => (string) $e['p']]);

        $this->assertSame([], $codigos);
    }

    // RNF-09
    public function testListarDe20EnPagina(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto($usuario);
        $requerimiento = $this->requerimiento($proyecto);
        array_map(fn (int $n) => $this->caso($proyecto, $requerimiento, $usuario, sprintf('SIS-%03d', $n)), range(1, 21));

        [$casos] = CasoServicio::listar($this->sesion($usuario), [], 2);

        $this->assertSame(['SIS-021'], array_column($casos, 'codigo'));
    }

    public function testVerInexistenteEsNull(): void
    {
        $usuario = $this->sesion($this->usuario(1));

        $caso = CasoServicio::ver(0, $usuario);

        $this->assertNull($caso);
    }

    /** @return array<string, array{callable(int, int, array{id: int, rol: int}): mixed}> */
    public static function lecturas(): array
    {
        return [
            'ver' => [fn (int $c, int $e, array $u) => CasoServicio::ver($c, $u)],
            'paraEditar' => [fn (int $c, int $e, array $u) => CasoServicio::paraEditar($c, $u)],
            'evidencia' => [fn (int $c, int $e, array $u) => CasoServicio::evidencia($e, $u)],
        ];
    }

    // BUG-026
    #[DataProvider('lecturas')]
    public function testCasoAjenoEsNoEncontrado(callable $accion): void
    {
        $autor = $this->usuario();
        $proyecto = $this->proyecto($autor);
        $caso = $this->caso($proyecto, $this->requerimiento($proyecto), $autor);
        $evidencia = EvidenciaModelo::crear($caso, 4, null, null, 'https://ejemplo.com', 'Enlace', $autor);
        $ajeno = $this->sesion($this->usuario());

        $this->expectException(ErrorNoEncontrado::class);

        $accion($caso, $evidencia, $ajeno);
    }

    // RF-16
    public function testPortafolioSoloSusProyectosDeLaMasReciente(): void
    {
        $usuario = $this->usuario();
        $mio = $this->proyecto($usuario);
        $ajeno = $this->proyecto();
        $caso = $this->caso($mio, $this->requerimiento($mio), $usuario);
        $otro = $this->caso($ajeno, $this->requerimiento($ajeno), $usuario);
        $e1 = EvidenciaModelo::crear($caso, 4, null, null, 'https://ejemplo.com/1', 'Uno', $usuario);
        $e2 = EvidenciaModelo::crear($caso, 4, null, null, 'https://ejemplo.com/2', 'Dos', $usuario);
        EvidenciaModelo::crear($otro, 4, null, null, 'https://ejemplo.com/3', 'Tres', $usuario);

        [$evidencias] = CasoServicio::portafolio($this->sesion($usuario), 1);

        $this->assertSame([$e2, $e1], array_column($evidencias, 'id'));
    }

    public function testRequerimientosSoloDelProyectoDelCaso(): void
    {
        $usuario = $this->usuario();
        $p = $this->proyecto($usuario);
        $q = $this->proyecto($usuario);
        $r1 = $this->requerimiento($p);
        $r2 = $this->requerimiento($p, 'RF-02');
        $this->requerimiento($q);

        $requerimientos = CasoServicio::requerimientos(['proyecto_id' => $p], $this->sesion($usuario));

        $this->assertSame([$r1, $r2], array_keys($requerimientos));
    }

    public function testMiembrosPorProyectoAgrupa(): void
    {
        $a = $this->usuario(0, 'Ana');
        $b = $this->usuario(0, 'Beto');
        $p = $this->proyecto($a, $b);
        $q = $this->proyecto($b);

        $miembros = CasoServicio::miembrosPorProyecto([['proyecto_id' => $p], ['proyecto_id' => $q], ['proyecto_id' => $p]]);

        $this->assertSame([$p => [$a, $b], $q => [$b]], array_map(fn (array $m) => array_column($m, 'id'), $miembros));
    }
}
