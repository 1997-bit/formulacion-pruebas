<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorNoEncontrado;
use App\Core\Vista;

// RF-25. Sin ?a: índice. Con ?a: artículo de config/ayuda.php
final class AyudaControlador
{
    public function ver(): void
    {
        $grupos = (require RAIZ . '/config/ayuda.php')['articulos'];
        $slug = $_GET['a'] ?? null;
        if ($slug === null) {
            Vista::pagina('ayuda/index', ['titulo' => 'Ayuda', 'grupos' => $grupos, 'migas' => [['texto' => 'Ayuda']]]);

            return;
        }
        // Slug fuera de la lista: 404. Así nadie abre otra vista
        $titulos = array_merge(...array_values($grupos));
        if (!is_string($slug) || !isset($titulos[$slug])) {
            throw new ErrorNoEncontrado();
        }
        Vista::pagina('ayuda/ver', [
            'titulo' => $titulos[$slug],
            'articulo' => $slug,
            'migas' => [['texto' => 'Ayuda', 'ruta' => '/ayuda'], ['texto' => $titulos[$slug]]],
        ]);
    }
}
