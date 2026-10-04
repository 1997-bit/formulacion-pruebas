<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Conexion;
use App\Core\ErrorValidacion;
use App\Core\Paginacion;
use App\Core\Validador;
use App\Helpers\Catalogo;
use App\Models\CasoModelo;
use App\Models\EvidenciaModelo;
use App\Models\IncidenteModelo;
use App\Models\RequerimientoModelo;

// RF-04, RF-05, RF-07, RF-16, RF-24
final class CasoServicio
{
    // Tipo de evidencia => sufijo de los campos: evidencia_captura, descripcion_captura…
    public const EVIDENCIAS = [1 => 'captura', 2 => 'log', 4 => 'enlace'];

    /**
     * Formulario 1. El proyecto sale del requerimiento y la técnica de la sub-técnica.
     *
     * @param array<string, string> $datos
     * @param list<string> $marcadas tipos de evidencia marcados
     * @param array<string, array{name: string, tmp_name: string, size: int, error: int}> $archivos por sufijo: captura, log
     * @param array{id: int, rol: int} $usuario
     * @return array{0: int, 1: string} id y código
     */
    public static function registrar(array $datos, array $marcadas, array $archivos, array $usuario): array
    {
        $d = array_map('trim', $datos);
        $requerimientos = array_column(RequerimientoModelo::todos(Permisos::proyectos($usuario)), null, 'id');
        $requerimiento = $requerimientos[$d['requerimiento_id']] ?? null;
        $tipos = self::tipos($marcadas);

        $v = self::validarCaso($d, $requerimiento);
        self::validarResultado($v, $d, $tipos, $archivos, 0);
        $v->comprobar();

        $proyectoId = (int) $requerimiento['proyecto_id'];
        $sigla = Catalogo::valores('tipo_prueba')[(int) $d['tipo_prueba']]['sigla'];
        $anotado = $d['estado'] !== '0';
        $guardados = [];

        // El bloqueo evita dos códigos iguales; UNIQUE (proyecto_id, codigo) lo respalda.
        $pdo = Conexion::pdo();
        $pdo->beginTransaction();
        try {
            $codigo = sprintf('%s-%03d', $sigla, CasoModelo::siguienteNumero($proyectoId, $sigla));
            $casoId = CasoModelo::crear([
                'proyecto_id' => $proyectoId,
                'codigo' => $codigo,
                ...self::campos($d),
                'estado' => (int) $d['estado'],
                'resultado_obtenido' => $d['resultado_obtenido'] ?: null,
                'observaciones' => $d['observaciones'] ?: null,
                'creado_por' => $usuario['id'],
                'anotado_por' => $anotado ? $usuario['id'] : null,
                'anotado_en' => $anotado ? date('Y-m-d H:i:s') : null,
            ]);
            self::guardarEvidencias($casoId, $d, $tipos, $archivos, $usuario['id'], $guardados);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            array_map('unlink', $guardados);
            throw $e;
        }

        return [$casoId, $codigo];
    }

