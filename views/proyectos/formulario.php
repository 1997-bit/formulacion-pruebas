<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Crear y editar, con miembros.
 *
 * @var ?int $id
 * @var list<array<string, mixed>> $testers
 * @var array<string, string> $errores
 * @var array{nombre?: string, descripcion?: string, miembros: list<string>} $datos
 */

$campo = fn (string $nombre, string $etiqueta, array $extra = []): string => Vista::capturar('partials/campo', $extra + [
    'nombre' => $nombre,
    'etiqueta' => $etiqueta,
    'valor' => $datos[$nombre] ?? '',
    'error' => $errores[$nombre] ?? '',
]);
$accion = $id === null ? '/admin/proyectos/crear' : '/admin/proyectos/editar';
?>
<header class="encabezado">
  <div>
    <h1><?= $id === null ? 'Crear proyecto' : 'Editar proyecto' ?></h1>
  </div>
</header>
<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
  <form class="tarjeta pila" method="post" action="<?= $accion ?>">
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <?php if ($id !== null): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
    <div class="campos">
      <?= $campo('nombre', 'Nombre', ['requerido' => true, 'atributos' => 'maxlength="100" autocomplete="off"']) ?>
      <?= $campo('descripcion', 'Descripción', ['tipo' => 'textarea']) ?>
    </div>

    <fieldset class="grupo"<?= isset($errores['miembros']) ? ' aria-describedby="f-miembros-error"' : '' ?>>
      <legend>Miembros</legend>
      <?php foreach ($testers as $t): ?>
        <label class="opcion"><input type="checkbox" name="miembros[]" value="<?= (int) $t['id'] ?>"<?= in_array((string) $t['id'], $datos['miembros'], true) ? ' checked' : '' ?>> <?= Html::e($t['nombre']) ?> <span class="codigo"><?= Html::e($t['usuario']) ?></span></label>
      <?php endforeach; ?>
      <?php if ($testers === []): ?><p class="campo-ayuda">No hay testers.</p><?php endif; ?>
      <?php if (isset($errores['miembros'])): ?>
        <p class="campo-error" id="f-miembros-error"><?= Icono::svg('circle-x') ?><?= Html::e($errores['miembros']) ?></p>
      <?php endif; ?>
    </fieldset>

    <p class="campo-ayuda"><span class="requerido" aria-hidden="true">*</span> Campo obligatorio</p>
    <div class="acciones">
      <a class="btn btn-secundario" href="/admin/proyectos">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar proyecto</button>
    </div>
  </form>
</div>
