<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Html;
use App\Helpers\Icono;
use App\Services\TablaDecisionServicio;

/**
 * Formulario 4 (RF-11) para crear o editar. Las columnas las arma el servidor:
 * agregar o quitar una fila envía el formulario sin guardar. Se guardan todas las reglas o ninguna.
 *
 * @var array<string, mixed> $requerimiento
 * @var string $huella  lo leído, para avisar si otro guardó antes (#100)
 * @var array{condiciones: list<array{texto: string, reglas: list<string>}>, acciones: list<array{texto: string, reglas: list<string>}>} $tabla
 * @var bool $nueva
 * @var array<string, string> $errores
 */

$reglas = 2 ** count($tabla['condiciones']);
$error = function (string $clave) use ($errores): string {
    if (!isset($errores[$clave])) {
        return '';
    }

    return '<p class="campo-error" id="td-' . str_replace('.', '-', $clave) . '-error">' . Icono::svg('circle-x') . Html::e($errores[$clave]) . '</p>';
};
$invalido = fn (string $clave): string => isset($errores[$clave]) ? ' aria-invalid="true" aria-describedby="td-' . str_replace('.', '-', $clave) . '-error"' : '';
$grupos = [
    'condicion' => ['Condiciones', 'Condición', $tabla['condiciones'], TablaDecisionServicio::MAX_CONDICIONES],
    'accion' => ['Acciones', 'Acción', $tabla['acciones'], TablaDecisionServicio::MAX_ACCIONES],
];
$reglasConError = array_filter(range(0, $reglas - 1), fn (int $r): bool => isset($errores["regla.{$r}"]));
?>
<header class="encabezado">
  <div>
    <p class="antetitulo fila"><span class="codigo"><?= Html::e($requerimiento['codigo']) ?></span> <?= Html::e($requerimiento['proyecto']) ?></p>
    <h1><?= $nueva ? 'Crear' : 'Editar' ?> tabla de decisión</h1>
    <p><?= Html::e($requerimiento['descripcion']) ?></p>
  </div>
</header>

<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
  <form class="tarjeta pila" method="post" action="/formularios/tabla_decision/editar">
    <!-- Enter guarda: es el primer botón de envío -->
    <button class="solo-lector" type="submit" tabindex="-1" aria-hidden="true">Guardar tabla</button>
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <input type="hidden" name="huella" value="<?= Html::e($huella) ?>">
    <input type="hidden" name="requerimiento_id" value="<?= (int) $requerimiento['id'] ?>">
    <input type="hidden" name="reglas" value="<?= $reglas ?>">
    <p class="campo-ayuda" id="td-ayuda">Las reglas salen de las condiciones: 2 condiciones son 4 reglas. Al agregar o quitar una condición, las reglas vuelven a todas las combinaciones y las acciones se desmarcan. Use — cuando la condición no importa.</p>
    <div class="tabla-contenedor">
      <table class="tabla tabla-editable" aria-describedby="td-ayuda">
        <caption class="solo-lector">Condiciones y acciones por regla</caption>
        <thead>
          <tr>
            <th scope="col">Condición / Acción</th>
            <?php for ($r = 1; $r <= $reglas; $r++): ?><th scope="col" class="celda-centro">Regla <?= $r ?></th><?php endfor; ?>
            <th scope="col" class="celda-acciones"><span class="solo-lector">Acciones</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($grupos as $campo => [$titulo, $etiqueta, $filas]): ?>
            <tr class="fila-grupo"><th scope="colgroup" colspan="<?= $reglas + 2 ?>"><?= $titulo ?></th></tr>
            <?php foreach ($filas as $i => $fila): ?>
              <?php $nombre = $etiqueta . ' ' . ($i + 1); ?>
              <tr>
                <th scope="row">
                  <input class="control" name="<?= $campo ?>[]" aria-label="<?= $nombre ?>" value="<?= Html::e($fila['texto']) ?>" required maxlength="255"<?= $invalido("{$campo}.{$i}") ?>>
                  <?= $error("{$campo}.{$i}") ?>
                </th>
                <?php foreach ($fila['reglas'] as $r => $valor): ?>
                  <?php $celda = "{$campo}.{$i}.{$r}"; $aria = 'aria-label="' . $nombre . ', regla ' . ($r + 1) . '"' . $invalido($celda); ?>
                  <td class="celda-centro celda-corta">
                    <?php if ($campo === 'condicion'): ?>
                      <div class="select"><select class="control" name="condicion_regla[<?= $i ?>][<?= $r ?>]" <?= $aria ?>>
                        <?php foreach (['V' => 'V', 'F' => 'F', '-' => '—'] as $opcion => $texto): ?>
                          <option value="<?= $opcion ?>"<?= $valor === $opcion ? ' selected' : '' ?>><?= $texto ?></option>
                        <?php endforeach; ?>
                      </select></div>
                    <?php else: ?>
                      <input type="checkbox" name="accion_regla[<?= $i ?>][<?= $r ?>]" value="X" <?= $aria ?><?= $valor === 'X' ? ' checked' : '' ?>>
                    <?php endif; ?>
                    <?= $error($celda) ?>
                  </td>
                <?php endforeach; ?>
                <td class="celda-acciones">
                  <button class="btn btn-fantasma btn-icono" type="submit" name="cambio" value="quitar-<?= $campo ?>-<?= $i ?>" formnovalidate
                    aria-label="Quitar <?= mb_strtolower($nombre) ?>" data-tooltip="Quitar <?= mb_strtolower($etiqueta) ?>" data-tooltip-alinear="fin"<?= count($filas) === 1 ? ' disabled' : '' ?>><?= Icono::svg('trash-2') ?></button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </tbody>
        <?php if ($reglasConError): ?>
          <tfoot>
            <tr>
              <th scope="row">Reglas sin acción</th>
              <?php for ($r = 0; $r < $reglas; $r++): ?><td class="celda-centro"><?= $error("regla.{$r}") ?></td><?php endfor; ?>
              <td></td>
            </tr>
          </tfoot>
        <?php endif; ?>
      </table>
    </div>
    <p class="campo-ayuda">Cada regla lleva al menos una acción. Si una celda tiene un error, no se guarda ninguna regla.</p>
    <div class="acciones acciones-separadas">
      <div class="acciones">
        <?php foreach ($grupos as $campo => [, $etiqueta, $filas, $maximo]): ?>
          <button class="btn btn-secundario" type="submit" name="cambio" value="<?= $campo ?>" formnovalidate<?= count($filas) >= $maximo ? ' disabled' : '' ?>><?= Icono::svg('plus') ?> Agregar <?= mb_strtolower($etiqueta) ?></button>
        <?php endforeach; ?>
      </div>
      <div class="acciones">
        <a class="btn btn-secundario" href="/formularios/tabla_decision?requerimiento=<?= (int) $requerimiento['id'] ?>">Cancelar</a>
        <button class="btn btn-primario" type="submit">Guardar tabla</button>
      </div>
    </div>
  </form>
</div>
