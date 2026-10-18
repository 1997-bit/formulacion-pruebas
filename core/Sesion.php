<?php

declare(strict_types=1);

namespace App\Core;

final class Sesion
{
    // ASVS 7.3.1 y 7.3.2, RNF-02. OWASP: 15 a 30 min sin uso, 4 a 8 h de jornada
    private const INACTIVA = 1800;
    private const MAXIMA = 28800;

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.gc_maxlifetime', (string) self::MAXIMA);
        ini_set('session.use_strict_mode', '1');
        session_name('casos_sesion');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        // Sin no-store: Atrás usa bfcache. no-cache pide la página al servidor
        session_cache_limiter('');
        session_start();
        header('Cache-Control: private, no-cache');
    }

    /** @param array<string, mixed> $usuario */
    public static function entrar(array $usuario): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario'] = $usuario;
        $_SESSION['inicio'] = $_SESSION['uso'] = time();
    }

    // Vencida: inactiva o maxima. Si no, marca el uso y da null
    public static function vencida(): ?string
    {
        $ahora = time();
        if ($ahora - ($_SESSION['inicio'] ?? 0) > self::MAXIMA) {
            return 'maxima';
        }
        if ($ahora - ($_SESSION['uso'] ?? 0) > self::INACTIVA) {
            return 'inactiva';
        }
        $_SESSION['uso'] = $ahora;

        return null;
    }

    // Id nuevo sin usuario ni datos. Solo queda el aviso del login
    public static function vencer(string $aviso): void
    {
        session_regenerate_id(true);
        $_SESSION = [];
        self::flash($aviso);
    }

    // Datos nuevos de la base sin regenerar el id
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

    // $accion: botón del aviso, snackbar de Material
    /** @param array{texto: string, ruta: string}|null $accion */
    public static function flash(string $mensaje, ?array $accion = null): void
    {
        $_SESSION['flash'] = $mensaje;
        $_SESSION['flash_accion'] = $accion;
    }

    // Lee y borra flash, errores y datos. Errores y datos solo en su ruta
    public static function tomar(string $clave, mixed $defecto = null): mixed
    {
        $valor = in_array($clave, ['errores', 'datos'], true)
            ? $_SESSION[$clave][$_SERVER['REQUEST_URI'] ?? '/'] ?? $defecto
            : $_SESSION[$clave] ?? $defecto;
        unset($_SESSION[$clave]);

        return $valor;
    }
}
