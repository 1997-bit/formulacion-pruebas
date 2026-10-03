<?php

declare(strict_types=1);

namespace App\Controllers;

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
        Vista::pagina('requerimientos/listar', [
            'titulo' => 'Requerimientos',
            'usuario' => Sesion::usuario(),
            'requerimientos' => RequerimientoServicio::listar(Sesion::usuario()),
            'flash' => Sesion::tomar('flash'),
            'migas' => [['texto' => 'Requerimientos'], ['texto' => 'Listar']],
        ]);
    }

    public function registrar(): void
    {
        Vista::pagina('requerimientos/registrar', [
            'titulo' => 'Registrar requerimiento',
            'usuario' => Sesion::usuario(),
            'proyectos' => RequerimientoServicio::proyectos(Sesion::usuario()),
            'errores' => Sesion::tomar('errores', []),
            'datos' => Sesion::tomar('datos', []),
            'migas' => [['texto' => 'Requerimientos', 'ruta' => '/requerimientos/listar'], ['texto' => 'Registrar']],
        ]);
    }

    public function guardar(): void
    {
        $datos = [];
        foreach (self::CAMPOS as $campo) {
            $datos[$campo] = is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        }
        try {
            $codigo = RequerimientoServicio::registrar($datos, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/requerimientos/registrar');
        }
        Respuesta::exito("Requerimiento {$codigo} guardado.", '/requerimientos/listar');
    }
}
