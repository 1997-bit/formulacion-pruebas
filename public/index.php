<?php

declare(strict_types=1);

// Toda petición entra aquí.
define('RAIZ', dirname(__DIR__));
require RAIZ . '/core/bootstrap.php';

use App\Core\Csrf;
use App\Core\ErrorNoEncontrado;
use App\Core\ErrorPermiso;
use App\Core\Log;
use App\Core\Respuesta;
use App\Core\Ruteador;
use App\Core\Sesion;
use App\Models\ProyectoModelo;
use App\Services\AccesoServicio;

// Ningún sitio puede cargar estas páginas en un iframe (BUG-017).
header('X-Frame-Options: DENY');

// RNF-11. Una línea http por petición, con su duración. Golden signals: latencia, tráfico y 5xx.
$duracion = static fn (): float => round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 1);
header('X-Request-Id: ' . Log::uid());
header_register_callback(static fn () => header('Server-Timing: app;dur=' . $duracion()));
register_shutdown_function(static function () use ($duracion): void {
    Log::canal('http')->info('{metodo} {ruta} {codigo}', [
        'metodo' => $_SERVER['REQUEST_METHOD'] ?? '-',
        'ruta' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
        'codigo' => http_response_code(),
        'duracion_ms' => $duracion(),
    ]);
});

// Error: página 500 con el uid de la petición, sin stack ni rutas. Stack trace en el log con ese uid. #125, #46, ASVS 16.5.1.
ini_set('display_errors', '0');
set_exception_handler(function (\Throwable $e): void {
    Log::canal('app')->critical('sys_crash', ['exception' => $e]);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    Respuesta::error(500, Log::uid());
});
// Warnings y notices: al log, no a la pantalla. @ los calla.
set_error_handler(function (int $nivel, string $mensaje, string $archivo, int $linea): bool {
    if (error_reporting() & $nivel) {
        Log::canal('app')->warning($mensaje, ['archivo' => $archivo, 'linea' => $linea]);
    }

    return true;
});

Sesion::iniciar();

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = Ruteador::buscar($metodo, $_SERVER['REQUEST_URI'] ?? '/') ?? Respuesta::error(404);
Log::$rf = $ruta['rf'];
$usuario = Sesion::usuario();
// RNF-02. Clave o rol cambiado, o usuario borrado: cierra la sesión.
if ($usuario !== null) {
    $usuario = AccesoServicio::vigente($usuario);
    if ($usuario === null) {
        Sesion::salir();
        Respuesta::redirigir('/');
    }
    // Proyecto activo del sidebar: solo si existe y el usuario aún es miembro.
    $activo = $_SESSION['proyecto'] ?? null;
    $usuario['activo'] = is_int($activo) && ($usuario['rol'] === 1 ? ProyectoModelo::porId($activo) !== null : in_array($activo, $usuario['proyectos'], true))
        ? $activo
        : null;
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
// Doble clic, BUG-031: ya guardado. Va al registro del primer envío.
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
