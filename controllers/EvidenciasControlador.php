<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Respuesta;
use App\Core\Sesion;
use App\Services\CasoServicio;
use App\Services\Subida;

// RF-24, RNF-03: solo miembros del proyecto.
final class EvidenciasControlador
{
    public function ver(): void
    {
        $evidencia = CasoServicio::evidencia((int) ($_GET['id'] ?? 0), Sesion::usuario()) ?? Respuesta::error(404);
        if ($evidencia['enlace'] !== null) {
            Respuesta::redirigir($evidencia['enlace']);
        }
        $ruta = Subida::ruta($evidencia['archivo']);
        if (!is_file($ruta)) {
            Respuesta::error(404);
        }
        Respuesta::archivo($ruta, Subida::mime($evidencia['archivo']), $evidencia['nombre_original']);
    }
}
