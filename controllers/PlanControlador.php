<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Services\PlanServicio;

// RF-13
final class PlanControlador
{
    private const CAMPOS = ['version', 'responsable_id', 'fecha', 'estrategia', 'estado', ...PlanServicio::TEXTOS];

    // Sin proyecto: la lista de proyectos. Con proyecto: su plan.
    public function ver(): void
    {
        $usuario = Sesion::usuario();
        if (!isset($_GET['proyecto'])) {
            Vista::pagina('plan_pruebas/listar', [
                'titulo' => 'Plan de pruebas',
                'proyectos' => PlanServicio::proyectos($usuario),
                'migas' => [['texto' => 'Formularios'], ['texto' => 'Plan de pruebas']],
            ]);

            return;
        }

        $proyecto = PlanServicio::proyecto((int) $_GET['proyecto'], $usuario) ?? Respuesta::error(404);
        Vista::pagina('plan_pruebas/ver', [
            'titulo' => 'Plan de pruebas · ' . $proyecto['nombre'],
            'proyecto' => $proyecto,
            'plan' => PlanServicio::plan($proyecto['id'], $usuario),
            'flash' => Sesion::tomar('flash'),
            'migas' => $this->migas($proyecto),
        ]);
    }

    public function editar(): void
    {
        $usuario = Sesion::usuario();
        $proyecto = PlanServicio::proyecto((int) ($_GET['proyecto'] ?? 0), $usuario) ?? Respuesta::error(404);
        $plan = PlanServicio::plan($proyecto['id'], $usuario);
        Vista::pagina('plan_pruebas/editar', [
            'titulo' => ($plan ? 'Editar' : 'Llenar') . ' plan de pruebas · ' . $proyecto['nombre'],
            'proyecto' => $proyecto,
            'nuevo' => $plan === null,
            'miembros' => PlanServicio::miembros($proyecto['id']),
            'datos' => Sesion::tomar('datos') ?? array_map(fn (mixed $v): string => (string) $v, $plan ?? []),
            'errores' => Sesion::tomar('errores', []),
            'migas' => [...$this->migas($proyecto, true), ['texto' => $plan ? 'Editar' : 'Llenar']],
        ]);
    }

    public function guardar(): void
    {
        $id = (int) ($_POST['proyecto_id'] ?? 0);
        $datos = [];
        foreach (self::CAMPOS as $campo) {
            $datos[$campo] = is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        }
        try {
            PlanServicio::guardar($id, $datos, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/formularios/plan_pruebas/editar?proyecto=' . $id);
        }
        Respuesta::exito('Plan guardado.', '/formularios/plan_pruebas?proyecto=' . $id);
    }

    /**
     * @param array<string, mixed> $proyecto
     * @return list<array{texto: string, ruta?: string}>
     */
    private function migas(array $proyecto, bool $enlace = false): array
    {
        $nombre = ['texto' => $proyecto['nombre']];
        if ($enlace) {
            $nombre['ruta'] = '/formularios/plan_pruebas?proyecto=' . $proyecto['id'];
        }

        return [['texto' => 'Formularios'], ['texto' => 'Plan de pruebas', 'ruta' => '/formularios/plan_pruebas'], $nombre];
    }
}
