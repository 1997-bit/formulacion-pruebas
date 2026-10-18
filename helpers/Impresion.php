<?php

declare(strict_types=1);

namespace App\Helpers;

// Encabezado, marca y pie de cada hoja. Documentos: firmas al final, layouts/app.php
final class Impresion
{
    // Número de formulario => título. 0: reporte de cierre
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
     * Uso: 'documento' => Impresion::documento(2, $proyecto, 'RF-01')
     * $marca: texto arriba al centro de cada hoja, como BORRADOR. Vacío: sin marca
     *
     * @return array{titulo: string, proyecto: string, marca: string}
     */
    public static function documento(int $formulario, string $proyecto, string $codigo = '', string $marca = ''): array
    {
        $titulo = ($formulario === 0 ? '' : "Formulario {$formulario} · ") . self::FORMULARIOS[$formulario];

        return ['titulo' => $titulo . ($codigo === '' ? '' : " · {$codigo}"), 'proyecto' => $proyecto, 'marca' => $marca];
    }

    /**
     * style del <html>. @page lee estas variables, componentes/impresion.css
     *
     * @param array{titulo: string, proyecto: string, marca: string} $documento
     */
    public static function variables(array $documento, string $usuario, \DateTimeInterface $ahora): string
    {
        $cadena = static fn (string $texto): string => "'" . addcslashes($texto, "'\\") . "'";

        return '--doc-titulo:' . $cadena($documento['titulo'])
            . ';--doc-proyecto:' . $cadena($documento['proyecto'])
            . ';--doc-marca:' . $cadena($documento['marca'])
            . ';--doc-pie:' . $cadena('Impreso el ' . Fecha::legible($ahora) . ' ' . Fecha::hora($ahora) . ' por ' . $usuario);
    }
}
