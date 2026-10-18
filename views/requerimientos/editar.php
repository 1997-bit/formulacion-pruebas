<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Change request básico: motivo obligatorio, casos afectados e historial.
 *
 * @var array<string, mixed> $requerimiento
 * @var list<array<string, mixed>> $casos
 * @var list<array<string, mixed>> $historial  Historial::de()
 * @var string $huella  #100
 * @var array<string, string> $errores
 * @var array<string, string> $datos
 * @var array{rol: int} $usuario
 */

$campo = fn (string $nombre, string $etiqueta, array $extra = []): string => Vista::capturar('partials/campo', $extra + [
    'nombre' => $nombre,
    'etiqueta' => $etiqueta,
    'valor' => $datos[$nombre] ?? '',
    'error' => $errores[$nombre] ?? '',
    'requerido' => true,
]);
$codigo = (string) $requerimiento['codigo'];
?>
<header class="encabezado">
  <div>
    <h1>Editar <?= Html::e($codigo) ?></h1>
    <p><?= Html::e($requerimiento['proyecto']) ?></p>
  </div>
  <?php if ($usuario['rol'] === 1): ?>
    <div class="acciones">
      <?= Vista::capturar('partials/eliminar', [
          'dialogo' => 'dlg-eliminar', 'etiqueta' => 'Eliminar requerimiento', 'accion' => '/requerimientos/eliminar', 'id' => (int) $requerimiento['id'],
          'titulo' => '¿Eliminar el requerimiento ' . $codigo . '?', 'confirmar' => $codigo,
          'texto' => 'Si tiene casos o formularios de técnicas no se elimina. Esta acción no se puede deshacer.',
      ]) ?>
    </div>
  <?php endif; ?>
</header>
<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
  <form class="tarjeta pila" method="post" action="/requerimientos/editar">
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <input type="hidden" name="envio" value="<?= Csrf::envio() ?>">
    <input type="hidden" name="id" value="<?= (int) $requerimiento['id'] ?>">
    <input type="hidden" name="huella" value="<?= Html::e($huella) ?>">
    <div class="campos">
      <?= $campo('codigo', 'Código', [
          'clase' => 'codigo',
          'atributos' => 'maxlength="10" placeholder="RF-25"',
          'ayuda' => 'RF-01 si es funcional, RNF-01 si es no funcional.',
      ]) ?>
    </div>
    <?= $campo('descripcion', 'Descripción', ['tipo' => 'textarea', 'ayuda' => 'Qué debe hacer o cumplir el sistema.']) ?>
    <?= $campo('motivo', 'Motivo del cambio', ['tipo' => 'textarea', 'ayuda' => 'Por qué cambia. Queda en el historial.']) ?>

    <p class="campo-ayuda"><span class="requerido" aria-hidden="true">*</span> Campo obligatorio</p>
    <div class="acciones">
      <a class="btn btn-secundario" href="/requerimientos/listar">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar cambios</button>
    </div>
  </form>

  <section class="pila" aria-labelledby="h-casos">
    <h2 class="titulo-seccion" id="h-casos">Casos afectados (<?= count($casos) ?>)</h2>
    <?php if (!$casos): ?>
      <p class="campo-ayuda">Ningún caso valida este requerimiento.</p>
    <?php else: ?>
      <p class="campo-ayuda">Revise estos casos después del cambio.</p>
      <div class="tabla-contenedor">
        <table class="tabla tabla-tarjetas">
          <caption class="solo-lector">Casos afectados</caption>
          <thead><tr><th scope="col">Caso</th><th scope="col">Estado</th></tr></thead>
          <tbody>
            <?php foreach ($casos as $c): ?>
              <tr>
                <td data-columna="Caso"><a href="/casos/resultado?id=<?= (int) $c['id'] ?>"><span class="codigo"><?= Html::e($c['codigo']) ?></span></a></td>
                <td data-columna="Estado"><?= Catalogo::insignia('estado_caso', $c['estado']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="pila" aria-labelledby="h-historial">
    <h2 class="titulo-seccion" id="h-historial">Historial</h2>
    <?= Vista::capturar('partials/historial', ['historial' => $historial, 'codigo' => $codigo]) ?>
  </section>
</div>
