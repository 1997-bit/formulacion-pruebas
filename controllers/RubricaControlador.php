<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Services\RubricaServicio;

// RF-14
final class RubricaControlador
{
    // Sin proyecto: la lista de proyectos con su total. Con proyecto: la rúbrica de solo lectura.
    public function ver(): void
    {
        $usuario = Sesion::usuario();
        if (!isset($_GET['proyecto'])) {
            Vista::pagina('rubrica/listar', [
                'titulo' => 'Rúbrica',
                'proyectos' => RubricaServicio::proyectos($usuario),
                'migas' => [['texto' => 'Formularios'], ['texto' => 'Rúbrica']],
            ]);

            return;
        }

        $proyecto = RubricaServicio::proyecto((int) $_GET['proyecto'], $usuario) ?? Respuesta::error(404);
        Vista::pagina('rubrica/ver', [
            'titulo' => 'Rúbrica · ' . $proyecto['nombre'],
            'proyecto' => $proyecto,
            'rubrica' => RubricaServicio::rubrica($proyecto['id'], $usuario),
            'flash' => Sesion::tomar('flash'),
            'migas' => $this->migas($proyecto),
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
            'migas' => [...$this->migas($proyecto, true), ['texto' => 'Editar']],
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

    /**
     * @param array<string, mixed> $proyecto
     * @return list<array{texto: string, ruta?: string}>
     */
    private function migas(array $proyecto, bool $enlace = false): array
    {
        $nombre = ['texto' => $proyecto['nombre']];
        if ($enlace) {
            $nombre['ruta'] = '/formularios/rubrica?proyecto=' . $proyecto['id'];
        }

        return [['texto' => 'Formularios'], ['texto' => 'Rúbrica', 'ruta' => '/formularios/rubrica'], $nombre];
    }
}
