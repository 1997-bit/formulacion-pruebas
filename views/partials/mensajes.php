<?php
declare(strict_types=1);

use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Aviso de éxito tras guardar y resumen cuando el formulario volvió con errores.
 *
 * @var ?string $flash
 * @var ?array<string, string> $errores
 */
?>
<?php if (!empty($flash)): ?>
  <div class="alerta alerta-exito" role="status"><?= Icono::svg('circle-check') ?>
    <p class="alerta-titulo"><?= Html::e($flash) ?></p>
  </div>
<?php endif; ?>
<?php if (!empty($errores)): ?>
  <div class="alerta alerta-error" role="alert"><?= Icono::svg('circle-alert') ?>
    <p class="alerta-titulo">Revise los campos marcados.</p>
  </div>
<?php endif; ?>
