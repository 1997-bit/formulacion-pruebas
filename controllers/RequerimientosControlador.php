<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Entrada;
use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\FilasModelo;
use App\Models\RequerimientoModelo;
use App\Services\Historial;
use App\Services\RequerimientoServicio;

final class RequerimientosControlador
{
    private const CAMPOS = ['proyecto_id', 'codigo', 'descripcion'];

    public function listar(): void
    {
        // Matriz requerimiento × tipo de prueba, sin paginar
        $matriz = ($_GET['vista'] ?? null) === 'matriz';
        [$requerimientos, $paginacion] = $matriz ? [[], null] : RequerimientoServicio::pagina(Sesion::usuario(), (int) ($_GET['pagina'] ?? 1));
        Vista::pagina('requerimientos/listar', [
            'titulo' => $matriz ? 'Matriz de cobertura' : 'Requerimientos',
            'vista' => $matriz ? 'matriz' : 'lista',
            'matriz' => $matriz ? RequerimientoServicio::matriz(Sesion::usuario()) : [],
            'requerimientos' => $requerimientos,
            'paginacion' => $paginacion,
            'flash' => Sesion::tomar('flash'),
            'accion' => Sesion::tomar('flash_accion'),
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
            [$id, $codigo] = RequerimientoServicio::registrar($datos, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/requerimientos/registrar');
        }
        Respuesta::exito("Requerimiento {$codigo} guardado.", '/requerimientos/listar', ['texto' => 'Registrar caso', 'ruta' => '/casos/registrar?requerimiento=' . $id]);
    }

    public function editar(): void
    {
        $requerimiento = RequerimientoServicio::ver((int) ($_GET['id'] ?? 0), Sesion::usuario()) ?? Respuesta::error(404);
        $id = (int) $requerimiento['id'];
        Vista::pagina('requerimientos/editar', [
            'titulo' => 'Editar ' . $requerimiento['codigo'],
            'requerimiento' => $requerimiento,
            'casos' => RequerimientoModelo::casos($id),
            'historial' => Historial::de('requerimientos', $id),
            'huella' => FilasModelo::huella(['requerimientos'], 'id', $id),
            'errores' => Sesion::tomar('errores', []),
            'datos' => Sesion::tomar('datos') ?: ['codigo' => $requerimiento['codigo'], 'descripcion' => $requerimiento['descripcion'], 'motivo' => ''],
            'migas' => [['texto' => 'Requerimientos', 'ruta' => '/requerimientos/listar'], ['texto' => $requerimiento['codigo']], ['texto' => 'Editar']],
        ]);
    }

    public function actualizar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $datos = Entrada::textos(['codigo', 'descripcion', 'motivo']);
        try {
            $codigo = RequerimientoServicio::editar($id, $datos, (string) ($_POST['huella'] ?? ''), Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/requerimientos/editar?id=' . $id);
        }
        Respuesta::exito("Requerimiento {$codigo} guardado.", '/requerimientos/listar');
    }

    public function eliminar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $codigo = RequerimientoServicio::eliminar($id, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, [], '/requerimientos/editar?id=' . $id);
        }
        Respuesta::exito("Requerimiento {$codigo} eliminado.", '/requerimientos/listar');
    }
}
