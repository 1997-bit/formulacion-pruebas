<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Vista;

// RF-23
final class PanelControlador
{
    public function ver(): void
    {
        Vista::pagina('dashboard', [
            'titulo' => 'Panel',
            'migas' => [
                ['texto' => 'Plataforma'],
                ['texto' => 'Panel'],
            ],
        ]);
    }
}
