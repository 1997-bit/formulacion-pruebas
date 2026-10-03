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
        $datos = [];
        foreach (self::CAMPOS as $campo) {
            $datos[$campo] = is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        }
        try {
            $codigo = IncidenteServicio::registrar($casoId, $datos, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/formularios/incidentes/registrar?caso=' . $casoId);
        }
        Respuesta::exito("Incidente {$codigo} registrado.", '/casos/resultado?id=' . $casoId);
    }
}
