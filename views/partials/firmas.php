<?php
declare(strict_types=1);

/**
 * Solo al imprimir: tres líneas de firma al final del documento.
 */
?>
<section class="solo-impresion firmas" aria-label="Firmas">
  <?php foreach (['Elaborado por', 'Revisado por', 'Aprobado por'] as $rol): ?>
    <div class="firma">
      <span class="firma-linea"></span>
      <p><strong><?= $rol ?></strong></p>
      <p>Nombre y firma</p>
      <p>Fecha: ____ / ____ / ________</p>
    </div>
  <?php endforeach; ?>
</section>
