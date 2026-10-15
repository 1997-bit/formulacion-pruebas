<?php

declare(strict_types=1);

namespace App\Helpers;

// Documentos imprimibles. En papel: encabezado, pie y firmas. En PDF, services/Pdf.php: además, metadatos XMP para un postmortem.
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
     * Pie de página de la hoja y tabla final para un postmortem: con el ID de la petición se encuentran sus líneas en los logs.
     *
     * @param array{titulo: string, proyecto: string} $documento
     * @param array{nombre: string, usuario: string, rol: int} $usuario
     * @return array<string, string> etiqueta => valor
     */
    public static function metadatos(array $documento, array $usuario, \DateTimeInterface $ahora, string $ruta, string $peticion, ?string $version, string $navegador): array
    {
        return [
            'Documento' => $documento['titulo'],
            'Proyecto' => $documento['proyecto'],
            'Dirección' => $ruta,
            'Generado' => $ahora->format('c'),
            'Usuario' => $usuario['nombre'] . ' (' . $usuario['usuario'] . ', ' . ($usuario['rol'] === 1 ? 'admin' : 'tester') . ')',
            'ID de la petición' => $peticion,
            'Versión de la app' => $version ?? 'sin dato',
            'Navegador' => $navegador === '' ? 'sin dato' : $navegador,
        ];
    }

    // Esquema propio del XMP. URI única y prefijo, ISO 16684-1.
    public const XMP_NS = 'https://github.com/1997-bit/formulacion-pruebas/ns/documento/1.0/';

    // Etiqueta de metadatos() => propiedad del esquema propio.
    private const XMP_PROPIEDADES = [
        'Documento' => 'documento',
        'Proyecto' => 'proyecto',
        'Dirección' => 'direccion',
        'Generado' => 'generado',
        'Usuario' => 'usuario',
        'ID de la petición' => 'peticion',
        'Versión de la app' => 'version',
        'Navegador' => 'navegador',
    ];

    /**
     * XMP de los metadatos: dc:identifier con el ID de la petición y el esquema propio cp:.
     * esquema: la descripción del esquema propio que PDF/A pide para campos no estándar.
     *
     * @param array<string, string> $metadatos  metadatos()
     * @return array{rdf: string, esquema: string}
     */
    public static function xmp(array $metadatos): array
    {
        $x = static fn (string $texto): string => htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $rdf = "\t\t<rdf:Description rdf:about=\"\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n"
            . "\t\t\t<dc:identifier>" . $x($metadatos['ID de la petición']) . "</dc:identifier>\n"
            . "\t\t</rdf:Description>\n"
            . "\t\t<rdf:Description rdf:about=\"\" xmlns:cp=\"" . self::XMP_NS . "\">\n";
        $propiedades = '';
        foreach (self::XMP_PROPIEDADES as $etiqueta => $nombre) {
            $rdf .= "\t\t\t<cp:{$nombre}>" . $x($metadatos[$etiqueta]) . "</cp:{$nombre}>\n";
            $propiedades .= "\t\t\t\t\t\t\t\t<rdf:li rdf:parseType=\"Resource\"><pdfaProperty:name>{$nombre}</pdfaProperty:name>"
                . '<pdfaProperty:valueType>Text</pdfaProperty:valueType><pdfaProperty:category>external</pdfaProperty:category>'
                . '<pdfaProperty:description>' . $x($etiqueta) . "</pdfaProperty:description></rdf:li>\n";
        }
        $rdf .= "\t\t</rdf:Description>\n";
        $esquema = "\t\t\t\t\t<rdf:li rdf:parseType=\"Resource\">\n"
            . "\t\t\t\t\t\t<pdfaSchema:schema>Casos de Prueba</pdfaSchema:schema>\n"
            . "\t\t\t\t\t\t<pdfaSchema:namespaceURI>" . self::XMP_NS . "</pdfaSchema:namespaceURI>\n"
            . "\t\t\t\t\t\t<pdfaSchema:prefix>cp</pdfaSchema:prefix>\n"
            . "\t\t\t\t\t\t<pdfaSchema:property><rdf:Seq>\n" . $propiedades . "\t\t\t\t\t\t</rdf:Seq></pdfaSchema:property>\n"
            . "\t\t\t\t\t</rdf:li>\n";

        return ['rdf' => $rdf, 'esquema' => $esquema];
    }

    /**
     * Rama y commit del repo, de .git sin ejecutar git. Null si no hay .git, como en Docker.
     */
    public static function version(string $raiz): ?string
    {
        $head = @file_get_contents($raiz . '/.git/HEAD');
        if ($head === false) {
            return null;
        }
        $head = trim($head);
        if (!str_starts_with($head, 'ref: ')) {
            return substr($head, 0, 7);
        }
        $ref = substr($head, 5);
        $commit = @file_get_contents($raiz . '/.git/' . $ref);

        return str_replace('refs/heads/', '', $ref) . ($commit === false ? '' : ' · ' . substr(trim($commit), 0, 7));
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
