<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorValidacion;
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

    public function registro(): void
    {
        $this->pintar('acceso/registro', 'Crear cuenta');
    }

    public function registrar(): void
    {
        try {
            AccesoServicio::registrar(
                (string) ($_POST['nombre'] ?? ''),
                (string) ($_POST['usuario'] ?? ''),
                (string) ($_POST['clave'] ?? ''),
            );
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, ['nombre' => $_POST['nombre'] ?? '', 'usuario' => $_POST['usuario'] ?? ''], '/registro');
        }
        Respuesta::exito('Cuenta creada. Ya puede iniciar sesión.', '/');
    }

    public function salir(): void
    {
        Sesion::salir();
        Respuesta::redirigir('/');
    }

    // Lo que dejó Respuesta::errores()
    private function pintar(string $vista, string $titulo): void
    {
        $errores = $_SESSION['errores'] ?? [];
        $datos = $_SESSION['datos'] ?? [];
        unset($_SESSION['errores'], $_SESSION['datos']);

        Vista::pagina($vista, [
            'titulo' => $titulo,
            'errores' => $errores,
            'datos' => $datos,
            'flash' => Sesion::tomarFlash(),
        ], 'layouts/acceso');
    }
}
