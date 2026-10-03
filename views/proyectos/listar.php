<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var list<array<string, mixed>> $proyectos
 * @var ?string $flash
 * @var array<string, string> $errores
 */
?>
<header class="encabezado">
  <div>
    <h1>Proyectos</h1>
    <p>Un tester solo ve los proyectos donde es miembro.</p>
  </div>
  <div class="acciones"><a class="btn btn-primario" href="/admin/proyectos/crear"><?= Icono::svg('plus') ?> Crear proyecto</a></div>
</header>
<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash]) ?>
  <?php if (isset($errores['general'])): ?>
    <div class="alerta alerta-error" role="alert"><?= Icono::svg('circle-alert') ?>
      <p class="alerta-titulo"><?= Html::e($errores['general']) ?></p>
    </div>
  <?php endif; ?>
  <div class="tabla-contenedor">
    <table class="tabla tabla-tarjetas">
      <caption class="solo-lector">Proyectos</caption>
      <thead>
        <tr><th scope="col">Nombre</th><th scope="col">Miembros</th><th scope="col">Creado</th><th scope="col"><span class="solo-lector">Acciones</span></th></tr>
      </thead>
      <tbody>
        <?php foreach ($proyectos as $p): ?>
          <tr>
            <td data-columna="Nombre"><?= Html::e($p['nombre']) ?></td>
            <td data-columna="Miembros"><?= Html::e($p['miembros'] ?? 'Sin miembros') ?></td>
            <td data-columna="Creado"><?= Fecha::legible(new DateTime($p['creado_en'])) ?></td>
            <td class="celda-acciones">
              <div class="acciones">
                <a class="btn btn-fantasma btn-icono" href="/admin/proyectos/editar?id=<?= (int) $p['id'] ?>" aria-label="Editar <?= Html::e($p['nombre']) ?>" data-tooltip="Editar"><?= Icono::svg('pencil') ?></a>
                <button class="btn btn-fantasma btn-icono" type="button" data-abrir-dialogo="dlg-eliminar-<?= (int) $p['id'] ?>" aria-label="Eliminar <?= Html::e($p['nombre']) ?>" data-tooltip="Eliminar" data-tooltip-alinear="fin"><?= Icono::svg('trash-2') ?></button>
                <dialog class="dialogo" id="dlg-eliminar-<?= (int) $p['id'] ?>" aria-labelledby="dlg-eliminar-<?= (int) $p['id'] ?>-titulo">
                  <h2 id="dlg-eliminar-<?= (int) $p['id'] ?>-titulo">¿Eliminar <?= Html::e($p['nombre']) ?>?</h2>
                  <p>Si tiene requerimientos o casos no se elimina. Esta acción no se puede deshacer.</p>
                  <form method="post" action="/admin/proyectos/eliminar" class="acciones">
                    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button class="btn btn-secundario" type="submit" formmethod="dialog" autofocus>Cancelar</button>
                    <button class="btn btn-peligro" type="submit">Eliminar</button>
                  </form>
                </dialog>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
