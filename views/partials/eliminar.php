<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Eliminar con diálogo: escribir $confirmar. Pegar está permitido
 * $bloqueo: deshabilitado y el tooltip dice por qué
 *
 * @var string $dialogo    id del dialog
 * @var string $etiqueta   aria-label del botón, como Eliminar caso
 * @var string $titulo
 * @var string $texto
 * @var string $accion     URL del POST
 * @var int $id
 * @var string $confirmar  código o nombre a escribir
 * @var ?string $bloqueo
 * @var ?string $clase     clase del botón, por defecto btn-secundario
 */

$clase ??= 'btn-secundario';
$bloqueo ??= '';
// pattern: regex de JS con bandera v. Escape solo de la sintaxis
$patron = preg_replace('/[\\\\^$.*+?()[\]{}|\/]/', '\\\\$0', $confirmar);
?>
<?php if ($bloqueo !== ''): ?>
  <span class="btn <?= Html::e($clase) ?> btn-icono" aria-disabled="true" role="button" tabindex="0" aria-label="<?= Html::e($etiqueta . ': ' . $bloqueo) ?>" data-tooltip="<?= Html::e($bloqueo) ?>" data-tooltip-alinear="fin"><?= Icono::svg('trash-2') ?></span>
<?php else: ?>
  <button class="btn <?= Html::e($clase) ?> btn-icono" type="button" command="show-modal" commandfor="<?= Html::e($dialogo) ?>" aria-label="<?= Html::e($etiqueta) ?>" data-tooltip="Eliminar" data-tooltip-alinear="fin"><?= Icono::svg('trash-2') ?></button>
  <dialog closedby="any" class="dialogo" id="<?= Html::e($dialogo) ?>" aria-labelledby="<?= Html::e($dialogo) ?>-titulo">
    <h2 id="<?= Html::e($dialogo) ?>-titulo"><?= Html::e($titulo) ?></h2>
    <p><?= Html::e($texto) ?></p>
    <form class="pila" method="post" action="<?= Html::e($accion) ?>">
      <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="campo">
        <label for="<?= Html::e($dialogo) ?>-confirmar">Para confirmar, escriba <strong class="codigo"><?= Html::e($confirmar) ?></strong></label>
        <input class="control" id="<?= Html::e($dialogo) ?>-confirmar" type="text" required autocomplete="off" spellcheck="false"
               pattern="<?= Html::e($patron) ?>" title="Escriba <?= Html::e($confirmar) ?> tal cual">
      </div>
      <div class="acciones">
        <button class="btn btn-secundario" type="submit" formmethod="dialog" formnovalidate autofocus>Cancelar</button>
        <button class="btn btn-peligro" type="submit">Eliminar</button>
      </div>
    </form>
  </dialog>
<?php endif; ?>
