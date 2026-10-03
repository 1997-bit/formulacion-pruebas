<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Services\ProyectoServicio;

// Proyectos y miembros (7.1).
final class ProyectosControlador
{
    public function listar(): void
    {
        Vista::pagina('proyectos/listar', [
            'titulo' => 'Proyectos',
            'proyectos' => ProyectoServicio::listar(Sesion::usuario()),
            'flash' => Sesion::tomar('flash'),
            'errores' => Sesion::tomar('errores', []),
            'migas' => [['texto' => 'Administración'], ['texto' => 'Proyectos']],
        ]);
    }

    public function crear(): void
    {
        $this->formulario(null, Sesion::tomar('datos', ['miembros' => []]));
    }

    public function editar(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $proyecto = ProyectoServicio::ver($id, Sesion::usuario()) ?? Respuesta::error(404);
        $this->formulario($id, Sesion::tomar('datos') ?? [
            'nombre' => $proyecto['nombre'],
            'descripcion' => (string) $proyecto['descripcion'],
            'miembros' => array_map('strval', $proyecto['miembros']),
        ]);
    }

    public function guardar(): void
    {
        $datos = $this->datos();
        try {
            $nombre = ProyectoServicio::guardar(null, $datos, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/admin/proyectos/crear');
        }
        Respuesta::exito("Proyecto {$nombre} creado.", '/admin/proyectos');
    }

    public function actualizar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        ProyectoServicio::ver($id, Sesion::usuario()) ?? Respuesta::error(404);
        $datos = $this->datos();
        try {
            $nombre = ProyectoServicio::guardar($id, $datos, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/admin/proyectos/editar?id=' . $id);
        }
        Respuesta::exito("Proyecto {$nombre} guardado.", '/admin/proyectos');
    }

    public function eliminar(): void
    {
        try {
            ProyectoServicio::eliminar((int) ($_POST['id'] ?? 0), Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, [], '/admin/proyectos');
        }
        Respuesta::exito('Proyecto eliminado.', '/admin/proyectos');
    }

    /** @param array{nombre?: string, descripcion?: string, miembros: list<string>} $datos */
    private function formulario(?int $id, array $datos): void
    {
        $titulo = $id === null ? 'Crear proyecto' : 'Editar proyecto';
        Vista::pagina('proyectos/formulario', [
            'titulo' => $titulo,
            'id' => $id,
            'testers' => ProyectoServicio::testers(Sesion::usuario()),
            'errores' => Sesion::tomar('errores', []),
            'datos' => $datos,
            'migas' => [['texto' => 'Proyectos', 'ruta' => '/admin/proyectos'], ['texto' => $titulo]],
        ]);
    }

    /** @return array{nombre: string, descripcion: string, miembros: list<string>} */
    private function datos(): array
    {
        $texto = fn (string $campo): string => is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        $miembros = is_array($_POST['miembros'] ?? null) ? $_POST['miembros'] : [];

        return [
            'nombre' => $texto('nombre'),
            'descripcion' => $texto('descripcion'),
            'miembros' => array_values(array_filter($miembros, 'is_string')),
        ];
    }
}
