<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Sesion;
use App\Core\Vista;
use App\Services\LogServicio;

// RNF-11. Solo admin
final class LogsControlador
{
    public function ver(): void
    {
        $filtros = [];
        foreach (['dia', 'canal', 'nivel', 'evento', 'uid'] as $filtro) {
            $filtros[$filtro] = is_string($_GET[$filtro] ?? null) ? $_GET[$filtro] : '';
        }
        $vista = in_array($_GET['vista'] ?? null, ['seguridad', 'rendimiento'], true) ? $_GET['vista'] : 'lista';
        $f = LogServicio::filtros($filtros);
        $datos = match ($vista) {
            'seguridad' => ['seguridad' => LogServicio::seguridad(Sesion::usuario(), $f['dia'])],
            'rendimiento' => ['rendimiento' => LogServicio::rendimiento(Sesion::usuario(), $f['dia'])],
            default => array_combine(['lineas', 'paginacion'], LogServicio::listar(Sesion::usuario(), $filtros, (int) ($_GET['pagina'] ?? 1))),
        };
        Vista::pagina('logs/ver', $datos + [
            'titulo' => 'Logs',
            'vista' => $vista,
            'filtros' => $f,
            'dias' => LogServicio::dias(),
            'migas' => [['texto' => 'Administración'], ['texto' => 'Logs']],
        ]);
    }
}
