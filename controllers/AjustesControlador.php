<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Vista;

// RNF-07, WCAG 2.1.4. Se guardan en el navegador, app.js
final class AjustesControlador
{
    public function ver(): void
    {
        $this->pestana('apariencia', 'Apariencia');
    }

    public function accesibilidad(): void
    {
        $this->pestana('accesibilidad', 'Accesibilidad');
    }

    private function pestana(string $pestana, string $texto): void
    {
        Vista::pagina('ajustes/ver', [
            'titulo' => 'Ajustes · ' . $texto,
            'pestana' => $pestana,
            'migas' => [['texto' => 'Ajustes', 'ruta' => '/ajustes'], ['texto' => $texto]],
        ]);
    }
}
