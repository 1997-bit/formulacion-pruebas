<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Log;
use App\Helpers\Impresion;

// Documento imprimible como PDF/A-3b, con metadatos Info y XMP. Respuesta::pdf lo envía.
final class Pdf
{
    // En el PDF no van: botones, formularios, pasos, gráficos de pantalla e íconos. Sus datos van en tablas.
    private const QUITAR = ['no-imprimir', 'btn', 'acciones', 'pasos', 'pestanas', 'filtros', 'grafico-barras', 'grafico-pilas', 'grafico-stoppers', 'grafico-linea', 'grafico-eje', 'leyenda', 'icono', 'firmas', 'campo-ayuda'];

    /**
     * Archivo PDF, nombre ASCII y SHA-256. $html: la vista sin layout.
     * El hash va al log con el ID de la petición: en un postmortem se compara con cualquier copia.
     *
     * @param array{titulo: string, proyecto: string} $documento
     * @param array<string, string> $metadatos  Impresion::metadatos()
     * @return array{archivo: string, nombre: string, sha256: string}
     */
    public static function generar(string $html, array $documento, array $metadatos): array
    {
        $pdf = new class ('P', 'pt', 'LETTER', true, 'UTF-8', false, 3) extends \TCPDF {
            public string $encabezado = '';
            public string $proyecto = '';
            public string $pie = '';

            // phpcs:ignore PSR1.Methods.CamelCapsMethodName -- nombre de TCPDF
            public function Header(): void
            {
                $this->SetFont('dejavusans', '', 7);
                $this->SetTextColor(90, 90, 90);
                $this->SetY(28);
                $this->Cell(0, 10, $this->encabezado, 0, 0, 'L');
                $this->SetX($this->lMargin);
                $this->Cell(0, 10, $this->proyecto, 0, 0, 'R');
            }

            // phpcs:ignore PSR1.Methods.CamelCapsMethodName -- nombre de TCPDF
            public function Footer(): void
            {
                $this->SetFont('dejavusans', '', 7);
                $this->SetTextColor(90, 90, 90);
                $this->SetY(-36);
                $this->Cell(0, 10, $this->pie, 0, 0, 'L');
                $this->SetX($this->lMargin);
                $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
            }
        };
        $pdf->encabezado = $documento['titulo'];
        $pdf->proyecto = $documento['proyecto'];
        $pdf->pie = 'Impreso el ' . (new \DateTimeImmutable($metadatos['Generado']))->format('d/m/Y H:i') . ' por ' . explode(' (', $metadatos['Usuario'])[0];

        // Info dictionary. TCPDF copia estos campos a dc:title, dc:creator, dc:description y dc:subject del XMP.
        $pdf->SetTitle($documento['titulo'] . ' · ' . $documento['proyecto']);
        $pdf->SetAuthor($metadatos['Usuario']);
        $pdf->SetSubject($documento['titulo']);
        $pdf->SetKeywords(implode(', ', [$documento['proyecto'], $documento['titulo'], 'ID ' . $metadatos['ID de la petición']]));
        $pdf->SetCreator('Casos de Prueba ' . $metadatos['Versión de la app']);
        $xmp = Impresion::xmp($metadatos);
        $pdf->setExtraXMPRDF($xmp['rdf']);
        $pdf->setExtraXMPPdfaextension($xmp['esquema']);

        // Márgenes como @page: 2 cm arriba, 1.5 cm a los lados, 1.8 cm abajo.
        $pdf->SetMargins(42, 57, 42);
        $pdf->SetAutoPageBreak(true, 51);
        $pdf->SetFont('dejavusans', '', 9);
        // Menos aire entre bloques que el que TCPDF pone por defecto.
        $poco = [0 => ['h' => 0.4, 'n' => 1], 1 => ['h' => 0.4, 'n' => 1]];
        $pdf->setHtmlVSpace(['p' => $poco, 'div' => [0 => ['h' => 0, 'n' => 0], 1 => ['h' => 0, 'n' => 0]], 'table' => $poco, 'h1' => $poco, 'h2' => $poco, 'header' => [0 => ['h' => 0, 'n' => 0], 1 => ['h' => 0, 'n' => 0]], 'section' => $poco]);
        $pdf->AddPage();
        $pdf->writeHTML(self::limpiar($html) . self::firmas());

        $archivo = $pdf->Output('', 'S');
        $hash = hash('sha256', $archivo);
        Log::canal('app')->info('pdf_generado:{documento}', ['documento' => $documento['titulo'], 'proyecto' => $documento['proyecto'], 'sha256' => $hash, 'bytes' => strlen($archivo)]);
        // Nombre ASCII: sin acentos ni símbolos. strtr y no iconv: iconv no está en todos los PHP.
        $ascii = strtr($documento['titulo'] . ' ' . $documento['proyecto'], ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N']);

        return ['archivo' => $archivo, 'nombre' => trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $ascii), '-') . '.pdf', 'sha256' => $hash];
    }



    // Quita lo de QUITAR y muestra lo .solo-impresion y .solo-lector. TCPDF no entiende display: none por clase.
    private static function limpiar(string $html): string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8"><div id="raiz">' . $html . '</div>', LIBXML_NOERROR);
        $xpath = new \DOMXPath($dom);
        $clase = static fn (string $c): string => "contains(concat(' ', normalize-space(@class), ' '), ' {$c} ')";
        foreach (iterator_to_array($xpath->query('//*[' . implode(' or ', array_map($clase, self::QUITAR)) . '] | //svg | //form | //script | //template | //dialog')) as $nodo) {
            $nodo->parentNode?->removeChild($nodo);
        }

        // TCPDF dibuja bien las tablas: <dl> y cifras pasan a tabla, el <caption> sobra.
        foreach (iterator_to_array($dom->getElementsByTagName('caption')) as $c) {
            $c->parentNode?->removeChild($c);
        }
        foreach (iterator_to_array($dom->getElementsByTagName('dl')) as $dl) {
            self::reemplazar($dom, $dl, self::filasDe($dom, $dl));
        }
        foreach (iterator_to_array($xpath->query('//*[' . $clase('cifras') . ']')) as $cifras) {
            $celdas = '';
            foreach ($xpath->query('./*', $cifras) as $cifra) {
                $celdas .= '<td>' . $dom->saveHTML($cifra) . '</td>';
            }
            self::reemplazar($dom, $cifras, '<table border="1" cellpadding="4" width="100%"><tr>' . $celdas . '</tr></table>');
        }
        foreach (iterator_to_array($dom->getElementsByTagName('table')) as $tabla) {
            if (!$tabla->hasAttribute('cellpadding')) {
                $tabla->setAttribute('cellpadding', '3');
                $tabla->setAttribute('width', '100%');
            }
            // Tabla corta, menos de 15 filas: entera en una hoja, como en la impresión.
            if ($tabla->getElementsByTagName('tr')->length < 15) {
                $tabla->setAttribute('nobr', 'true');
            }
        }

        $raiz = $dom->getElementById('raiz');
        $salida = '';
        foreach ($raiz === null ? [] : $raiz->childNodes as $hijo) {
            $salida .= $dom->saveHTML($hijo);
        }

        return '<style>' . (string) file_get_contents(RAIZ . '/public/assets/css/pdf.css') . '</style>' . $salida;
    }

    // <dl>: una fila por <dt>, con sus <dd> en la segunda celda.
    private static function filasDe(\DOMDocument $dom, \DOMElement $dl): string
    {
        $interior = static function (\DOMElement $e) use ($dom): string {
            $html = '';
            foreach ($e->childNodes as $n) {
                $html .= $dom->saveHTML($n);
            }

            return $html;
        };
        $filas = [];
        // dt y dd en orden, también dentro de un <div> por par, como en .grafico-pie.
        foreach ((new \DOMXPath($dom))->query('.//dt | .//dd', $dl) as $hijo) {
            if ($hijo instanceof \DOMElement && $hijo->tagName === 'dt') {
                $filas[] = ['<th width="30%">' . $interior($hijo) . '</th>', ''];
            } elseif ($hijo instanceof \DOMElement && $hijo->tagName === 'dd' && $filas !== []) {
                $filas[array_key_last($filas)][1] .= $interior($hijo) . ' ';
            }
        }

        return '<table cellpadding="3" width="100%">' . implode('', array_map(fn (array $f): string => '<tr>' . $f[0] . '<td width="70%">' . $f[1] . '</td></tr>', $filas)) . '</table>';
    }

    private static function reemplazar(\DOMDocument $dom, \DOMNode $nodo, string $html): void
    {
        $nuevo = new \DOMDocument();
        @$nuevo->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_NOERROR);
        $div = $nuevo->getElementsByTagName('div')->item(0);
        foreach ($div === null ? [] : iterator_to_array($div->childNodes) as $n) {
            $nodo->parentNode?->insertBefore($dom->importNode($n, true), $nodo);
        }
        $nodo->parentNode?->removeChild($nodo);
    }

    private static function firmas(): string
    {
        $celda = static fn (string $rol): string => '<td class="firma"><br><br><br>______________________________<br><b>' . $rol . '</b><br>Nombre y firma<br>Fecha: ____ / ____ / ________</td>';

        return '<br><br><table class="firmas" cellpadding="4" nobr="true"><tr>' . $celda('Elaborado por') . $celda('Revisado por') . $celda('Aprobado por') . '</tr></table>';
    }
}
