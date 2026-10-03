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
        'proyecto_id', 'requerimiento_id', 'tipo_prueba', 'tecnica', 'subtecnica', 'modulo', 'plataforma', 'entorno', 'objetivo',
        'precondiciones', 'entrada', 'pasos', 'resultado_esperado', 'fecha_inicio', 'fecha_fin',
        'estado', 'resultado_obtenido', 'observaciones', 'evidencia_enlace',
        'descripcion_captura', 'descripcion_log', 'descripcion_enlace',
    ];

    public function listar(): void
    {
        [$casos, $paginacion] = CasoServicio::pagina(Sesion::usuario(), (int) ($_GET['pagina'] ?? 1));
        Vista::pagina('casos/listar', [
            'titulo' => 'Casos de prueba',
            'casos' => $casos,
            'paginacion' => $paginacion,
            'flash' => Sesion::tomar('flash'),
            'migas' => [['texto' => 'Casos de prueba'], ['texto' => 'Listar']],
        ]);
    }

    public function registrar(): void
    {
        Vista::pagina('casos/registrar', [
            'titulo' => 'Registrar caso',
            'proyectos' => RequerimientoServicio::proyectos(Sesion::usuario()),
            'requerimientos' => RequerimientoServicio::listar(Sesion::usuario()),
            'errores' => Sesion::tomar('errores', []),
            'datos' => Sesion::tomar('datos', ['estado' => '0', 'evidencias' => []]),
            'migas' => [['texto' => 'Casos de prueba', 'ruta' => '/casos/listar'], ['texto' => 'Registrar']],
        ]);
    }

    public function guardar(): void
    {
        $datos = [];
        foreach (self::CAMPOS as $campo) {
            $datos[$campo] = is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        }
        $marcadas = is_array($_POST['evidencias'] ?? null) ? array_values(array_filter($_POST['evidencias'], 'is_string')) : [];
        $archivos = [];
        foreach (['captura', 'log'] as $nombre) {
            $archivo = $_FILES['evidencia_' . $nombre] ?? null;
            if (is_array($archivo) && is_string($archivo['name'] ?? null)) {
                $archivos[$nombre] = $archivo;
            }
        }
        try {
            $codigo = CasoServicio::registrar($datos, $marcadas, $archivos, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos + ['evidencias' => $marcadas], '/casos/registrar');
        }
        Respuesta::exito("Caso {$codigo} guardado.", '/casos/listar');
    }
}
