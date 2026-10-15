<?php

declare(strict_types=1);

namespace App\Helpers;

// Documentos imprimibles: encabezado y pie de cada hoja. layouts/app.php pone las firmas al final.
final class Impresion
{
    // Número de formulario de la guía => título. 0: el reporte de cierre, sin número.
    private const FORMULARIOS = [
        0 => 'Reporte de cierre',
        1 => 'Registro de caso de prueba',
        2 => 'Matriz de clases de equivalencia',
        3 => 'Análisis de valor límite',
        4 => 'Tabla de decisión',
        5 => 'Cobertura de caja blanca',
        6 => 'Plan de pruebas',
        7 => 'Rúbrica de evaluación',
        8 => 'Autoevaluación y coevaluación',
    ];

    /**
     * Para el controlador: 'documento' => Impresion::documento(2, $proyecto, 'RF-01').
     *
     * @return array{titulo: string, proyecto: string}
     */
    public static function documento(int $formulario, string $proyecto, string $codigo = ''): array
    {
        $titulo = ($formulario === 0 ? '' : "Formulario {$formulario} · ") . self::FORMULARIOS[$formulario];

        return ['titulo' => $titulo . ($codigo === '' ? '' : " · {$codigo}"), 'proyecto' => $proyecto];
    }

    /**
     * Atributo style del <html>. @page lee estas variables en sus márgenes, ver componentes.css.
     *
     * @param array{titulo: string, proyecto: string} $documento
     */
    public static function variables(array $documento, string $usuario, \DateTimeInterface $ahora): string
    {
        $cadena = static fn (string $texto): string => "'" . addcslashes($texto, "'\\") . "'";

        return '--doc-titulo:' . $cadena($documento['titulo'])
            . ';--doc-proyecto:' . $cadena($documento['proyecto'])
            . ';--doc-pie:' . $cadena('Impreso el ' . Fecha::legible($ahora) . ' ' . $ahora->format('H:i') . ' por ' . $usuario);
    }
}
