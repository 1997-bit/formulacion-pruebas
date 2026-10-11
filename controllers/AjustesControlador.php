<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Vista;

// RNF-07: tema y pista de atajos. Se guardan en el navegador (app.js).
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
