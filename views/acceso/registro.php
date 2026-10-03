<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var array<string, string> $errores
 * @var array<string, string> $datos
 */

$campos = [
    ['nombre', 'Nombre completo', 'text', 'name', 100],
    ['usuario', 'Usuario', 'text', 'username', 30],
    ['clave', 'Contraseña', 'password', 'new-password', null],
];
?>
<form class="tarjeta pila" method="post" action="/registro" aria-labelledby="ac-titulo">
  <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
  <header class="tarjeta-encabezado" style="margin:0">
    <h1 class="tarjeta-titulo" id="ac-titulo">Crear cuenta</h1>
    <p class="tarjeta-descripcion">La cuenta nueva tiene rol general.</p>
  </header>
  <?php foreach ($campos as [$campo, $etiqueta, $tipo, $autocompletar, $maximo]): ?>
    <?php $error = $errores[$campo] ?? null; ?>
    <div class="campo">
      <label for="ac-<?= $campo ?>"><?= $etiqueta ?></label>
      <input class="control" id="ac-<?= $campo ?>" name="<?= $campo ?>" type="<?= $tipo ?>" autocomplete="<?= $autocompletar ?>" required
             <?= $maximo ? 'maxlength="' . $maximo . '"' : 'minlength="8"' ?>
             <?= $tipo !== 'password' ? 'value="' . Html::e($datos[$campo] ?? '') . '"' : '' ?>
             <?= $error ? 'aria-invalid="true" aria-describedby="ac-' . $campo . '-error"' : '' ?>>
      <?php if ($error): ?>
        <p class="campo-error" id="ac-<?= $campo ?>-error"><?= Icono::svg('circle-x') ?><?= Html::e($error) ?></p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <button class="btn btn-primario btn-bloque" type="submit">Crear cuenta</button>
</form>
<p class="acceso-pie">¿Ya tiene cuenta? <a href="/">Iniciar sesión</a></p>
