<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Sesion;
use App\Core\Vista;

// Panel según rol (docs/IR.md, sección 3).
final class PanelControlador
{
    public function ver(): void
    {
        Vista::pagina('dashboard', [
            'titulo' => 'Panel',
            'usuario' => Sesion::usuario(),
            'migas' => [
                ['texto' => 'Plataforma'],
                ['texto' => 'Panel'],
            ],
        ]);
    }
}
