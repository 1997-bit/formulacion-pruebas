<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Helpers\Impresion;
use App\Services\RubricaServicio;

// RF-14
final class RubricaControlador
{
    // Sin proyecto: proyectos con su total. Con proyecto: rúbrica de solo lectura.
    public function ver(): void
    {
        $usuario = Sesion::usuario();
        // Sin ?proyecto: el activo del sidebar.
        $id = $_GET['proyecto'] ?? $usuario['activo'];
        if ($id === null) {
            Vista::pagina('rubrica/listar', [
                'titulo' => 'Rúbrica',
                'proyectos' => RubricaServicio::proyectos($usuario),
                'migas' => [['texto' => 'Formularios'], ['texto' => 'Rúbrica']],
            ]);

            return;
        }

        $proyecto = RubricaServicio::proyecto((int) $id, $usuario) ?? Respuesta::error(404);
        Vista::pagina('rubrica/ver', [
            'titulo' => 'Rúbrica · ' . $proyecto['nombre'],
            'documento' => Impresion::documento(7, $proyecto['nombre']),
            'proyecto' => $proyecto,
            'rubrica' => RubricaServicio::rubrica($proyecto['id'], $usuario),
            'flash' => Sesion::tomar('flash'),
            'migas' => Vista::migas('Rúbrica', '/formularios/rubrica', $proyecto['nombre']),
        ]);
    }

    public function editar(): void
    {
        $usuario = Sesion::usuario();
        $proyecto = RubricaServicio::proyecto((int) ($_GET['proyecto'] ?? 0), $usuario) ?? Respuesta::error(404);
        $rubrica = RubricaServicio::rubrica($proyecto['id'], $usuario);
        Vista::pagina('rubrica/editar', [
            'titulo' => 'Editar rúbrica · ' . $proyecto['nombre'],
            'proyecto' => $proyecto,
            'nueva' => $rubrica === null,
            'puntos' => Sesion::tomar('datos')['puntos'] ?? $rubrica['puntos'] ?? [],
            'errores' => Sesion::tomar('errores', []),
            'migas' => Vista::migas('Rúbrica', '/formularios/rubrica', $proyecto['nombre'], 'Editar', '?proyecto=' . $proyecto['id']),
        ]);
    }

    public function guardar(): void
    {
        $usuario = Sesion::usuario();
        $id = (int) ($_POST['proyecto_id'] ?? 0);
        RubricaServicio::proyecto($id, $usuario) ?? Respuesta::error(404);
        $valores = is_array($_POST['puntos'] ?? null) ? $_POST['puntos'] : [];
        $datos = ['puntos' => array_map(fn (mixed $v): string => is_string($v) ? $v : '', $valores)];
        try {
            RubricaServicio::guardar($id, $datos['puntos'], $usuario);
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/formularios/rubrica/editar?proyecto=' . $id);
        }
        Respuesta::exito('Rúbrica guardada.', '/formularios/rubrica?proyecto=' . $id);
    }
}
