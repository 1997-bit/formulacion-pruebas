<?php

declare(strict_types=1);

namespace App\Core;

// Usuario en sesión y mensajes flash.
final class Sesion
{
    // 8 horas sin actividad: alcanza para llenar un formulario largo.
    private const DURACION = 28800;

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.gc_maxlifetime', (string) self::DURACION);
        ini_set('session.use_strict_mode', '1');
        session_name(Env::get('SESSION_NOMBRE', 'casos_sesion'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    /** @param array<string, mixed> $usuario */
    public static function entrar(array $usuario): void
    {
        session_regenerate_id(true);
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

    public static function flash(string $mensaje): void
    {
        $_SESSION['flash'] = $mensaje;
    }

    public static function tomarFlash(): ?string
    {
        $mensaje = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        return $mensaje;
    }
}