    /**
     * RF-06. El código no cambia aunque cambie el tipo; el requerimiento queda en el mismo proyecto.
     *
     * @param array<string, string> $datos
     * @param array{id: int, rol: int} $usuario
     */
    public static function editar(int $id, array $datos, array $usuario): string
    {
        $caso = self::paraEditar($id, $usuario) ?? throw new \DomainException('Caso no encontrado.');
        $d = array_map('trim', $datos);
        $requerimiento = self::requerimientos($caso, $usuario)[$d['requerimiento_id']] ?? null;
        self::validarCaso($d, $requerimiento)->comprobar();

        $campos = self::campos($d);
        $pdo = Conexion::pdo();
        $pdo->beginTransaction();
        try {
            $antes = CasoModelo::bloquear($id);
            CasoModelo::actualizar($id, $campos);
            Historial::registrar($id, $antes, $campos + ['requerimiento' => $requerimiento['codigo']], $usuario['id']);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $caso['codigo'];
    }

    /**
     * Con 'evidencias'. Null si no existe; ErrorPermiso si no puede editarlo.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array<string, mixed>|null
     */
    public static function paraEditar(int $id, array $usuario): ?array
    {
        $caso = self::ver($id, $usuario);
        if ($caso !== null) {
            Permisos::exigirEditarCaso($usuario, $caso);
        }

        return $caso;
    }

    /**
     * Los del proyecto del caso, por id.
     *
     * @param array<string, mixed> $caso
     * @param array{id: int, rol: int} $usuario
     * @return array<int, array<string, mixed>>
     */
    public static function requerimientos(array $caso, array $usuario): array
    {
        $todos = RequerimientoModelo::todos(Permisos::proyectos($usuario));

        return array_column(array_filter($todos, fn (array $r): bool => $r['proyecto_id'] === $caso['proyecto_id']), null, 'id');
    }

    /**
     * RF-24. Cualquiera del proyecto, aunque no lo haya creado. Puede volver a Pendiente.
     *
     * @param array<string, string> $datos
     * @param list<string> $marcadas
     * @param array<string, array{name: string, tmp_name: string, size: int, error: int}> $archivos
     * @param array{id: int, rol: int} $usuario
     */
    public static function registrarResultado(int $id, array $datos, array $marcadas, array $archivos, array $usuario): string
    {
        $caso = self::ver($id, $usuario) ?? throw new \DomainException('Caso no encontrado.');
        $d = array_map('trim', $datos);
        $tipos = self::tipos($marcadas);

        $v = new Validador();
        self::validarResultado($v, $d, $tipos, $archivos, count($caso['evidencias']));
        $v->comprobar();

        $guardados = [];
        $pdo = Conexion::pdo();
        $pdo->beginTransaction();
        try {
            $antes = CasoModelo::bloquear($id);
            $resultado = [
                'estado' => (int) $d['estado'],
                'resultado_obtenido' => $d['resultado_obtenido'] ?: null,
                'observaciones' => $d['observaciones'] ?: null,
            ];
            CasoModelo::anotar($id, $resultado['estado'], $resultado['resultado_obtenido'], $resultado['observaciones'], $usuario['id']);
            Historial::registrar($id, $antes, $resultado, $usuario['id']);
            self::guardarEvidencias($id, $d, $tipos, $archivos, $usuario['id'], $guardados);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            array_map('unlink', $guardados);
            throw $e;
        }

        return $caso['codigo'];
    }

    /** @param array{id: int, rol: int} $usuario */
    public static function eliminar(int $id, array $usuario): void
    {
        Permisos::exigirAdmin($usuario);
        if (!CasoModelo::eliminar($id)) {
            throw new ErrorValidacion(['general' => 'Tiene evidencias o incidentes: no se puede eliminar.']);
        }
    }

    /**
     * Con 'evidencias' e 'incidentes'. Null si no existe.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array<string, mixed>|null
     */
    public static function ver(int $id, array $usuario): ?array
    {
        $caso = CasoModelo::porId($id);
        if ($caso === null) {
            return null;
        }
        Permisos::exigirMiembro($usuario, (int) $caso['proyecto_id']);

        return $caso + ['evidencias' => EvidenciaModelo::deCaso($id), 'incidentes' => IncidenteModelo::deCaso($id)];
    }

    /**
     * Null si no existe.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array<string, mixed>|null
     */
    public static function evidencia(int $id, array $usuario): ?array
    {
        $evidencia = EvidenciaModelo::porId($id);
        if ($evidencia !== null) {
            Permisos::exigirMiembro($usuario, (int) $evidencia['proyecto_id']);
        }

        return $evidencia;
    }

    /**
     * RF-16, RNF-09. Las evidencias de los proyectos del usuario.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array{0: list<array<string, mixed>>, 1: Paginacion}
     */
    public static function portafolio(array $usuario, int $pagina): array
    {
        $proyectos = Permisos::proyectos($usuario);
        $paginacion = new Paginacion(EvidenciaModelo::contar($proyectos), $pagina);

        return [EvidenciaModelo::portafolio($proyectos, $paginacion->offset()), $paginacion];
    }

    /**
     * RF-22, RNF-09. Un filtro vacío no filtra; con uno de otro proyecto sale vacía.
     *
     * @param array<string, string> $filtros proyecto, requerimiento, estado
     * @param array{id: int, rol: int} $usuario
     * @return array{0: list<array<string, mixed>>, 1: Paginacion}
     */
    public static function listar(array $usuario, array $filtros, int $pagina): array
    {
        $proyectos = Permisos::proyectos($usuario);
        $f = self::filtros($filtros);
        $paginacion = new Paginacion(CasoModelo::contar($proyectos, $f), $pagina);

        return [CasoModelo::listar($proyectos, $f, $paginacion->offset()), $paginacion];
    }

    /**
     * Formulario 1 sin el resultado.
     *
     * @param array<string, string> $d
     * @param array<string, mixed>|null $requerimiento null si no es válido
     */
    private static function validarCaso(array $d, ?array $requerimiento): Validador
    {
        return (new Validador())
            ->requerido('requerimiento_id', $d['requerimiento_id'])
            ->regla('requerimiento_id', $d['requerimiento_id'] === '' || $requerimiento !== null, 'Valor no válido.')
            ->requerido('tipo_prueba', $d['tipo_prueba'])
            ->catalogo('tipo_prueba', 'tipo_prueba', $d['tipo_prueba'])
            ->requerido('subtecnica', $d['subtecnica'])
            ->catalogo('subtecnica', 'subtecnica', $d['subtecnica'])
            ->requerido('modulo', $d['modulo'])
            ->regla('modulo', mb_strlen($d['modulo']) <= 100, 'Máximo 100 caracteres.')
            ->requerido('plataforma', $d['plataforma'])
            ->catalogo('plataforma', 'plataforma', $d['plataforma'])
            ->regla('entorno', mb_strlen($d['entorno']) <= 255, 'Máximo 255 caracteres.')
            ->requerido('objetivo', $d['objetivo'])
            ->requerido('entrada', $d['entrada'])
            ->requerido('pasos', $d['pasos'])
            ->requerido('resultado_esperado', $d['resultado_esperado'])
            ->requerido('fecha_inicio', $d['fecha_inicio'])
            ->fecha('fecha_inicio', $d['fecha_inicio'])
            ->requerido('fecha_fin', $d['fecha_fin'])
            ->fecha('fecha_fin', $d['fecha_fin'])
            ->regla('fecha_fin', $d['fecha_fin'] >= $d['fecha_inicio'], 'No puede ser anterior a la fecha de inicio.');
    }

    /**
     * Columnas del formulario 1, sin el resultado.
     *
     * @param array<string, string> $d
     * @return array<string, mixed>
     */
    private static function campos(array $d): array
    {
        return [
            'requerimiento_id' => (int) $d['requerimiento_id'],
            'tipo_prueba' => (int) $d['tipo_prueba'],
            'subtecnica' => (int) $d['subtecnica'],
            'modulo' => $d['modulo'],
            'plataforma' => (int) $d['plataforma'],
            'entorno' => $d['entorno'] ?: null,
            'objetivo' => $d['objetivo'],
            'precondiciones' => $d['precondiciones'] ?: null,
            'entrada' => $d['entrada'],
            'pasos' => $d['pasos'],
            'resultado_esperado' => $d['resultado_esperado'],
            'fecha_inicio' => $d['fecha_inicio'],
            'fecha_fin' => $d['fecha_fin'],
        ];
    }

    /**
     * @param array<string, string> $filtros
     * @return array{proyecto: ?int, requerimiento: ?int, estado: ?int}
     */
    private static function filtros(array $filtros): array
    {
        $numero = fn (string $clave): ?int => ctype_digit($filtros[$clave] ?? '') ? (int) $filtros[$clave] : null;
        $estado = $numero('estado');

        return [
            'proyecto' => $numero('proyecto'),
            'requerimiento' => $numero('requerimiento'),
            'estado' => Catalogo::existe('estado_caso', $estado) ? $estado : null,
        ];
    }

    /**
     * @param list<string> $marcadas
     * @return list<int>
     */
    private static function tipos(array $marcadas): array
    {
        return array_values(array_intersect(array_keys(self::EVIDENCIAS), array_map('intval', $marcadas)));
    }

    /**
     * RF-24: con OK o FAULT lleva resultado, observaciones y al menos una evidencia, nueva o de antes.
     *
     * @param array<string, string> $d
     * @param list<int> $tipos
     * @param array<string, array{name: string, tmp_name: string, size: int, error: int}> $archivos
     */
    private static function validarResultado(Validador $v, array $d, array $tipos, array $archivos, int $previas): void
    {
        $anotado = in_array($d['estado'], ['1', '2'], true);
        $v->requerido('estado', $d['estado'])
            ->catalogo('estado', 'estado_caso', $d['estado'])
            ->regla('resultado_obtenido', !$anotado || $d['resultado_obtenido'] !== '', 'Es obligatorio con OK o FAULT.')
            ->regla('observaciones', !$anotado || $d['observaciones'] !== '', 'Es obligatorio con OK o FAULT.')
            ->regla('evidencias', !$anotado || $tipos !== [] || $previas > 0, 'Con OK o FAULT hace falta al menos una.');

        foreach ($tipos as $tipo) {
            $nombre = self::EVIDENCIAS[$tipo];
            $campo = 'evidencia_' . $nombre;
            if ($tipo === 4) {
                $v->requerido($campo, $d[$campo])
                    ->regla($campo, $d[$campo] === '' || (filter_var($d[$campo], FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $d[$campo])), 'Escriba un enlace que empiece con http:// o https://.')
                    ->regla($campo, mb_strlen($d[$campo]) <= 500, 'Máximo 500 caracteres.');
            } else {
                $error = Subida::error($archivos[$nombre] ?? null, $tipo);
                $v->regla($campo, $error === null, (string) $error);
            }
            $v->requerido('descripcion_' . $nombre, $d['descripcion_' . $nombre])
                ->regla('descripcion_' . $nombre, mb_strlen($d['descripcion_' . $nombre]) <= 255, 'Máximo 255 caracteres.');
        }
    }

    /**
     * Dentro de la transacción. $guardados junta las rutas para borrarlas si falla.
     *
     * @param array<string, string> $d
     * @param list<int> $tipos
     * @param array<string, array{name: string, tmp_name: string, size: int, error: int}> $archivos
     * @param list<string> $guardados
     */
    private static function guardarEvidencias(int $casoId, array $d, array $tipos, array $archivos, int $usuarioId, array &$guardados): void
    {
        foreach ($tipos as $tipo) {
            $nombre = self::EVIDENCIAS[$tipo];
            $descripcion = $d['descripcion_' . $nombre];
            if ($tipo === 4) {
                EvidenciaModelo::crear($casoId, $tipo, null, null, $d['evidencia_enlace'], $descripcion, $usuarioId);
                continue;
            }
            $archivo = $archivos[$nombre];
            $guardado = Subida::guardar($archivo);
            $guardados[] = Subida::ruta($guardado);
            EvidenciaModelo::crear($casoId, $tipo, $guardado, mb_substr($archivo['name'], 0, 255), null, $descripcion, $usuarioId);
        }
    }
}
