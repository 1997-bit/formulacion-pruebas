<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\FilasModelo;
use App\Services\ValorLimiteServicio;
use App\Services\RequerimientoServicio;

// RF-10
final class ValorLimiteControlador
{
    // Sin requerimiento: la lista de análisis. Con requerimiento: el análisis de solo lectura.
    public function ver(): void
    {
        $usuario = Sesion::usuario();
        if (!isset($_GET['requerimiento'])) {
            [$requerimientos, $paginacion] = ValorLimiteServicio::pagina($usuario, (int) ($_GET['pagina'] ?? 1));
            Vista::pagina('valor_limite/listar', [
                'titulo' => 'Valor límite',
                'requerimientos' => $requerimientos,
                'paginacion' => $paginacion,
                'migas' => [['texto' => 'Formularios'], ['texto' => 'Valor límite']],
            ]);

            return;
        }

        $requerimiento = RequerimientoServicio::ver((int) $_GET['requerimiento'], $usuario) ?? Respuesta::error(404);
        Vista::pagina('valor_limite/ver', [
            'titulo' => 'Valor límite · ' . $requerimiento['codigo'],
            'requerimiento' => $requerimiento,
            'filas' => ValorLimiteServicio::filas($requerimiento['id'], $usuario),
            'guardado' => ValorLimiteServicio::guardado($requerimiento['id'], $usuario),
            'flash' => Sesion::tomar('flash'),
            'migas' => $this->migas($requerimiento),
        ]);
    }

    public function editar(): void
    {
        $usuario = Sesion::usuario();
        $requerimiento = RequerimientoServicio::ver((int) ($_GET['requerimiento'] ?? 0), $usuario) ?? Respuesta::error(404);
        Vista::pagina('valor_limite/editar', [
            'titulo' => 'Editar valor límite · ' . $requerimiento['codigo'],
            'requerimiento' => $requerimiento,
            'filas' => Sesion::tomar('datos')['filas'] ?? ValorLimiteServicio::filas($requerimiento['id'], $usuario),
            'huella' => FilasModelo::huella(ValorLimiteServicio::TABLAS, 'requerimiento_id', $requerimiento['id']),
            'errores' => Sesion::tomar('errores', []),
            'migas' => [...$this->migas($requerimiento, true), ['texto' => 'Editar']],
        ]);
    }

    public function guardar(): void
    {
        $id = (int) ($_POST['requerimiento_id'] ?? 0);
        $filas = $this->filas(array_keys(ValorLimiteServicio::COLUMNAS));
        try {
            ValorLimiteServicio::guardar($id, $filas, (string) ($_POST['huella'] ?? ''), Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, ['filas' => $filas], '/formularios/valor_limite/editar?requerimiento=' . $id);
        }
        Respuesta::exito('Análisis guardado.', '/formularios/valor_limite?requerimiento=' . $id);
    }

    /**
     * @param array<string, mixed> $requerimiento
     * @return list<array{texto: string, ruta?: string}>
     */
    private function migas(array $requerimiento, bool $enlace = false): array
    {
        $codigo = ['texto' => $requerimiento['codigo']];
        if ($enlace) {
            $codigo['ruta'] = '/formularios/valor_limite?requerimiento=' . $requerimiento['id'];
        }

        return [['texto' => 'Formularios'], ['texto' => 'Valor límite', 'ruta' => '/formularios/valor_limite'], $codigo];
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
