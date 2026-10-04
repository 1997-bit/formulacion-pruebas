<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorPermiso;
use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Services\AutoevaluacionServicio;

// RF-15
final class AutoevaluacionControlador
{
    // Sin proyecto: la lista de proyectos. Con proyecto: la evaluación propia y los promedios del equipo.
    public function ver(): void
    {
        $usuario = Sesion::usuario();
        if (!isset($_GET['proyecto'])) {
            Vista::pagina('autoevaluacion/listar', [
                'titulo' => 'Auto y coevaluación',
                'proyectos' => AutoevaluacionServicio::proyectos($usuario),
                'migas' => [['texto' => 'Formularios'], ['texto' => 'Auto y coevaluación']],
            ]);

            return;
        }

        $proyecto = AutoevaluacionServicio::proyecto((int) $_GET['proyecto'], $usuario) ?? Respuesta::error(404);
        Vista::pagina('autoevaluacion/ver', [
            'titulo' => 'Auto y coevaluación · ' . $proyecto['nombre'],
            'proyecto' => $proyecto,
            'puedeLlenar' => AutoevaluacionServicio::puedeLlenar($proyecto['id'], $usuario['id']),
            'evaluacion' => AutoevaluacionServicio::evaluacion($proyecto['id'], $usuario),
            'promedios' => AutoevaluacionServicio::promedios($proyecto['id'], $usuario),
            'flash' => Sesion::tomar('flash'),
            'migas' => $this->migas($proyecto),
        ]);
    }

    public function editar(): void
    {
        $usuario = Sesion::usuario();
        $proyecto = AutoevaluacionServicio::proyecto((int) ($_GET['proyecto'] ?? 0), $usuario) ?? Respuesta::error(404);
        if (!AutoevaluacionServicio::puedeLlenar($proyecto['id'], $usuario['id'])) {
            throw new ErrorPermiso();
        }
        $companeros = array_filter(
            AutoevaluacionServicio::promedios($proyecto['id'], $usuario),
            fn (array $p): bool => $p['id'] !== $usuario['id'],
        );
        Vista::pagina('autoevaluacion/editar', [
            'titulo' => 'Editar auto y coevaluación · ' . $proyecto['nombre'],
            'proyecto' => $proyecto,
            'companeros' => $companeros,
            'datos' => Sesion::tomar('datos') ?? AutoevaluacionServicio::evaluacion($proyecto['id'], $usuario),
            'errores' => Sesion::tomar('errores', []),
            'migas' => [...$this->migas($proyecto, true), ['texto' => 'Editar']],
        ]);
    }

    public function guardar(): void
    {
        $id = (int) ($_POST['proyecto_id'] ?? 0);
        $datos = [
            'evaluado_id' => is_string($_POST['evaluado_id'] ?? null) ? $_POST['evaluado_id'] : '',
            'auto' => $this->textos('auto'),
            'co' => $this->textos('co'),
            'comentario' => $this->textos('comentario'),
        ];
        try {
            AutoevaluacionServicio::guardar($id, $datos, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/formularios/autoevaluacion/editar?proyecto=' . $id);
        }
        Respuesta::exito('Evaluación guardada.', '/formularios/autoevaluacion?proyecto=' . $id);
    }

    /**
     * @param array<string, mixed> $proyecto
     * @return list<array{texto: string, ruta?: string}>
     */
    private function migas(array $proyecto, bool $enlace = false): array
    {
        $nombre = ['texto' => $proyecto['nombre']];
        if ($enlace) {
            $nombre['ruta'] = '/formularios/autoevaluacion?proyecto=' . $proyecto['id'];
        }

        return [['texto' => 'Formularios'], ['texto' => 'Auto y coevaluación', 'ruta' => '/formularios/autoevaluacion'], $nombre];
    }

    /**
     * name="campo[aspecto]" a aspecto => texto; lo que no sea texto queda vacío.
     *
     * @return array<int|string, string>
     */
    private function textos(string $campo): array
    {
        $valores = is_array($_POST[$campo] ?? null) ? $_POST[$campo] : [];

        return array_map(fn (mixed $v): string => is_string($v) ? $v : '', $valores);
    }
}
