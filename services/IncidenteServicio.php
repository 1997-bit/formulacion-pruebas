<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Conexion;
use App\Core\ErrorNoEncontrado;
use App\Core\ErrorValidacion;
use App\Core\Paginacion;
use App\Core\Validador;
use App\Helpers\Catalogo;
use App\Models\IncidenteModelo;
use App\Models\PlanModelo;
use App\Models\UsuarioModelo;

// RF-17, RF-19
final class IncidenteServicio
{
    /**
     * Formulario 10. Proyecto del caso. Código: BUG + consecutivo del proyecto.
     *
     * @param array<string, string> $datos
     * @param array{id: int, rol: int} $usuario
     * @return array{0: int, 1: string} id y código
     */
    public static function registrar(int $casoId, array $datos, array $usuario): array
    {
        $caso = CasoServicio::ver($casoId, $usuario) ?? throw new ErrorNoEncontrado();
        $d = array_map('trim', $datos);
        $asignables = array_column(self::asignables($caso), 'id');

        $v = (new Validador())
            ->requerido('titulo', $d['titulo'])
            ->regla('titulo', mb_strlen($d['titulo']) <= 150, 'Máximo 150 caracteres.')
            ->requerido('modulo', $d['modulo'])
            ->regla('modulo', mb_strlen($d['modulo']) <= 100, 'Máximo 100 caracteres.')
            ->requerido('severidad', $d['severidad'])
            ->catalogo('severidad', 'severidad', $d['severidad'])
            ->requerido('prioridad', $d['prioridad'])
            ->catalogo('prioridad', 'prioridad', $d['prioridad'])
            ->requerido('descripcion', $d['descripcion'])
            ->requerido('pasos', $d['pasos'])
            ->requerido('resultado_esperado', $d['resultado_esperado'])
            ->requerido('resultado_obtenido', $d['resultado_obtenido']);
        self::validarSeguimiento($v, $d, $asignables);
        $v->comprobar();

        // Lock: sin códigos duplicados. Respaldo: UNIQUE proyecto_id + codigo.
        return Conexion::transaccion(function () use ($caso, $casoId, $d, $usuario): array {
            self::exigirPlanAbierto($caso['proyecto_id'], $d);
            $codigo = sprintf('BUG-%03d', IncidenteModelo::siguienteNumero($caso['proyecto_id']));
            $id = IncidenteModelo::crear([
                'proyecto_id' => $caso['proyecto_id'],
                'caso_id' => $casoId,
                'codigo' => $codigo,
                'titulo' => $d['titulo'],
                'modulo' => $d['modulo'],
                'descripcion' => $d['descripcion'],
                'pasos' => $d['pasos'],
                'resultado_esperado' => $d['resultado_esperado'],
                'resultado_obtenido' => $d['resultado_obtenido'],
                'severidad' => (int) $d['severidad'],
                'prioridad' => (int) $d['prioridad'],
                'estado' => (int) $d['estado'],
                'es_stopper' => (int) ($d['es_stopper'] === '1'),
                'asignado_id' => $d['asignado_id'] === '' ? null : (int) $d['asignado_id'],
                'creado_por' => $usuario['id'],
            ]);

            return [$id, $codigo];
        });
    }

    /**
     * Estado, asignado y stopper. Cualquiera del proyecto.
     *
     * @param array<string, string> $datos
     * @param array{id: int, rol: int} $usuario
     */
    public static function seguimiento(int $id, array $datos, array $usuario): string
    {
        $incidente = self::ver($id, $usuario) ?? throw new ErrorNoEncontrado();
        $d = array_map('trim', $datos);
        $v = new Validador();
        self::validarSeguimiento($v, $d, array_column(self::asignables($incidente), 'id'));
        $v->comprobar();
        self::exigirPlanAbierto($incidente['proyecto_id'], $d);

        $despues = [
            'estado' => (int) $d['estado'],
            'asignado_id' => $d['asignado_id'] === '' ? null : (int) $d['asignado_id'],
            'es_stopper' => (int) ($d['es_stopper'] === '1'),
        ];
        Conexion::transaccion(function () use ($id, $incidente, $despues, $usuario): void {
            IncidenteModelo::actualizar($id, $despues['estado'], $despues['asignado_id'], $despues['es_stopper']);
            Historial::registrar('incidentes', $id, $incidente, $despues, $usuario['id']);
        });

        return $incidente['codigo'];
    }

