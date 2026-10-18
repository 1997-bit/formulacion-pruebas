<?php
declare(strict_types=1);

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
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash, 'errores' => $errores]) ?>
  <?php if (!$usuarios): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>Todavía no hay usuarios</h2>
      <p>Cree la primera cuenta para que pueda entrar al sistema.</p>
      <a class="btn btn-primario" href="/admin/usuarios/crear"><?= Icono::svg('plus') ?> Crear usuario</a>
    </div>
  <?php else: ?>
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
                  <?= Vista::capturar('partials/eliminar', [
                      'dialogo' => 'dlg-eliminar-' . (int) $u['id'], 'etiqueta' => 'Eliminar ' . $u['usuario'], 'accion' => '/admin/usuarios/eliminar', 'id' => (int) $u['id'],
                      'titulo' => '¿Eliminar a ' . $u['nombre'] . '?', 'confirmar' => (string) $u['usuario'], 'clase' => 'btn-fantasma',
                      'texto' => 'Si tiene casos, evidencias o formularios a su nombre no se elimina. Esta acción no se puede deshacer.',
                  ]) ?>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
