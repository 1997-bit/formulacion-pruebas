<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Html;

/**
 * @var list<array<string, mixed>> $proyectos
 * @var array<string, string> $errores
 * @var array<string, string> $datos
 */

$campo = fn (string $nombre, string $etiqueta, array $extra = []): string => Vista::capturar('partials/campo', [
    'nombre' => $nombre,
    'etiqueta' => $etiqueta,
    'valor' => $datos[$nombre] ?? '',
    'error' => $errores[$nombre] ?? '',
    'requerido' => true,
] + $extra);
$opciones = '';
foreach ($proyectos as $p) {
    $elegido = (string) $p['id'] === ($datos['proyecto_id'] ?? '') ? ' selected' : '';
    $opciones .= '<option value="' . $p['id'] . '"' . $elegido . '>' . Html::e($p['nombre']) . '</option>';
}
?>
<header class="encabezado">
  <div>
    <h1>Registrar requerimiento</h1>
  </div>
</header>
<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
  <form class="tarjeta pila" method="post" action="/requerimientos/registrar">
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <div class="campos">
      <?= $campo('proyecto_id', 'Proyecto', ['tipo' => 'select', 'opciones' => $opciones]) ?>
      <?= $campo('codigo', 'Código', [
          'clase' => 'codigo',
          'atributos' => 'maxlength="10" placeholder="RF-25"',
          'ayuda' => 'RF-01 si es funcional, RNF-01 si es no funcional.',
      ]) ?>
    </div>
    <?= $campo('descripcion', 'Descripción', ['tipo' => 'textarea', 'ayuda' => 'Qué debe hacer o cumplir el sistema.']) ?>

    <p class="campo-ayuda"><span class="requerido" aria-hidden="true">*</span> Campo obligatorio</p>
    <div class="acciones">
      <a class="btn btn-secundario" href="/requerimientos/listar">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar requerimiento</button>
    </div>
  </form>
</div>
