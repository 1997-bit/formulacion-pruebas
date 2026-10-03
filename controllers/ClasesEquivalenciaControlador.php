<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Services\ClasesEquivalenciaServicio;
use App\Services\RequerimientoServicio;

// RF-09
final class ClasesEquivalenciaControlador
{
    // Sin requerimiento: la lista de matrices. Con requerimiento: la matriz.
    public function ver(): void
    {
        $usuario = Sesion::usuario();
        $migas = [['texto' => 'Formularios'], ['texto' => 'Clases de equivalencia']];
        if (!isset($_GET['requerimiento'])) {
            [$requerimientos, $paginacion] = ClasesEquivalenciaServicio::pagina($usuario, (int) ($_GET['pagina'] ?? 1));
            Vista::pagina('clases_equivalencia/ver', [
                'titulo' => 'Clases de equivalencia',
                'requerimiento' => null,
                'requerimientos' => $requerimientos,
                'paginacion' => $paginacion,
                'migas' => $migas,
            ]);

            return;
        }

        $requerimiento = RequerimientoServicio::ver((int) $_GET['requerimiento'], $usuario) ?? Respuesta::error(404);
        Vista::pagina('clases_equivalencia/ver', [
            'titulo' => 'Clases de equivalencia',
            'requerimiento' => $requerimiento,
            'filas' => Sesion::tomar('datos')['filas'] ?? ClasesEquivalenciaServicio::filas($requerimiento['id'], $usuario),
            'errores' => Sesion::tomar('errores', []),
            'flash' => Sesion::tomar('flash'),
            'migas' => [
                ['texto' => 'Formularios'],
                ['texto' => 'Clases de equivalencia', 'ruta' => '/formularios/clases_equivalencia'],
                ['texto' => $requerimiento['codigo']],
            ],
        ]);
    }

    public function guardar(): void
    {
        $id = (int) ($_POST['requerimiento_id'] ?? 0);
        $ruta = '/formularios/clases_equivalencia?requerimiento=' . $id;
        $filas = $this->filas(array_keys(ClasesEquivalenciaServicio::COLUMNAS));
        try {
            ClasesEquivalenciaServicio::guardar($id, $filas, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, ['filas' => $filas], $ruta);
        }
        Respuesta::exito('Matriz guardada.', $ruta);
    }

    /**
     * Columnas name="columna[]" a filas, en el orden de la pantalla.
     *
     * @param list<string> $columnas
     * @return list<array<string, string>>
     */
    private function filas(array $columnas): array
    {
        $total = is_array($_POST[$columnas[0]] ?? null) ? count($_POST[$columnas[0]]) : 0;
        $filas = [];
        for ($i = 0; $i < $total; $i++) {
            foreach ($columnas as $columna) {
                $valor = $_POST[$columna][$i] ?? '';
                $filas[$i][$columna] = is_string($valor) ? $valor : '';
            }
        }

        return $filas;
    }
}
