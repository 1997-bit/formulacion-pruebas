<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Vista;

// RNF-07: tema y atajos (WCAG 2.1.4). Se guardan en el navegador (app.js).
final class AjustesControlador
{
    public function ver(): void
    {
        Vista::pagina('ajustes/ver', [
            'titulo' => 'Ajustes',
            'migas' => [['texto' => 'Ajustes']],
        ]);
    }
}
