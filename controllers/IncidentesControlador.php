<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Entrada;
use App\Core\ErrorValidacion;
use App\Core\Paginacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\CasoModelo;
use App\Services\CasoServicio;
use App\Services\IncidenteServicio;
use App\Services\Permisos;

// RF-17, RF-19
final class IncidentesControlador
{
    private const CAMPOS = [
        'titulo', 'modulo', 'severidad', 'prioridad', 'descripcion', 'pasos',
        'resultado_esperado', 'resultado_obtenido', 'commit_afectado', 'estado', 'asignado_id', 'es_stopper',
    ];

    public function listar(): void
    {
        $filtros = [];
        foreach (['estado', 'severidad', 'stopper', 'mios', 'todos', 'q'] as $filtro) {
            $filtros[$filtro] = is_string($_GET[$filtro] ?? null) ? $_GET[$filtro] : '';
        }
        // Lista por defecto, g i y el menú
        $vista = ($_GET['vista'] ?? '') === 'tablero' ? 'tablero' : 'lista';
        [$incidentes, $paginacion] = $vista === 'lista'
            ? IncidenteServicio::pagina(Sesion::usuario(), (int) ($_GET['pagina'] ?? 1), $filtros)
            : [IncidenteServicio::tablero(Sesion::usuario(), $filtros), null];
        Vista::pagina('incidentes/listar', [
            'titulo' => 'Incidentes',
            'incidentes' => $incidentes,
            'paginacion' => $paginacion,
            'vista' => $vista,
            'filtros' => $filtros,
            'flash' => Sesion::tomar('flash'),
            'migas' => [['texto' => 'Incidentes'], ['texto' => 'Listar']],
        ]);
    }

    // Sin caso: lista los FAULT y busca por código, BUG-050. Con caso: formulario 10
    public function registrar(): void
    {
        $usuario = Sesion::usuario();
        $migas = [['texto' => 'Incidentes', 'ruta' => '/formularios/incidentes'], ['texto' => 'Registrar']];
        if (!isset($_GET['caso'])) {
            $codigo = strtoupper(trim((string) ($_GET['codigo'] ?? '')));
            $casos = $codigo === '' ? [] : CasoModelo::porCodigo(Permisos::proyectos($usuario), $codigo);
            if (count($casos) === 1) {
                Respuesta::redirigir('/formularios/incidentes/registrar?caso=' . $casos[0]['id']);
            }
            Vista::pagina('incidentes/registrar', [
                'titulo' => 'Registrar incidente',
                'caso' => null,
                'codigo' => $codigo,
                'casos' => $casos,
                'fallidos' => CasoModelo::fallidos(Permisos::proyectos($usuario), $codigo, Paginacion::POR_PAGINA),
                'migas' => $migas,
            ]);

            return;
        }

        $caso = CasoServicio::ver((int) $_GET['caso'], $usuario) ?? Respuesta::error(404);
        Vista::pagina('incidentes/registrar', [
            'titulo' => 'Registrar incidente',
            'caso' => $caso,
            'asignables' => IncidenteServicio::asignables($caso),
            'errores' => Sesion::tomar('errores', []),
            'datos' => Sesion::tomar('datos') ?? [
                'modulo' => $caso['modulo'],
                'pasos' => $caso['pasos'],
                'resultado_esperado' => $caso['resultado_esperado'],
                'resultado_obtenido' => (string) $caso['resultado_obtenido'],
                'commit_afectado' => (string) $caso['commit_probado'],
                'estado' => '0',
            ],
            'commits' => CasoModelo::commits($caso['proyecto_id']),
            'migas' => [
                ['texto' => 'Incidentes', 'ruta' => '/formularios/incidentes'],
                ['texto' => $caso['codigo'], 'ruta' => '/casos/resultado?id=' . $caso['id']],
                ['texto' => 'Registrar'],
            ],
        ]);
    }

    public function guardar(): void
    {
        $casoId = (int) ($_POST['caso_id'] ?? 0);
        $datos = Entrada::textos(self::CAMPOS);
        try {
            [$id, $codigo] = IncidenteServicio::registrar($casoId, $datos, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/formularios/incidentes/registrar?caso=' . $casoId);
        }
        Respuesta::exito("Incidente {$codigo} registrado.", '/formularios/incidentes/ver?id=' . $id);
    }

    public function ver(): void
    {
        $incidente = IncidenteServicio::ver((int) ($_GET['id'] ?? 0), Sesion::usuario()) ?? Respuesta::error(404);
        Vista::pagina('incidentes/ver', [
            'titulo' => $incidente['codigo'],
            'incidente' => $incidente,
            'asignables' => IncidenteServicio::asignables($incidente),
            'flash' => Sesion::tomar('flash'),
            'errores' => Sesion::tomar('errores', []),
            'datos' => Sesion::tomar('datos') ?? [
                'estado' => (string) $incidente['estado'],
                'asignado_id' => (string) $incidente['asignado_id'],
                'es_stopper' => (string) $incidente['es_stopper'],
                'commit_corregido' => (string) $incidente['commit_corregido'],
            ],
            'commits' => CasoModelo::commits($incidente['proyecto_id']),
            'migas' => [['texto' => 'Incidentes', 'ruta' => '/formularios/incidentes'], ['texto' => $incidente['codigo']]],
        ]);
    }

    public function actualizar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $datos = Entrada::textos(['estado', 'asignado_id', 'es_stopper', 'commit_corregido']);
        try {
            $codigo = IncidenteServicio::seguimiento($id, $datos, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/formularios/incidentes/ver?id=' . $id);
        }
        Respuesta::exito("Incidente {$codigo} guardado.", '/formularios/incidentes/ver?id=' . $id);
    }

    // Tablero: destino = "id-estado". Solo cambia el estado
    public function mover(): void
    {
        [$id, $estado] = array_map('intval', explode('-', (string) ($_POST['destino'] ?? '0-0'), 2) + [1 => 0]);
        try {
            $codigo = IncidenteServicio::mover($id, $estado, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, [], '/formularios/incidentes/ver?id=' . $id);
        }
        Respuesta::exito("Incidente {$codigo} movido.", '/formularios/incidentes?vista=tablero');
    }
}
