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

// Ningún sitio puede cargar estas páginas en un iframe (BUG-017).
header('X-Frame-Options: DENY');

// Un error no muestra la pila ni rutas: página 500 con un código corto, y el detalle con ese código en storage/logs (#125, #46).
ini_set('display_errors', '0');
set_exception_handler(function (\Throwable $e): void {
    $codigo = bin2hex(random_bytes(4));
    $linea = sprintf(
        '[%s] %s %s %s %s: %s en %s:%d',
        date('Y-m-d H:i:s'),
        $codigo,
        $_SERVER['REQUEST_METHOD'] ?? '-',
        $_SERVER['REQUEST_URI'] ?? '-',
        $e::class,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );
    error_log(str_replace(["\r", "\n"], ' ', $linea) . "\n", 3, RAIZ . '/storage/logs/errores.log');
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    Respuesta::error(500, $codigo);
});

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