    /**
     * Tablero: cambia solo el estado.
     *
     * @param array{id: int, rol: int} $usuario
     */
    public static function mover(int $id, int $estado, array $usuario): string
    {
        $incidente = self::ver($id, $usuario) ?? throw new ErrorNoEncontrado();

        return self::seguimiento($id, [
            'estado' => (string) $estado,
            'asignado_id' => (string) $incidente['asignado_id'],
            'es_stopper' => (string) $incidente['es_stopper'],
        ], $usuario);
    }

    /**
     * Null si no existe.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array<string, mixed>|null
     */
    public static function ver(int $id, array $usuario): ?array
    {
        $incidente = IncidenteModelo::porId($id);
        if ($incidente !== null) {
            Permisos::exigirMiembro($usuario, $incidente['proyecto_id']);
        }

        return $incidente;
    }

    /**
     * RNF-09
     *
     * @param array{id: int, rol: int} $usuario
     * @param array<string, string> $filtros
     * @return array{0: list<array<string, mixed>>, 1: Paginacion}
     */
    public static function pagina(array $usuario, int $pagina, array $filtros = []): array
    {
        $proyectos = Permisos::proyectos($usuario);
        $f = self::filtros($filtros, $usuario);
        $paginacion = new Paginacion(IncidenteModelo::contar($proyectos, $f), $pagina);

        return [IncidenteModelo::listar($proyectos, $paginacion->offset(), $f), $paginacion];
    }

    /**
     * Tablero: los filtros de la lista, sin paginar.
     *
     * @param array{id: int, rol: int} $usuario
     * @param array<string, string> $filtros
     * @return list<array<string, mixed>>
     */
    public static function tablero(array $usuario, array $filtros): array
    {
        return IncidenteModelo::listar(Permisos::proyectos($usuario), null, self::filtros($filtros, $usuario), CasoServicio::TOPE_VISTA);
    }

    /**
     * Valores de la URL a filtros del modelo. Lo que no es válido no filtra.
     *
     * @param array<string, string> $filtros estado, severidad, stopper ('1'), mios ('1')
     * @param array{id: int} $usuario
     * @return array<string, ?int>
     */
    private static function filtros(array $filtros, array $usuario): array
    {
        $numero = fn (string $clave, string $catalogo): ?int => Catalogo::existe($catalogo, $filtros[$clave] ?? null) ? (int) $filtros[$clave] : null;

        return [
            'estado' => $numero('estado', 'estado_incidente'),
            'severidad' => $numero('severidad', 'severidad'),
            'stopper' => ($filtros['stopper'] ?? '') === '1' ? 1 : null,
            'asignado' => ($filtros['mios'] ?? '') === '1' ? $usuario['id'] : null,
        ];
    }

    /**
     * Miembros del proyecto del caso o del incidente.
     *
     * @param array<string, mixed> $registro
     * @return list<array<string, mixed>>
     */
    public static function asignables(array $registro): array
    {
        return UsuarioModelo::deProyecto($registro['proyecto_id']);
    }

    /**
     * Un stopper abierto no entra en un plan cerrado (BUG-035).
     *
     * @param array<string, string> $d
     */
    private static function exigirPlanAbierto(int $proyectoId, array $d): void
    {
        if ($d['es_stopper'] === '1' && $d['estado'] !== '2' && PlanModelo::cerrado($proyectoId)) {
            throw new ErrorValidacion(['es_stopper' => 'El plan del proyecto está cerrado: reábralo para registrar un stopper.']);
        }
    }

    /**
     * @param array<string, string> $d
     * @param list<int> $asignables
     */
    private static function validarSeguimiento(Validador $v, array $d, array $asignables): void
    {
        $v->requerido('estado', $d['estado'])
            ->catalogo('estado', 'estado_incidente', $d['estado'])
            ->regla('asignado_id', $d['asignado_id'] === '' || in_array((int) $d['asignado_id'], $asignables, true), 'Valor no válido.');
    }
}
