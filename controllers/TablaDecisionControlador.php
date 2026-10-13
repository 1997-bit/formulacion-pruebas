<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\FilasModelo;
use App\Services\RequerimientoServicio;
use App\Services\TablaDecisionServicio;

// RF-11
final class TablaDecisionControlador
{
    // Sin requerimiento: lista de tablas. Con requerimiento: tabla de solo lectura.
    public function ver(): void
    {
        $usuario = Sesion::usuario();
        if (!isset($_GET['requerimiento'])) {
            [$requerimientos, $paginacion] = TablaDecisionServicio::pagina($usuario, (int) ($_GET['pagina'] ?? 1));
            Vista::pagina('tabla_decision/listar', [
                'titulo' => 'Tabla de decisión',
                'requerimientos' => $requerimientos,
                'paginacion' => $paginacion,
                'migas' => [['texto' => 'Formularios'], ['texto' => 'Tabla de decisión']],
            ]);

            return;
        }

        $requerimiento = RequerimientoServicio::ver((int) $_GET['requerimiento'], $usuario) ?? Respuesta::error(404);
        $guardado = TablaDecisionServicio::guardado($requerimiento['id'], $usuario);
        Vista::pagina('tabla_decision/ver', [
            'titulo' => 'Tabla de decisión · ' . $requerimiento['codigo'],
            'requerimiento' => $requerimiento,
            'tabla' => $guardado !== null ? TablaDecisionServicio::tabla($requerimiento['id'], $usuario) : null,
            'guardado' => $guardado,
            'flash' => Sesion::tomar('flash'),
            'migas' => Vista::migas('Tabla de decisión', '/formularios/tabla_decision', $requerimiento['codigo']),
        ]);
    }

    public function editar(): void
    {
        $usuario = Sesion::usuario();
        $requerimiento = RequerimientoServicio::ver((int) ($_GET['requerimiento'] ?? 0), $usuario) ?? Respuesta::error(404);
        Vista::pagina('tabla_decision/editar', [
            'titulo' => 'Editar tabla de decisión · ' . $requerimiento['codigo'],
            'requerimiento' => $requerimiento,
            'tabla' => Sesion::tomar('datos')['tabla'] ?? TablaDecisionServicio::tabla($requerimiento['id'], $usuario),
            'nueva' => TablaDecisionServicio::guardado($requerimiento['id'], $usuario) === null,
            'huella' => FilasModelo::huella(TablaDecisionServicio::TABLAS, 'requerimiento_id', $requerimiento['id']),
            'errores' => Sesion::tomar('errores', []),
            'migas' => Vista::migas('Tabla de decisión', '/formularios/tabla_decision', $requerimiento['codigo'], 'Editar', '?requerimiento=' . $requerimiento['id']),
        ]);
    }

    // Con "cambio" agrega o quita una fila y vuelve a la pantalla sin guardar.
    public function guardar(): void
    {
        $id = (int) ($_POST['requerimiento_id'] ?? 0);
        $editar = '/formularios/tabla_decision/editar?requerimiento=' . $id;
        $tabla = $this->tabla();
        if (is_string($_POST['cambio'] ?? null)) {
            Respuesta::errores([], ['tabla' => TablaDecisionServicio::cambiar($tabla, $_POST['cambio'])], $editar);
        }
        try {
            TablaDecisionServicio::guardar($id, $tabla, (string) ($_POST['huella'] ?? ''), Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, ['tabla' => $tabla], $editar);
        }
        Respuesta::exito('Tabla guardada.', '/formularios/tabla_decision?requerimiento=' . $id);
    }

    /**
     * condicion[i], condicion_regla[i][r], accion[i] y accion_regla[i][r] → tabla del servicio.
     * Casilla sin marcar no llega: vale "-".
     *
     * @return array{condiciones: list<array{texto: string, reglas: list<string>}>, acciones: list<array{texto: string, reglas: list<string>}>}
     */
    private function tabla(): array
    {
        $reglas = min(max((int) ($_POST['reglas'] ?? 0), 0), 2 ** TablaDecisionServicio::MAX_CONDICIONES);
        $tabla = ['condiciones' => [], 'acciones' => []];
        foreach (['condicion' => 'condiciones', 'accion' => 'acciones'] as $campo => $grupo) {
            $textos = is_array($_POST[$campo] ?? null) ? array_values($_POST[$campo]) : [];
            foreach ($textos as $i => $texto) {
                $celdas = [];
                for ($r = 0; $r < $reglas; $r++) {
                    $valor = $_POST["{$campo}_regla"][$i][$r] ?? '-';
                    $celdas[] = is_string($valor) ? $valor : '';
                }
                $tabla[$grupo][] = ['texto' => is_string($texto) ? $texto : '', 'reglas' => $celdas];
            }
        }

        return $tabla;
    }
}
