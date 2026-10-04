<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Services\ReporteServicio;

// RF-23
final class PanelControlador
{
    public function ver(): void
    {
        Vista::pagina('dashboard', [
            'titulo' => 'Panel',
            'avance' => ReporteServicio::avance(Sesion::usuario()),
            'migas' => [
                ['texto' => 'Plataforma'],
                ['texto' => 'Panel'],
            ],
        ]);
    }

    // Sin proyecto: la decisión de cada proyecto. Con proyecto: su cierre.
    public function cierre(): void
    {
        $usuario = Sesion::usuario();
        if (!isset($_GET['proyecto'])) {
            Vista::pagina('reportes/listar', [
                'titulo' => 'Reporte de cierre',
                'proyectos' => ReporteServicio::avance($usuario)['proyectos'],
                'migas' => [['texto' => 'Plataforma'], ['texto' => 'Reporte de cierre']],
            ]);

            return;
        }

        $proyecto = ReporteServicio::cierre((int) $_GET['proyecto'], $usuario) ?? Respuesta::error(404);
        Vista::pagina('reportes/cierre', [
            'titulo' => 'Reporte de cierre · ' . $proyecto['nombre'],
            'proyecto' => $proyecto,
            'migas' => [
                ['texto' => 'Reporte de cierre', 'ruta' => '/reportes/cierre'],
                ['texto' => $proyecto['nombre']],
            ],
        ]);
    }
}
