<?php
declare(strict_types=1);

use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Etiqueta, control, ayuda y error enlazados con aria-describedby (RNF-07).
 *
 * @var string $nombre
 * @var string $etiqueta
 * @var ?string $id         por defecto f-{nombre}
 * @var ?string $tipo       text, date, url, file, textarea o select
 * @var ?string $valor
 * @var ?string $opciones   <option> ya armadas, solo para select
 * @var ?string $ayuda
 * @var ?string $error
 * @var ?bool $requerido
 * @var ?string $atributos  extra: maxlength, placeholder, accept…
 * @var ?string $clase      extra para el control, como codigo
 */

$id ??= 'f-' . $nombre;
$tipo ??= 'text';
$ayuda ??= '';
$error ??= '';
$requerido ??= false;
$descrito = array_filter([$error !== '' ? "{$id}-error" : '', $ayuda !== '' ? "{$id}-ayuda" : '']);
$attrs = 'class="control' . (isset($clase) ? ' ' . Html::e($clase) : '') . '" id="' . Html::e($id) . '" name="' . Html::e($nombre) . '"'
    . ($requerido ? ' required' : '')
    . ($error !== '' ? ' aria-invalid="true"' : '')
    . ($descrito ? ' aria-describedby="' . implode(' ', $descrito) . '"' : '')
    . (isset($atributos) ? ' ' . $atributos : '');
?>
<div class="campo">
  <label for="<?= Html::e($id) ?>"><?= Html::e($etiqueta) ?><?php if ($requerido): ?> <span class="requerido" aria-hidden="true">*</span><?php endif; ?></label>
  <?php if ($tipo === 'textarea'): ?>
    <textarea <?= $attrs ?>><?= Html::e($valor ?? '') ?></textarea>
  <?php elseif ($tipo === 'select'): ?>
    <div class="select">
      <select <?= $attrs ?>>
        <option value="">Elegir…</option>
        <?= $opciones ?? '' ?>
      </select>
    </div>
  <?php else: ?>
    <input <?= $attrs ?> type="<?= Html::e($tipo) ?>"<?= $tipo !== 'file' ? ' value="' . Html::e($valor ?? '') . '"' : '' ?>>
  <?php endif; ?>
  <?php if ($ayuda !== ''): ?>
    <p class="campo-ayuda" id="<?= Html::e($id) ?>-ayuda"><?= Html::e($ayuda) ?></p>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <p class="campo-error" id="<?= Html::e($id) ?>-error"><?= Icono::svg('circle-x') ?><?= Html::e($error) ?></p>
  <?php endif; ?>
</div>
