<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;
use App\Services\CoberturaServicio;

/**
 * Formulario 5 (RF-12) para crear o editar. Una fila fija por métrica; la vacía no se mide.
 * El porcentaje se ve al escribir; el servidor lo recalcula con el mismo redondeo. Se guardan todas o ninguna.
 *
 * @var array<string, mixed> $requerimiento
 * @var array<int, array<string, mixed>> $filas  metrica => total, cubiertos, herramienta
 * @var bool $nueva
 * @var array<string, string> $errores
 */

$error = function (string $clave) use ($errores): string {
    $mensaje = $errores[$clave] ?? '';

    return $mensaje === '' ? '' : '<p class="campo-error" id="cb-' . str_replace('.', '-', $clave) . '-error">' . Icono::svg('circle-x') . Html::e($mensaje) . '</p>';
};
$invalido = fn (string $clave): string => isset($errores[$clave]) ? ' aria-invalid="true" aria-describedby="cb-' . str_replace('.', '-', $clave) . '-error"' : '';
?>
<header class="encabezado">
  <div>
    <p class="antetitulo fila"><span class="codigo"><?= Html::e($requerimiento['codigo']) ?></span> <?= Html::e($requerimiento['proyecto']) ?></p>
    <h1><?= $nueva ? 'Crear' : 'Editar' ?> cobertura de caja blanca</h1>
  </div>
</header>

<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
  <form class="tarjeta pila" method="post" action="/formularios/cobertura_blanca/editar">
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <input type="hidden" name="requerimiento_id" value="<?= (int) $requerimiento['id'] ?>">
    <p class="campo-ayuda" id="cb-ayuda">Por métrica: cuántos elementos tiene el código, cuántos recorren las pruebas y la herramienta que lo midió. Deje vacía la métrica que no midió.</p>
    <div class="tabla-contenedor">
      <table class="tabla tabla-editable" aria-describedby="cb-ayuda">
        <caption class="solo-lector">Cobertura alcanzada por métrica</caption>
        <thead>
          <tr><th scope="col">Métrica</th><th scope="col">Total</th><th scope="col">Cubiertos</th><th scope="col">Cobertura</th><th scope="col">Herramienta</th></tr>
        </thead>
        <tbody>
          <?php foreach (CoberturaServicio::METRICAS as $m): ?>
            <?php
            $nombre = Catalogo::texto('subtecnica', $m);
            $fila = $filas[$m] ?? [];
            $total = (string) ($fila['total'] ?? '');
            $cubiertos = (string) ($fila['cubiertos'] ?? '');
            $calculable = ctype_digit($total) && ctype_digit($cubiertos) && (int) $total > 0 && (int) $cubiertos <= (int) $total;
            $porcentaje = $calculable ? CoberturaServicio::porcentaje((int) $total, (int) $cubiertos) : 0;
            ?>
            <tr>
              <th scope="row"><?= Html::e($nombre) ?></th>
              <td class="celda-corta">
                <input class="control" type="number" min="1" inputmode="numeric" name="total[<?= $m ?>]" data-parte="total" aria-label="Total de <?= Html::e(mb_strtolower($nombre)) ?>" value="<?= Html::e($total) ?>"<?= $invalido("{$m}.total") ?>>
                <?= $error("{$m}.total") ?>
              </td>
              <td class="celda-corta">
                <input class="control" type="number" min="0" inputmode="numeric" name="cubiertos[<?= $m ?>]" data-parte="cubiertos" aria-label="Cubiertos de <?= Html::e(mb_strtolower($nombre)) ?>" value="<?= Html::e($cubiertos) ?>"<?= $invalido("{$m}.cubiertos") ?>>
                <?= $error("{$m}.cubiertos") ?>
              </td>
              <td style="vertical-align:middle">
                <div class="progreso-celda">
                  <meter class="progreso" min="0" max="100" low="70" high="90" optimum="100" value="<?= $porcentaje ?>" aria-label="<?= Html::e($nombre) ?>"></meter>
                  <output data-porcentaje><?= $calculable ? $porcentaje . ' %' : '—' ?></output>
                </div>
              </td>
              <td>
                <input class="control" name="herramienta[<?= $m ?>]" aria-label="Herramienta de <?= Html::e(mb_strtolower($nombre)) ?>" maxlength="100" value="<?= Html::e((string) ($fila['herramienta'] ?? '')) ?>"<?= $invalido("{$m}.herramienta") ?>>
                <?= $error("{$m}.herramienta") ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="campo-ayuda">Cubiertos no pasa del total. Si una celda tiene un error, no se guarda ninguna métrica.</p>
    <div class="acciones">
      <a class="btn btn-secundario" href="/formularios/cobertura_blanca?requerimiento=<?= (int) $requerimiento['id'] ?>">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar cobertura</button>
    </div>
  </form>
</div>
