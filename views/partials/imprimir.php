<?php
declare(strict_types=1);

use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Botones de un documento imprimible: Imprimir del navegador y PDF con metadatos, Vista::pagina con ?formato=pdf.
 */

$consulta = $_GET;
$consulta['formato'] = 'pdf';
$pdf = (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/') . '?' . http_build_query($consulta);
?>
<button class="btn btn-secundario" type="button" data-imprimir><?= Icono::svg('printer') ?> Imprimir</button>
<a class="btn btn-secundario" href="<?= Html::e($pdf) ?>" download><?= Icono::svg('download') ?> Descargar PDF</a>
