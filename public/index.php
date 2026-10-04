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
use App\Services\AccesoServicio;

Sesion::iniciar();

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = Ruteador::buscar($metodo, $_SERVER['REQUEST_URI'] ?? '/') ?? Respuesta::error(404);
$usuario = Sesion::usuario();
// RNF-02: la clave o el rol cambió, o el usuario ya no existe: la sesión se cierra.
if ($usuario !== null) {
    $usuario = AccesoServicio::vigente($usuario);
    if ($usuario === null) {
        Sesion::salir();
        Respuesta::redirigir('/');
    }
    Sesion::refrescar($usuario);
}

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
// Doble clic (BUG-031): el envío ya se guardó; va al registro que creó el primero.
if ($metodo === 'POST' && is_string($_POST['envio'] ?? null) && isset($_SESSION['envios'][$_POST['envio']])) {
    Respuesta::redirigir($_SESSION['envios'][$_POST['envio']]);
}

$clase = 'App\\Controllers\\' . $ruta['controlador'] . 'Controlador';
try {
    (new $clase())->{$ruta['accion']}();
} catch (ErrorPermiso) {
    Respuesta::error(403);
} catch (ErrorNoEncontrado) {
    Respuesta::error(404);
}
