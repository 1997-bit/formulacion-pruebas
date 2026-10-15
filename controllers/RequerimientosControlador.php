<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Entrada;
use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Services\RequerimientoServicio;

final class RequerimientosControlador
{
    private const CAMPOS = ['proyecto_id', 'codigo', 'descripcion'];

    public function listar(): void
    {
        // Vista matriz: cobertura requerimiento × tipo de prueba, sin paginar.
        $matriz = ($_GET['vista'] ?? null) === 'matriz';
        [$requerimientos, $paginacion] = $matriz ? [[], null] : RequerimientoServicio::pagina(Sesion::usuario(), (int) ($_GET['pagina'] ?? 1));
        Vista::pagina('requerimientos/listar', [
            'titulo' => $matriz ? 'Matriz de cobertura' : 'Requerimientos',
            'vista' => $matriz ? 'matriz' : 'lista',
            'matriz' => $matriz ? RequerimientoServicio::matriz(Sesion::usuario()) : [],
            'requerimientos' => $requerimientos,
            'paginacion' => $paginacion,
            'flash' => Sesion::tomar('flash'),
            'migas' => [['texto' => 'Requerimientos'], ['texto' => 'Listar']],
        ]);
    }

    public function registrar(): void
    {
        Vista::pagina('requerimientos/registrar', [
            'titulo' => 'Registrar requerimiento',
            'proyectos' => RequerimientoServicio::proyectos(Sesion::usuario()),
            'errores' => Sesion::tomar('errores', []),
            'datos' => Sesion::tomar('datos', ['proyecto_id' => (string) Sesion::usuario()['activo']]),
            'migas' => [['texto' => 'Requerimientos', 'ruta' => '/requerimientos/listar'], ['texto' => 'Registrar']],
        ]);
    }

    public function guardar(): void
    {
        $datos = Entrada::textos(self::CAMPOS);
        try {
            $codigo = RequerimientoServicio::registrar($datos, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/requerimientos/registrar');
        }
        Respuesta::exito("Requerimiento {$codigo} guardado.", '/requerimientos/listar');
    }
}
