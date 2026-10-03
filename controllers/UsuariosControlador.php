<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Services\UsuarioServicio;

// RF-02, RF-03. Ninguna vista recibe la clave.
final class UsuariosControlador
{
    private const CAMPOS = ['nombre', 'usuario', 'clave', 'rol'];

    public function listar(): void
    {
        Vista::pagina('usuarios/listar', [
            'titulo' => 'Usuarios',
            'usuarios' => UsuarioServicio::listar(Sesion::usuario()),
            'flash' => Sesion::tomar('flash'),
            'errores' => Sesion::tomar('errores', []),
            'migas' => [['texto' => 'Administración'], ['texto' => 'Usuarios']],
        ]);
    }

    public function crear(): void
    {
        $this->formulario(null, Sesion::tomar('datos', ['rol' => '0']));
    }

    public function editar(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $usuario = UsuarioServicio::ver($id, Sesion::usuario()) ?? Respuesta::error(404);
        $this->formulario($id, Sesion::tomar('datos') ?? array_map('strval', $usuario));
    }

    public function guardar(): void
    {
        $datos = $this->datos();
        try {
            $usuario = UsuarioServicio::crear($datos, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $this->sinClave($datos), '/admin/usuarios/crear');
        }
        Respuesta::exito("Usuario {$usuario} creado.", '/admin/usuarios');
    }

    public function actualizar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        UsuarioServicio::ver($id, Sesion::usuario()) ?? Respuesta::error(404);
        $datos = $this->datos();
        try {
            $usuario = UsuarioServicio::editar($id, $datos, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $this->sinClave($datos), '/admin/usuarios/editar?id=' . $id);
        }
        Respuesta::exito("Usuario {$usuario} guardado.", '/admin/usuarios');
    }

    public function eliminar(): void
    {
        try {
            UsuarioServicio::eliminar((int) ($_POST['id'] ?? 0), Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, [], '/admin/usuarios');
        }
        Respuesta::exito('Usuario eliminado.', '/admin/usuarios');
    }

    /** @param array<string, string> $datos */
    private function formulario(?int $id, array $datos): void
    {
        $titulo = $id === null ? 'Crear usuario' : 'Editar usuario';
        Vista::pagina('usuarios/formulario', [
            'titulo' => $titulo,
            'id' => $id,
            'errores' => Sesion::tomar('errores', []),
            'datos' => $datos,
            'migas' => [['texto' => 'Usuarios', 'ruta' => '/admin/usuarios'], ['texto' => $titulo]],
        ]);
    }

    /** @return array<string, string> */
    private function datos(): array
    {
        $datos = [];
        foreach (self::CAMPOS as $campo) {
            $datos[$campo] = is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        }

        return $datos;
    }

    /**
     * La clave no vuelve al formulario.
     *
     * @param array<string, string> $datos
     * @return array<string, string>
     */
    private function sinClave(array $datos): array
    {
        unset($datos['clave']);

        return $datos;
    }
}
