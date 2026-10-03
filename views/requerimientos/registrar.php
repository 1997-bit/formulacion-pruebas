<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var list<array<string, mixed>> $proyectos
 * @var array<string, string> $errores
 * @var array<string, string> $datos
 */

$v = fn (string $campo): string => Html::e($datos[$campo] ?? '');
// aria-invalid y aria-describedby: enlaza la ayuda y, si hay, el error (RNF-07).
$aria = function (string $campo, string $ayuda = '') use ($errores): string {
    $ids = array_filter([isset($errores[$campo]) ? "e-{$campo}" : '', $ayuda]);

    return (isset($errores[$campo]) ? ' aria-invalid="true"' : '')
        . ($ids ? ' aria-describedby="' . implode(' ', $ids) . '"' : '');
};
$error = fn (string $campo): string => isset($errores[$campo])
    ? '<p class="campo-error" id="e-' . $campo . '">' . Icono::svg('circle-x') . Html::e($errores[$campo]) . '</p>'
    : '';
?>
<header class="encabezado">
  <div>
    <h1>Registrar requerimiento</h1>
  </div>
</header>
<div class="pila">
  <?php if ($errores): ?>
    <div class="alerta alerta-error" role="alert"><?= Icono::svg('circle-alert') ?>
      <p class="alerta-titulo">Revise los campos marcados.</p>
    </div>
  <?php endif; ?>
  <form class="tarjeta pila" method="post" action="/requerimientos/registrar">
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <div class="campos">
      <div class="campo">
        <label for="r-proyecto">Proyecto <span class="requerido" aria-hidden="true">*</span></label>
        <div class="select">
          <select class="control" id="r-proyecto" name="proyecto_id" required<?= $aria('proyecto_id') ?>>
            <option value="">Elegir…</option>
            <?php foreach ($proyectos as $p): ?>
              <option value="<?= $p['id'] ?>"<?= (string) $p['id'] === ($datos['proyecto_id'] ?? '') ? ' selected' : '' ?>><?= Html::e($p['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?= $error('proyecto_id') ?>
      </div>
      <div class="campo">
        <label for="r-codigo">Código <span class="requerido" aria-hidden="true">*</span></label>
        <input class="control codigo" id="r-codigo" name="codigo" required maxlength="10" placeholder="RF-25" value="<?= $v('codigo') ?>"<?= $aria('codigo', 'r-codigo-ayuda') ?>>
        <p class="campo-ayuda" id="r-codigo-ayuda">RF-01 si es funcional, RNF-01 si es no funcional.</p>
        <?= $error('codigo') ?>
      </div>
    </div>
    <div class="campo">
      <label for="r-desc">Descripción <span class="requerido" aria-hidden="true">*</span></label>
      <textarea class="control" id="r-desc" name="descripcion" required<?= $aria('descripcion', 'r-desc-ayuda') ?>><?= $v('descripcion') ?></textarea>
      <p class="campo-ayuda" id="r-desc-ayuda">Qué debe hacer o cumplir el sistema.</p>
      <?= $error('descripcion') ?>
    </div>

    <p class="campo-ayuda"><span class="requerido" aria-hidden="true">*</span> Campo obligatorio</p>
    <div class="acciones">
      <a class="btn btn-secundario" href="/requerimientos/listar">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar requerimiento</button>
    </div>
  </form>
</div>
