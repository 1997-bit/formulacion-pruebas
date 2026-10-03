<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;

/**
 * Crear y editar. La clave nunca llega aquí.
 *
 * @var ?int $id
 * @var string $titulo
 * @var array<string, string> $errores
 * @var array<string, string> $datos
 */

$campo = fn (string $nombre, string $etiqueta, array $extra = []): string => Vista::capturar('partials/campo', $extra + [
    'nombre' => $nombre,
    'etiqueta' => $etiqueta,
    'valor' => $datos[$nombre] ?? '',
    'error' => $errores[$nombre] ?? '',
    'requerido' => true,
]);
$accion = $id === null ? '/admin/usuarios/crear' : '/admin/usuarios/editar';
?>
<header class="encabezado">
  <div>
    <h1><?= $id === null ? 'Crear usuario' : 'Editar usuario' ?></h1>
  </div>
</header>
<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
  <form class="tarjeta pila" method="post" action="<?= $accion ?>">
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <?php if ($id !== null): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
    <div class="campos">
      <?= $campo('nombre', 'Nombre completo', ['atributos' => 'maxlength="100" autocomplete="off"']) ?>
      <?= $campo('usuario', 'Usuario', ['atributos' => 'maxlength="30" autocomplete="off"']) ?>
      <?= $campo('clave', $id === null ? 'Contraseña' : 'Nueva contraseña', [
          'tipo' => 'password',
          'valor' => '',
          'requerido' => $id === null,
          'atributos' => 'minlength="8" autocomplete="new-password"',
          'ayuda' => $id === null ? 'Mínimo 8 caracteres.' : 'Déjela vacía para no cambiarla.',
      ]) ?>
      <?= $campo('rol', 'Rol', ['tipo' => 'select', 'opciones' => Catalogo::opciones('rol', $datos['rol'] ?? null), 'ayuda' => 'Un admin gestiona usuarios, proyectos y la rúbrica.']) ?>
    </div>

    <p class="campo-ayuda"><span class="requerido" aria-hidden="true">*</span> Campo obligatorio</p>
    <div class="acciones">
      <a class="btn btn-secundario" href="/admin/usuarios">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar usuario</button>
    </div>
  </form>
</div>
