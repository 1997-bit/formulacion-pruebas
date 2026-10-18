<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorValidacion;
use App\Core\Log;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Services\AccesoServicio;

final class AccesoControlador
{
    public function login(): void
    {
        if (Sesion::usuario() !== null) {
            Respuesta::redirigir('/dashboard');
        }
        $this->pintar('acceso/login', 'Iniciar sesión');
    }

    public function entrar(): void
    {
        try {
            $usuario = AccesoServicio::entrar((string) ($_POST['usuario'] ?? ''), (string) ($_POST['clave'] ?? ''));
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, ['usuario' => $_POST['usuario'] ?? ''], '/');
        }
        Sesion::entrar($usuario);
        Respuesta::redirigir('/dashboard');
    }

    public function salir(): void
    {
        Log::evento('info', 'session_logout:{id}');
        Sesion::salir();
        Respuesta::redirigir('/');
    }

    private function pintar(string $vista, string $titulo): void
    {
        Vista::pagina($vista, [
            'titulo' => $titulo,
            'errores' => Sesion::tomar('errores', []),
            'datos' => Sesion::tomar('datos', []),
            'flash' => Sesion::tomar('flash'),
        ], 'layouts/acceso');
    }
}
