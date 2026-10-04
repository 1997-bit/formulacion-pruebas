<?php

declare(strict_types=1);

// Toda petición entra aquí.
define('RAIZ', dirname(__DIR__));
require RAIZ . '/core/bootstrap.php';

use App\Core\Csrf;
use App\Core\ErrorNoEncontrado;
use App\Core\ErrorPermiso;
use App\Core\Respuesta;
use App\Core\Ruteador;
use App\Core\Sesion;

Sesion::iniciar();

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = Ruteador::buscar($metodo, $_SERVER['REQUEST_URI'] ?? '/') ?? Respuesta::error(404);
$usuario = Sesion::usuario();

// RF-21, RNF-02
if ($ruta['rol'] !== null && $usuario === null) {
    Respuesta::redirigir('/');
}
if ($ruta['rol'] === 1 && $usuario['rol'] !== 1) {
    Respuesta::error(403);
}
if ($metodo === 'POST' && !Csrf::valido($_POST['csrf'] ?? null)) {
    Respuesta::error(403);
}

$clase = 'App\\Controllers\\' . $ruta['controlador'] . 'Controlador';
try {
    (new $clase())->{$ruta['accion']}();
} catch (ErrorPermiso) {
    Respuesta::error(403);
} catch (ErrorNoEncontrado) {
    Respuesta::error(404);
}
