<?php

declare(strict_types=1);

namespace App\Controllers;

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

}
