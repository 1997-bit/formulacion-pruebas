<?php

declare(strict_types=1);

// Entrada de toda petición
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

// Sin iframe ajeno, BUG-017. frame-ancestors estándar, X-Frame-Options respaldo
header('X-Frame-Options: DENY');
// Solo scripts y estilos propios. style-src-attr: style="" de las vistas. inline-speculation-rules: precarga del layout
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'inline-speculation-rules'; style-src 'self'; style-src-attr 'unsafe-inline'; img-src 'self' data: blob:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
header('Cross-Origin-Opener-Policy: same-origin');
header('X-Content-Type-Options: nosniff');

// RNF-11. Una línea http por petición con su duración. Golden signals: latencia, tráfico y 5xx
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

// Página 500 con el uid, sin stack ni rutas. Stack trace en el log, #125, #46, ASVS 16.5.1
ini_set('display_errors', '0');
set_exception_handler(function (\Throwable $e): void {
    Log::canal('app')->critical('sys_crash', ['exception' => $e]);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    Respuesta::error(500, Log::uid());
});
// Warnings y notices al log, no a la pantalla. @ los calla
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
// RNF-02. Clave o rol cambiado o usuario borrado: cierra la sesión
if ($usuario !== null) {
    $motivo = Sesion::vencida();
    if ($motivo !== null) {
        Log::evento('info', 'session_expired:{id},{motivo}', ['motivo' => $motivo]);
        Sesion::vencer('La sesión venció. Inicie sesión de nuevo.');
        Respuesta::redirigir('/');
    }
    $usuario = AccesoServicio::vigente($usuario);
    if ($usuario === null) {
        Log::evento('info', 'session_expired:{id},{motivo}', ['motivo' => 'revocada']);
        Sesion::salir();
        Respuesta::redirigir('/');
    }
    // Proyecto activo: solo si existe y aún es miembro
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
// RNF-11. 403 con evento de seguridad
$prohibir = static function (string $nivel, string $evento) use ($ruta): never {
    Log::evento($nivel, $evento, ['ruta' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)]);
    Respuesta::error(403);
};
if ($ruta['rol'] === 1 && $usuario['rol'] !== 1) {
    $prohibir('critical', 'authz_fail:{id},{ruta}');
}
if ($metodo === 'POST' && !Csrf::valido($_POST['csrf'] ?? null)) {
    $prohibir('warning', 'malicious_csrf:{id}');
}
// Doble clic, BUG-031: va al registro del primer envío
if ($metodo === 'POST' && is_string($_POST['envio'] ?? null) && isset($_SESSION['envios'][$_POST['envio']])) {
    Respuesta::redirigir($_SESSION['envios'][$_POST['envio']]);
}

$clase = 'App\\Controllers\\' . $ruta['controlador'] . 'Controlador';
try {
    (new $clase())->{$ruta['accion']}();
} catch (ErrorPermiso) {
    $prohibir('critical', 'authz_fail:{id},{ruta}');
} catch (ErrorNoEncontrado) {
    Respuesta::error(404);
}
