<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var array<string, string> $errores
 * @var array<string, string> $datos
 * @var ?string $flash
 */
?>
<form class="tarjeta pila" method="post" action="/" aria-labelledby="ac-titulo">
  <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
  <header class="tarjeta-encabezado" style="margin:0">
    <h1 class="tarjeta-titulo" id="ac-titulo">Iniciar sesión</h1>
    <p class="tarjeta-descripcion">Entre con su usuario y contraseña.</p>
  </header>
  <?php if ($flash): ?>
    <div class="alerta alerta-exito" role="status"><?= Icono::svg('circle-check') ?>
      <p class="alerta-titulo"><?= Html::e($flash) ?></p>
    </div>
  <?php endif; ?>
  <?php if (isset($errores['general'])): ?>
    <div class="alerta alerta-error" role="alert"><?= Icono::svg('circle-alert') ?>
      <p class="alerta-titulo"><?= Html::e($errores['general']) ?></p>
    </div>
  <?php endif; ?>
  <div class="campo">
    <label for="ac-usuario">Usuario</label>
    <input class="control" id="ac-usuario" name="usuario" autocomplete="username" required value="<?= Html::e($datos['usuario'] ?? '') ?>">
  </div>
  <div class="campo">
    <label for="ac-clave">Contraseña</label>
    <input class="control" id="ac-clave" name="clave" type="password" autocomplete="current-password" required>
  </div>
  <button class="btn btn-primario btn-bloque" type="submit">Iniciar sesión</button>
</form>
<p class="acceso-pie">¿No tiene cuenta? <a href="/registro">Crear cuenta</a></p>
