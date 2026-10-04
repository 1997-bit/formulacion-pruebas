<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Services\CoberturaServicio;
use App\Services\RequerimientoServicio;

// RF-12
final class CoberturaControlador
{
    // Sin requerimiento: la lista de coberturas. Con requerimiento: la cobertura de solo lectura.
    public function ver(): void
    {
        $usuario = Sesion::usuario();
        if (!isset($_GET['requerimiento'])) {
            [$requerimientos, $paginacion] = CoberturaServicio::pagina($usuario, (int) ($_GET['pagina'] ?? 1));
            Vista::pagina('cobertura/listar', [
                'titulo' => 'Cobertura de caja blanca',
                'requerimientos' => $requerimientos,
                'paginacion' => $paginacion,
                'migas' => [['texto' => 'Formularios'], ['texto' => 'Cobertura de caja blanca']],
            ]);

            return;
        }

        $requerimiento = RequerimientoServicio::ver((int) $_GET['requerimiento'], $usuario) ?? Respuesta::error(404);
        Vista::pagina('cobertura/ver', [
            'titulo' => 'Cobertura de caja blanca · ' . $requerimiento['codigo'],
            'requerimiento' => $requerimiento,
            'filas' => CoberturaServicio::filas($requerimiento['id'], $usuario),
            'guardado' => CoberturaServicio::guardado($requerimiento['id'], $usuario),
            'flash' => Sesion::tomar('flash'),
            'migas' => $this->migas($requerimiento),
        ]);
    }

    public function editar(): void
    {
        $usuario = Sesion::usuario();
        $requerimiento = RequerimientoServicio::ver((int) ($_GET['requerimiento'] ?? 0), $usuario) ?? Respuesta::error(404);
        $guardadas = CoberturaServicio::filas($requerimiento['id'], $usuario);
        Vista::pagina('cobertura/editar', [
            'titulo' => 'Editar cobertura de caja blanca · ' . $requerimiento['codigo'],
            'requerimiento' => $requerimiento,
            'filas' => Sesion::tomar('datos')['filas'] ?? $guardadas,
            'nueva' => $guardadas === [],
            'errores' => Sesion::tomar('errores', []),
            'migas' => [...$this->migas($requerimiento, true), ['texto' => 'Editar']],
        ]);
    }

    public function guardar(): void
    {
        $id = (int) ($_POST['requerimiento_id'] ?? 0);
        $filas = $this->filas();
        try {
            CoberturaServicio::guardar($id, $filas, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, ['filas' => $filas], '/formularios/cobertura_blanca/editar?requerimiento=' . $id);
        }
        Respuesta::exito('Cobertura guardada.', '/formularios/cobertura_blanca?requerimiento=' . $id);
    }

    /**
     * @param array<string, mixed> $requerimiento
     * @return list<array{texto: string, ruta?: string}>
     */
    private function migas(array $requerimiento, bool $enlace = false): array
    {
        $codigo = ['texto' => $requerimiento['codigo']];
        if ($enlace) {
            $codigo['ruta'] = '/formularios/cobertura_blanca?requerimiento=' . $requerimiento['id'];
        }

        return [['texto' => 'Formularios'], ['texto' => 'Cobertura de caja blanca', 'ruta' => '/formularios/cobertura_blanca'], $codigo];
    }

    /**
     * total[metrica], cubiertos[metrica] y herramienta[metrica] a filas por métrica.
     *
     * @return array<int, array{total: string, cubiertos: string, herramienta: string}>
     */
    private function filas(): array
    {
        $filas = [];
        foreach (CoberturaServicio::METRICAS as $m) {
            foreach (['total', 'cubiertos', 'herramienta'] as $columna) {
                $valor = $_POST[$columna][$m] ?? '';
                $filas[$m][$columna] = is_string($valor) ? $valor : '';
            }
        }

        return $filas;
    }
}
