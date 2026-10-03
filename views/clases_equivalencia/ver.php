<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Html;
use App\Helpers\Icono;
use App\Services\ClasesEquivalenciaServicio;

/**
 * Formulario 2 de la paleta (RF-09). Sin requerimiento, primero se elige uno.
 *
 * @var ?array<string, mixed> $requerimiento
 * @var ?list<array<string, mixed>> $requerimientos  solo sin requerimiento
 * @var ?list<array<string, mixed>> $filas
 * @var ?array<string, string> $errores
 * @var ?string $flash
 */

$etiquetas = [
    'campo' => ['Campo', 'text'],
    'clase_valida' => ['Clase válida', 'textarea'],
    'clases_invalidas' => ['Clases inválidas', 'textarea'],
    'valores_representativos' => ['Valores representativos', 'text'],
    'resultado_esperado' => ['Resultado esperado', 'textarea'],
];
$columnas = [];
foreach (ClasesEquivalenciaServicio::COLUMNAS as $nombre => $maximo) {
    $columnas[$nombre] = [...$etiquetas[$nombre], $maximo];
}
?>
<header class="encabezado">
  <div>
    <?php if ($requerimiento !== null): ?>
      <p class="antetitulo fila"><span class="codigo"><?= Html::e($requerimiento['codigo']) ?></span> <?= Html::e($requerimiento['proyecto']) ?></p>
    <?php endif; ?>
    <h1>Matriz de clases de equivalencia</h1>
    <p><?= $requerimiento !== null ? Html::e($requerimiento['descripcion']) : 'Formulario 2' ?></p>
  </div>
</header>

<?php if ($requerimiento === null): ?>
  <?php
  $porProyecto = [];
  foreach ($requerimientos as $r) {
      $porProyecto[$r['proyecto']][] = $r;
  }
  ?>
  <?php if (!$requerimientos): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>Todavía no hay requerimientos</h2>
      <p>La matriz pertenece a un requerimiento. Registre uno primero.</p>
      <a class="btn btn-primario" href="/requerimientos/registrar"><?= Icono::svg('plus') ?> Registrar requerimiento</a>
    </div>
  <?php else: ?>
    <form class="tarjeta pila" method="get" action="/formularios/clases_equivalencia">
      <div class="campo">
        <label for="f-requerimiento">Requerimiento <span class="requerido" aria-hidden="true">*</span></label>
        <div class="select">
          <select class="control" id="f-requerimiento" name="requerimiento" required>
            <option value="">Elegir…</option>
            <?php foreach ($porProyecto as $proyecto => $lista): ?>
              <?= count($porProyecto) > 1 ? '<optgroup label="' . Html::e($proyecto) . '">' : '' ?>
              <?php foreach ($lista as $r): ?>
                <option value="<?= (int) $r['id'] ?>"><?= Html::e($r['codigo'] . ' · ' . $r['descripcion']) ?></option>
              <?php endforeach; ?>
              <?= count($porProyecto) > 1 ? '</optgroup>' : '' ?>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="acciones">
        <button class="btn btn-primario" type="submit">Continuar</button>
      </div>
    </form>
  <?php endif; ?>
<?php else: ?>
  <div class="pila">
    <?= Vista::capturar('partials/mensajes', ['flash' => $flash, 'errores' => $errores]) ?>
    <form class="tarjeta pila" method="post" action="/formularios/clases_equivalencia">
      <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
      <input type="hidden" name="requerimiento_id" value="<?= (int) $requerimiento['id'] ?>">
      <div class="tabla-contenedor">
        <table class="tabla tabla-editable tabla-tarjetas">
          <caption class="solo-lector">Clases de equivalencia por campo</caption>
          <thead>
            <tr>
              <?php foreach ($columnas as [$etiqueta]): ?><th scope="col"><?= Html::e($etiqueta) ?></th><?php endforeach; ?>
              <th scope="col" class="celda-acciones"><span class="solo-lector">Acciones</span></th>
            </tr>
          </thead>
          <tbody id="clases">
            <?php foreach ($filas ?: [[]] as $i => $fila): ?>
              <?= Vista::capturar('partials/fila_editable', ['columnas' => $columnas, 'valores' => $fila, 'indice' => $i, 'errores' => $errores]) ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <template id="clases-fila"><?= Vista::capturar('partials/fila_editable', ['columnas' => $columnas]) ?></template>
      <p class="campo-ayuda">Todas las celdas son obligatorias. Si una fila tiene un error, no se guarda ninguna.</p>
      <div class="acciones acciones-separadas">
        <button class="btn btn-secundario" type="button" data-agregar-fila="clases"><?= Icono::svg('plus') ?> Agregar fila</button>
        <button class="btn btn-primario" type="submit">Guardar matriz</button>
      </div>
    </form>
  </div>
<?php endif; ?>
