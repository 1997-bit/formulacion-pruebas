<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 8 (RF-15) para llenar o editar. Los promedios se ven al escribir; el servidor los recalcula.
 *
 * @var array<string, mixed> $proyecto
 * @var array<int, array{id: int, nombre: string}> $companeros
 * @var ?array<string, mixed> $datos  evaluado_id, auto, co y comentario por aspecto
 * @var array<string, string> $errores
 */

$elegido = (string) ($datos['evaluado_id'] ?? (count($companeros) === 1 ? reset($companeros)['id'] : ''));
$opciones = '';
foreach ($companeros as $c) {
    $opciones .= '<option value="' . $c['id'] . '"' . ((string) $c['id'] === $elegido ? ' selected' : '') . '>' . Html::e($c['nombre']) . '</option>';
}
$error = function (string $clave) use ($errores): string {
    $mensaje = $errores[$clave] ?? '';

    return $mensaje === '' ? '' : '<p class="campo-error" id="f-' . str_replace('.', '-', $clave) . '-error">' . Icono::svg('circle-x') . Html::e($mensaje) . '</p>';
};
$invalido = fn (string $clave): string => isset($errores[$clave]) ? ' aria-invalid="true" aria-describedby="f-' . str_replace('.', '-', $clave) . '-error"' : '';
?>
<header class="encabezado">
  <div>
    <p class="antetitulo"><?= Html::e($proyecto['nombre']) ?></p>
    <h1><?= $datos ? 'Editar' : 'Llenar' ?> autoevaluación y coevaluación</h1>
    <p>De 1 a 5: 5 excelente, 4 bueno, 3 regular, 1 o 2 deficiente.</p>
  </div>
</header>

<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
  <form class="tarjeta pila" method="post" action="/formularios/autoevaluacion/editar">
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <input type="hidden" name="proyecto_id" value="<?= (int) $proyecto['id'] ?>">
    <div style="max-width:24rem">
      <?= Vista::capturar('partials/campo', [
          'nombre' => 'evaluado_id',
          'etiqueta' => 'Compañero que evalúa',
          'tipo' => 'select',
          'opciones' => '<option value="">Elegir…</option>' . $opciones,
          'ayuda' => 'Solo salen los miembros del proyecto. La autoevaluación es de usted.',
          'error' => $errores['evaluado_id'] ?? '',
          'requerido' => true,
      ]) ?>
    </div>
    <div class="tabla-contenedor">
      <table class="tabla tabla-editable tabla-tarjetas">
        <caption class="solo-lector">Puntos de 1 a 5 por aspecto</caption>
        <thead><tr><th scope="col">Aspecto</th><th scope="col">Autoevaluación (1-5)</th><th scope="col">Coevaluación (1-5)</th><th scope="col">Comentarios</th></tr></thead>
        <tbody>
          <?php foreach (Catalogo::valores('aspecto_evaluacion') as $clave => $a): ?>
            <?php $aspecto = Html::e(mb_strtolower($a['texto'])); ?>
            <tr>
              <th scope="row"><?= Html::e($a['texto']) ?></th>
              <?php foreach (['auto' => 'Autoevaluación', 'co' => 'Coevaluación'] as $tipo => $etiqueta): ?>
                <td class="celda-corta" data-columna="<?= $etiqueta ?>">
                  <input class="control" type="number" min="1" max="5" step="1" inputmode="numeric" required
                    name="<?= $tipo ?>[<?= $clave ?>]" data-grupo="<?= $tipo ?>" aria-label="<?= $etiqueta ?> de <?= $aspecto ?>"
                    value="<?= Html::e((string) ($datos[$tipo][$clave] ?? '')) ?>"<?= $invalido("{$tipo}.{$clave}") ?>>
                  <?= $error("{$tipo}.{$clave}") ?>
                </td>
              <?php endforeach; ?>
              <td data-columna="Comentarios">
                <textarea class="control" name="comentario[<?= $clave ?>]" maxlength="500" aria-label="Comentarios de <?= $aspecto ?>"<?= $invalido("comentario.{$clave}") ?>><?= Html::e((string) ($datos['comentario'][$clave] ?? '')) ?></textarea>
                <?= $error("comentario.{$clave}") ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><th scope="row">Promedio</th><td data-columna="Autoevaluación"><output data-promedio="auto">—</output></td><td data-columna="Coevaluación"><output data-promedio="co">—</output></td><td></td></tr>
        </tfoot>
      </table>
    </div>
    <p class="campo-ayuda">Todos los puntos son obligatorios; los comentarios, no. El servidor recalcula los promedios al guardar.</p>
    <div class="acciones">
      <a class="btn btn-secundario" href="/formularios/autoevaluacion?proyecto=<?= (int) $proyecto['id'] ?>">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar evaluación</button>
    </div>
  </form>
</div>
