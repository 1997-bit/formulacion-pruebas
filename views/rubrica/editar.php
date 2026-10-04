<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;
use App\Services\RubricaServicio;

/**
 * Formulario 7 (RF-14) para llenar o editar. El total se ve al elegir; el servidor lo recalcula.
 *
 * @var array<string, mixed> $proyecto
 * @var bool $nueva
 * @var array<int|string, int|string> $puntos  criterio => puntos
 * @var array<string, string> $errores
 */

// Puntos => descriptor: 5, 4, 3 y 1-2.
$niveles = [5 => 0, 4 => 1, 3 => 2, 2 => 3, 1 => 3];
?>
<header class="encabezado">
  <div>
    <p class="antetitulo"><?= Html::e($proyecto['nombre']) ?></p>
    <h1><?= $nueva ? 'Llenar' : 'Editar' ?> rúbrica de evaluación</h1>
    <p>De 1 a 5: 5 excelente, 4 bueno, 3 regular, 1 o 2 deficiente.</p>
  </div>
</header>

<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
  <form class="tarjeta pila" method="post" action="/formularios/rubrica/editar">
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <input type="hidden" name="proyecto_id" value="<?= (int) $proyecto['id'] ?>">
    <div class="tabla-contenedor">
      <table class="tabla tabla-editable tabla-tarjetas">
        <caption class="solo-lector">Puntos por criterio</caption>
        <thead><tr><th scope="col">Criterio</th><th scope="col">Puntos</th></tr></thead>
        <tbody>
          <?php foreach (Catalogo::valores('criterio_rubrica') as $clave => $c): ?>
            <?php
            $elegido = (string) ($puntos[$clave] ?? '');
            $error = $errores["puntos.{$clave}"] ?? '';
            $id = "f-puntos-{$clave}";
            ?>
            <tr>
              <th scope="row"><?= Html::e($c['texto']) ?></th>
              <td data-columna="Puntos">
                <div class="select"><select class="control" id="<?= $id ?>" name="puntos[<?= $clave ?>]" data-grupo="rubrica" required
                  aria-label="Puntos de <?= Html::e(mb_strtolower($c['texto'])) ?>"<?= $error !== '' ? ' aria-invalid="true" aria-describedby="' . $id . '-error"' : '' ?>>
                  <option value="">Elegir…</option>
                  <?php foreach ($niveles as $valor => $n): ?>
                    <option value="<?= $valor ?>"<?= (string) $valor === $elegido ? ' selected' : '' ?>><?= $valor ?> · <?= Html::e($c['niveles'][$n] ?? '') ?></option>
                  <?php endforeach; ?>
                </select></div>
                <?php if ($error !== ''): ?><p class="campo-error" id="<?= $id ?>-error"><?= Icono::svg('circle-x') ?><?= Html::e($error) ?></p><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><th scope="row">Total</th><td data-columna="Total"><output data-suma="rubrica">0</output> / <?= RubricaServicio::MAXIMO ?></td></tr>
        </tfoot>
      </table>
    </div>
    <p class="campo-ayuda">Todos los criterios son obligatorios. El servidor recalcula el total al guardar.</p>
    <div class="acciones">
      <a class="btn btn-secundario" href="/formularios/rubrica?proyecto=<?= (int) $proyecto['id'] ?>">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar rúbrica</button>
    </div>
  </form>
</div>
