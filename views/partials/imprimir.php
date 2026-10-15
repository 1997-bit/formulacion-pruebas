<?php
declare(strict_types=1);

use App\Helpers\Icono;

/**
 * Botón Imprimir de un documento imprimible. El formato de la hoja está en componentes.css, @media print.
 */
?>
<button class="btn btn-secundario" type="button" data-imprimir><?= Icono::svg('printer') ?> Imprimir</button>
