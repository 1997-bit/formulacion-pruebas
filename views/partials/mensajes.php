<?php
declare(strict_types=1);

use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var ?string $flash
 * @var ?array{texto: string, ruta: string} $accion  siguiente paso. Con acción el aviso no se cierra solo
 * @var ?array<string, string> $errores
 */
?>
<?php if (!empty($flash)): ?>
  <?php // app.js cierra a los 5 s, salvo con acción. Sin JS queda ?>
  <div class="alerta alerta-exito aviso-flotante" role="status"><?= Icono::svg('circle-check') ?>
    <p class="alerta-titulo"><?= Html::e($flash) ?></p>
    <?php if (!empty($accion)): ?>
      <p class="alerta-descripcion"><a href="<?= Html::e($accion['ruta']) ?>" data-aviso-accion><?= Html::e($accion['texto']) ?></a></p>
    <?php endif; ?>
    <button class="btn btn-fantasma btn-icono btn-sm alerta-accion" type="button" data-cerrar-aviso aria-label="Cerrar aviso" data-tooltip="Cerrar" data-tooltip-alinear="fin"><?= Icono::svg('x') ?></button>
  </div>
<?php endif; ?>
<?php if (!empty($errores)): ?>
  <div class="alerta alerta-error" role="alert"><?= Icono::svg('circle-alert') ?>
    <p class="alerta-titulo"><?= Html::e($errores['general'] ?? 'Revise los campos marcados.') ?></p>
  </div>
<?php endif; ?>
