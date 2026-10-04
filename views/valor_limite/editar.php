<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Html;
use App\Helpers\Icono;
use App\Services\ValorLimiteServicio;

/**
 * Formulario 3 (RF-10) para crear o editar. Se guardan todas las filas o ninguna.
 *
 * @var array<string, mixed> $requerimiento
 * @var list<array<string, mixed>> $filas
 * @var array<string, string> $errores
 */

$columnas = ValorLimiteServicio::COLUMNAS;
?>
<header class="encabezado">
  <div>
    <p class="antetitulo fila"><span class="codigo"><?= Html::e($requerimiento['codigo']) ?></span> <?= Html::e($requerimiento['proyecto']) ?></p>
    <h1><?= $filas ? 'Editar' : 'Crear' ?> análisis de valor límite</h1>
    <p><?= Html::e($requerimiento['descripcion']) ?></p>
  </div>
</header>

<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
  <form class="tarjeta pila" method="post" action="/formularios/valor_limite/editar">
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <input type="hidden" name="requerimiento_id" value="<?= (int) $requerimiento['id'] ?>">
    <p class="campo-ayuda" id="vl-ayuda">Una fila por campo con rango: números, fechas o largos. Pruebe cada frontera, el valor justo antes y el justo después: con 18 a 65, pruebe 17, 18, 19, 64, 65 y 66.</p>
    <div class="tabla-contenedor">
      <table class="tabla tabla-editable tabla-tarjetas" aria-describedby="vl-ayuda">
        <caption class="solo-lector">Valores frontera por campo</caption>
        <thead>
          <tr>
            <?php foreach ($columnas as [$etiqueta]): ?><th scope="col"><?= Html::e($etiqueta) ?></th><?php endforeach; ?>
            <th scope="col" class="celda-acciones"><span class="solo-lector">Acciones</span></th>
          </tr>
        </thead>
        <tbody id="limites">
          <?php foreach ($filas ?: [[]] as $i => $fila): ?>
            <?= Vista::capturar('partials/fila_editable', ['columnas' => $columnas, 'valores' => $fila, 'indice' => $i, 'errores' => $errores]) ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <template id="limites-fila"><?= Vista::capturar('partials/fila_editable', ['columnas' => $columnas]) ?></template>
    <p class="campo-ayuda">Todas las celdas son obligatorias. Si una fila tiene un error, no se guarda ninguna.</p>
    <div class="acciones acciones-separadas">
      <button class="btn btn-secundario" type="button" data-agregar-fila="limites"><?= Icono::svg('plus') ?> Agregar fila</button>
      <div class="acciones">
        <a class="btn btn-secundario" href="/formularios/valor_limite?requerimiento=<?= (int) $requerimiento['id'] ?>">Cancelar</a>
        <button class="btn btn-primario" type="submit">Guardar análisis</button>
      </div>
    </div>
  </form>
</div>
