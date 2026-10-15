<?php
declare(strict_types=1);

use App\Helpers\Html;

/**
 * Solo al imprimir: datos del documento para un postmortem. Impresion::metadatos().
 *
 * @var array<string, string> $metadatos
 */
?>
<section class="solo-impresion metadatos" aria-label="Datos del documento">
  <h2>Datos del documento</h2>
  <dl>
    <?php foreach ($metadatos as $etiqueta => $valor): ?>
      <dt><?= Html::e($etiqueta) ?></dt><dd><?= Html::e($valor) ?></dd>
    <?php endforeach; ?>
  </dl>
</section>
