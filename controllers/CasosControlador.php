<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ErrorValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\FilasModelo;
use App\Services\CasoServicio;
use App\Services\Historial;
use App\Services\Permisos;
use App\Services\RequerimientoServicio;

// RF-04, RF-05, RF-06, RF-07, RF-22, RF-24
final class CasosControlador
{
    private const CAMPOS = [
        'requerimiento_id', 'tipo_prueba', 'subtecnica', 'modulo', 'plataforma', 'entorno', 'objetivo',
        'precondiciones', 'entrada', 'pasos', 'resultado_esperado', 'fecha_inicio', 'fecha_fin',
        'solicitado_por', 'aprobado_por',
    ];
    private const CAMPOS_RESULTADO = [
        'estado', 'resultado_obtenido', 'observaciones', 'evidencia_enlace',
        'descripcion_captura', 'descripcion_log', 'descripcion_enlace',
    ];

    public function listar(): void
    {
        $filtros = [];
        foreach (['proyecto', 'requerimiento', 'estado'] as $filtro) {
            $filtros[$filtro] = is_string($_GET[$filtro] ?? null) ? $_GET[$filtro] : '';
        }
        [$casos, $paginacion] = CasoServicio::listar(Sesion::usuario(), $filtros, (int) ($_GET['pagina'] ?? 1));
        Vista::pagina('casos/listar', [
            'titulo' => 'Casos de prueba',
            'casos' => $casos,
            'paginacion' => $paginacion,
            'filtros' => $filtros,
            'proyectos' => RequerimientoServicio::proyectos(Sesion::usuario()),
            'requerimientos' => RequerimientoServicio::listar(Sesion::usuario()),
            'flash' => Sesion::tomar('flash'),
            'migas' => [['texto' => 'Casos de prueba'], ['texto' => 'Listar']],
        ]);
    }

    public function registrar(): void
    {
        $requerimientos = RequerimientoServicio::listar(Sesion::usuario());
        Vista::pagina('casos/formulario', [
            'titulo' => 'Registrar caso',
            'caso' => null,
            'historial' => [],
            'requerimientos' => $requerimientos,
            'miembros' => CasoServicio::miembrosPorProyecto($requerimientos),
            'errores' => Sesion::tomar('errores', []),
            'datos' => Sesion::tomar('datos', ['estado' => '0', 'evidencias' => []]),
            'migas' => [['texto' => 'Casos de prueba', 'ruta' => '/casos/listar'], ['texto' => 'Registrar']],
        ]);
    }

    public function guardar(): void
    {
        $datos = $this->texto([...self::CAMPOS, ...self::CAMPOS_RESULTADO]);
        $marcadas = $this->marcadas();
        try {
            [$id, $codigo] = CasoServicio::registrar($datos, $marcadas, $this->archivos(), Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos + ['evidencias' => $marcadas], '/casos/registrar');
        }
        Respuesta::exito("Caso {$codigo} guardado.", '/casos/resultado?id=' . $id);
    }

    public function editar(): void
    {
        $caso = CasoServicio::paraEditar((int) ($_GET['id'] ?? 0), Sesion::usuario()) ?? Respuesta::error(404);
        $requerimientos = array_values(CasoServicio::requerimientos($caso, Sesion::usuario()));
        Vista::pagina('casos/formulario', [
            'titulo' => 'Editar ' . $caso['codigo'],
            'caso' => $caso,
            'historial' => Historial::deCaso($caso['id']),
            'huella' => FilasModelo::huella(['casos_prueba'], 'id', $caso['id']),
            'requerimientos' => $requerimientos,
            'miembros' => CasoServicio::miembrosPorProyecto($requerimientos),
            'errores' => Sesion::tomar('errores', []),
            'datos' => Sesion::tomar('datos') ?? array_map('strval', array_intersect_key($caso, array_flip(self::CAMPOS))),
            'migas' => [
                ['texto' => 'Casos de prueba', 'ruta' => '/casos/listar'],
                ['texto' => $caso['codigo'], 'ruta' => '/casos/resultado?id=' . $caso['id']],
                ['texto' => 'Editar'],
            ],
        ]);
    }

    public function actualizar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        CasoServicio::paraEditar($id, Sesion::usuario()) ?? Respuesta::error(404);
        $datos = $this->texto(self::CAMPOS);
        try {
            $codigo = CasoServicio::editar($id, $datos, (string) ($_POST['huella'] ?? ''), Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos, '/casos/editar?id=' . $id);
        }
        Respuesta::exito("Caso {$codigo} guardado.", '/casos/resultado?id=' . $id);
    }

    public function resultado(): void
    {
        $caso = CasoServicio::ver((int) ($_GET['id'] ?? 0), Sesion::usuario()) ?? Respuesta::error(404);
        Vista::pagina('casos/resultado', [
            'titulo' => $caso['codigo'],
            'caso' => $caso,
            'editable' => Permisos::puedeEditarCaso(Sesion::usuario(), $caso),
            'flash' => Sesion::tomar('flash'),
            'errores' => Sesion::tomar('errores', []),
            'datos' => Sesion::tomar('datos') ?? [
                'estado' => (string) $caso['estado'],
                'resultado_obtenido' => (string) $caso['resultado_obtenido'],
                'observaciones' => (string) $caso['observaciones'],
                'evidencias' => [],
            ],
            'migas' => [['texto' => 'Casos de prueba', 'ruta' => '/casos/listar'], ['texto' => $caso['codigo']]],
        ]);
    }

    public function anotar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        CasoServicio::ver($id, Sesion::usuario()) ?? Respuesta::error(404);
        $datos = $this->texto(self::CAMPOS_RESULTADO);
        $marcadas = $this->marcadas();
        try {
            $codigo = CasoServicio::registrarResultado($id, $datos, $marcadas, $this->archivos(), Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, $datos + ['evidencias' => $marcadas], '/casos/resultado?id=' . $id);
        }
        Respuesta::exito("Resultado de {$codigo} guardado.", '/casos/resultado?id=' . $id);
    }

    public function eliminar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $caso = CasoServicio::ver($id, Sesion::usuario()) ?? Respuesta::error(404);
        try {
            CasoServicio::eliminar($id, Sesion::usuario());
        } catch (ErrorValidacion $e) {
            Respuesta::errores($e->errores, [], '/casos/resultado?id=' . $id);
        }
        Respuesta::exito("Caso {$caso['codigo']} eliminado.", '/casos/listar');
    }

    /**
     * @param list<string> $campos
     * @return array<string, string>
     */
    private function texto(array $campos): array
    {
        $datos = [];
        foreach ($campos as $campo) {
            $datos[$campo] = is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        }

        return $datos;
    }

    /** @return list<string> */
    private function marcadas(): array
    {
        return is_array($_POST['evidencias'] ?? null) ? array_values(array_filter($_POST['evidencias'], 'is_string')) : [];
    }

    /** @return array<string, array{name: string, tmp_name: string, size: int, error: int}> */
    private function archivos(): array
    {
        $archivos = [];
        foreach (['captura', 'log'] as $nombre) {
            $archivo = $_FILES['evidencia_' . $nombre] ?? null;
            if (is_array($archivo) && is_string($archivo['name'] ?? null)) {
                $archivos[$nombre] = $archivo;
            }
        }

        return $archivos;
    }
}
