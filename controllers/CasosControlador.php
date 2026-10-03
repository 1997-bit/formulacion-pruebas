<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Services\CasoServicio;
use App\Services\RequerimientoServicio;

// RF-04, RF-05, RF-24
final class CasosControlador
{
    private const CAMPOS = [
        'requerimiento_id', 'tipo_prueba', 'tecnica', 'subtecnica', 'modulo', 'plataforma', 'entorno', 'objetivo',
        'precondiciones', 'entrada', 'pasos', 'resultado_esperado', 'fecha_inicio', 'fecha_fin',
        'estado', 'resultado_obtenido', 'observaciones', 'evidencia_enlace', 'evidencia_descripcion',
    ];

    public function listar(): void
    {
        Vista::pagina('casos/listar', [
            'titulo' => 'Casos de prueba',
            'casos' => CasoServicio::listar(Sesion::usuario()),
            'flash' => Sesion::tomar('flash'),
            'migas' => [['texto' => 'Casos de prueba'], ['texto' => 'Listar']],
        ]);
    }

    public function registrar(): void
    {
        Vista::pagina('casos/registrar', [
            'titulo' => 'Registrar caso',
            'requerimientos' => RequerimientoServicio::listar(Sesion::usuario()),
            'errores' => Sesion::tomar('errores', []),
            'datos' => Sesion::tomar('datos', []),
            'migas' => [['texto' => 'Casos de prueba', 'ruta' => '/casos/listar'], ['texto' => 'Registrar']],
        ]);
    }

    public function guardar(): void
    {
        $datos = [];
        foreach (self::CAMPOS as $campo) {
            $datos[$campo] = is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        }
        $archivo = $_FILES['evidencia'] ?? null;
        $archivo = is_array($archivo) && is_string($archivo['name'] ?? null) ? $archivo : null;
        try {
            $codigo = CasoServicio::registrar($datos, $archivo, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/casos/registrar');
        }
        Respuesta::exito("Caso {$codigo} guardado.", '/casos/listar');
    }
}
