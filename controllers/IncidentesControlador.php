<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\CasoModelo;
use App\Services\CasoServicio;
use App\Services\IncidenteServicio;

// RF-17, RF-19
final class IncidentesControlador
{
    private const CAMPOS = [
        'titulo', 'modulo', 'severidad', 'prioridad', 'descripcion', 'pasos',
        'resultado_esperado', 'resultado_obtenido', 'estado', 'asignado_id', 'es_stopper',
    ];

    public function listar(): void
    {
        [$incidentes, $paginacion] = IncidenteServicio::pagina(Sesion::usuario(), (int) ($_GET['pagina'] ?? 1));
        Vista::pagina('incidentes/listar', [
            'titulo' => 'Incidentes',
            'incidentes' => $incidentes,
            'paginacion' => $paginacion,
            'flash' => Sesion::tomar('flash'),
            'migas' => [['texto' => 'Incidentes'], ['texto' => 'Listar']],
        ]);
    }

    // Sin caso: primero se elige. Con caso: el formulario 10, con lo del caso ya puesto.
    public function registrar(): void
    {
        $usuario = Sesion::usuario();
        $migas = [['texto' => 'Incidentes', 'ruta' => '/formularios/incidentes'], ['texto' => 'Registrar']];
        if (!isset($_GET['caso'])) {
            Vista::pagina('incidentes/registrar', [
                'titulo' => 'Registrar incidente',
                'caso' => null,
                'casos' => CasoModelo::listar($usuario['id'], $usuario['rol'] === 1, ['proyecto' => null, 'requerimiento' => null, 'estado' => null]),
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
                'estado' => '0',
            ],
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
        CasoServicio::ver($casoId, Sesion::usuario()) ?? Respuesta::error(404);
        $datos = $this->texto(self::CAMPOS);
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
            ],
            'migas' => [['texto' => 'Incidentes', 'ruta' => '/formularios/incidentes'], ['texto' => $incidente['codigo']]],
        ]);
    }

    public function actualizar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        IncidenteServicio::ver($id, Sesion::usuario()) ?? Respuesta::error(404);
        $datos = $this->texto(['estado', 'asignado_id', 'es_stopper']);
        try {
            $codigo = IncidenteServicio::seguimiento($id, $datos, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/formularios/incidentes/ver?id=' . $id);
        }
        Respuesta::exito("Incidente {$codigo} guardado.", '/formularios/incidentes/ver?id=' . $id);
    }

    /**
     * @param list<string> $campos
     * @return array<string, string>
     */
    private function texto(array $campos): array
    {
        $datos = [];
        foreach ($campos as $campo) {
            $datos[$campo] = is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        }

        return $datos;
    }
}
