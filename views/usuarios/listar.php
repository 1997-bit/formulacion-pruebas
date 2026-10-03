<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var list<array<string, mixed>> $usuarios
 * @var array<string, mixed> $usuario
 * @var ?string $flash
 * @var array<string, string> $errores
 */
?>
<header class="encabezado">
  <div>
    <h1>Usuarios</h1>
    <p>Cuentas del sistema. El rol se cambia al editar.</p>
  </div>
  <div class="acciones"><a class="btn btn-primario" href="/admin/usuarios/crear"><?= Icono::svg('plus') ?> Crear usuario</a></div>
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
      <caption class="solo-lector">Usuarios</caption>
      <thead>
        <tr><th scope="col">Nombre</th><th scope="col">Usuario</th><th scope="col">Rol</th><th scope="col">Creado</th><th scope="col"><span class="solo-lector">Acciones</span></th></tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td data-columna="Nombre"><?= Html::e($u['nombre']) ?></td>
            <td data-columna="Usuario"><span class="codigo"><?= Html::e($u['usuario']) ?></span></td>
            <td data-columna="Rol"><?= Catalogo::insignia('rol', $u['rol']) ?></td>
            <td data-columna="Creado"><?= Fecha::legible(new DateTime($u['creado_en'])) ?></td>
            <td class="celda-acciones">
              <div class="acciones">
                <a class="btn btn-fantasma btn-icono" href="/admin/usuarios/editar?id=<?= (int) $u['id'] ?>" aria-label="Editar <?= Html::e($u['usuario']) ?>" data-tooltip="Editar"><?= Icono::svg('pencil') ?></a>
                <?php if ($u['id'] !== $usuario['id']): ?>
                  <button class="btn btn-fantasma btn-icono" type="button" data-abrir-dialogo="dlg-eliminar-<?= (int) $u['id'] ?>" aria-label="Eliminar <?= Html::e($u['usuario']) ?>" data-tooltip="Eliminar" data-tooltip-alinear="fin"><?= Icono::svg('trash-2') ?></button>
                  <dialog class="dialogo" id="dlg-eliminar-<?= (int) $u['id'] ?>" aria-labelledby="dlg-eliminar-<?= (int) $u['id'] ?>-titulo">
                    <h2 id="dlg-eliminar-<?= (int) $u['id'] ?>-titulo">¿Eliminar a <?= Html::e($u['nombre']) ?>?</h2>
                    <p>Si tiene casos, evidencias o formularios a su nombre no se elimina. Esta acción no se puede deshacer.</p>
                    <form method="post" action="/admin/usuarios/eliminar" class="acciones">
                      <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
                      <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                      <button class="btn btn-secundario" type="submit" formmethod="dialog" autofocus>Cancelar</button>
                      <button class="btn btn-peligro" type="submit">Eliminar</button>
                    </form>
                  </dialog>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
