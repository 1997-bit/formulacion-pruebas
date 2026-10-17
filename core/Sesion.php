<?php

declare(strict_types=1);

namespace App\Core;

final class Sesion
{
    // 8 h: alcanza para un formulario largo.
    private const DURACION = 28800;

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.gc_maxlifetime', (string) self::DURACION);
        ini_set('session.use_strict_mode', '1');
        session_name('casos_sesion');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        // Sin no-store: Atrás restaura la página al instante, bfcache. no-cache sigue pidiendo la página al servidor.
        session_cache_limiter('');
        session_start();
        header('Cache-Control: private, no-cache');
    }

    /** @param array<string, mixed> $usuario */
    public static function entrar(array $usuario): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario'] = $usuario;
    }

    // Datos nuevos de la base, sin regenerar el id. Ejemplo: nombre cambiado.
    /** @param array<string, mixed> $usuario */
    public static function refrescar(array $usuario): void
    {
        $_SESSION['usuario'] = $usuario;
    }

    public static function salir(): void
    {
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', ['expires' => 1, 'path' => '/']);
    }

    /** @return array<string, mixed>|null */
    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    // $accion: botón del aviso, el siguiente paso. Material: snackbar con acción.
    /** @param array{texto: string, ruta: string}|null $accion */
    public static function flash(string $mensaje, ?array $accion = null): void
    {
        $_SESSION['flash'] = $mensaje;
        $_SESSION['flash_accion'] = $accion;
    }

    // Lee y borra flash, errores y datos de Respuesta::errores(). Errores y datos, solo en su ruta.
    public static function tomar(string $clave, mixed $defecto = null): mixed
    {
        $valor = in_array($clave, ['errores', 'datos'], true)
            ? $_SESSION[$clave][$_SERVER['REQUEST_URI'] ?? '/'] ?? $defecto
            : $_SESSION[$clave] ?? $defecto;
        unset($_SESSION[$clave]);

        return $valor;
    }
}
