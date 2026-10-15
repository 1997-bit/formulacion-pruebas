<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Helpers\Impresion;
use App\Models\CasoModelo;
use App\Models\IncidenteModelo;
use App\Services\Permisos;
use App\Services\ProyectoServicio;
use App\Services\ReporteServicio;

// RF-23
final class PanelControlador
{
    public function ver(): void
    {
        $usuario = Sesion::usuario();
        $proyectos = Permisos::proyectos($usuario);
        Vista::pagina('dashboard', [
            'titulo' => 'Panel',
            'avance' => ReporteServicio::avance($usuario),
            'asignados' => IncidenteModelo::asignadosA($usuario['id'], $proyectos, 5),
            'atrasados' => CasoModelo::atrasados($proyectos, 5),
            // Por rol. Tester: lo suyo por anotar. Admin: lo que solo él completa.
            'misPendientes' => $usuario['rol'] === 1 ? [] : CasoModelo::pendientesDe($proyectos, $usuario['id'], 5),
            'incompletos' => $usuario['rol'] === 1 ? ProyectoServicio::incompletos($usuario) : [],
            'migas' => [
                ['texto' => 'Plataforma'],
                ['texto' => 'Panel'],
            ],
        ]);
    }

    // Sin proyecto: decisión de cada proyecto. Con proyecto: su cierre.
    public function cierre(): void
    {
        $usuario = Sesion::usuario();
        // Sin ?proyecto: el activo del sidebar.
        $id = $_GET['proyecto'] ?? $usuario['activo'];
        if ($id === null) {
            Vista::pagina('reportes/listar', [
                'titulo' => 'Reporte de cierre',
                'proyectos' => ReporteServicio::avance($usuario)['proyectos'],
                'migas' => [['texto' => 'Plataforma'], ['texto' => 'Reporte de cierre']],
            ]);

            return;
        }

        $proyecto = ReporteServicio::cierre((int) $id, $usuario) ?? Respuesta::error(404);
        Vista::pagina('reportes/cierre', [
            'titulo' => 'Reporte de cierre · ' . $proyecto['nombre'],
            'documento' => Impresion::documento(0, $proyecto['nombre'], '', $proyecto['go'] ? '' : 'NO-GO'),
            'proyecto' => $proyecto,
            'migas' => [
                ['texto' => 'Reporte de cierre', 'ruta' => '/reportes/cierre'],
                ['texto' => $proyecto['nombre']],
            ],
        ]);
    }
}
