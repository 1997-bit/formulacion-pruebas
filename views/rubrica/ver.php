<?php
declare(strict_types=1);

use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;
use App\Services\RubricaServicio;

/**
 * Formulario 7 (RF-14) de solo lectura. El total lo suma el servidor.
 *
 * @var array<string, mixed> $proyecto
 * @var ?array{puntos: array<int, int>, total: int, autor: string, guardado_en: string} $rubrica
 * @var ?string $flash
 */

$editar = '/formularios/rubrica/editar?proyecto=' . (int) $proyecto['id'];
// Descriptor de 5, 4, 3 y 1-2.
$nivel = fn (int $p): int => min(5 - $p, 3);
?>
<header class="encabezado">
  <div>
    <p class="antetitulo"><?= Html::e($proyecto['nombre']) ?></p>
    <h1>Rúbrica de evaluación</h1>
    <p class="solo-impresion">Formulario 7 · Rúbrica de evaluación</p>
  </div>
  <?php if ($rubrica !== null): ?>
    <div class="acciones">
      <a class="btn btn-secundario" href="<?= $editar ?>"><?= Icono::svg('pencil') ?> Editar</a>
      <button class="btn btn-secundario" type="button" data-imprimir><?= Icono::svg('printer') ?> Imprimir</button>
    </div>
  <?php endif; ?>
</header>

<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash]) ?>
  <?php if ($rubrica === null): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>Este proyecto no tiene rúbrica</h2>
      <p>Puntúe de 1 a 5 cada criterio; el total es sobre <?= RubricaServicio::MAXIMO ?>.</p>
      <a class="btn btn-primario" href="<?= $editar ?>"><?= Icono::svg('plus') ?> Llenar rúbrica</a>
    </div>
  <?php else: ?>
    <div class="tabla-contenedor">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Puntos de 1 a 5 por criterio</caption>
        <thead><tr><th scope="col">Criterio</th><th scope="col">Puntos</th><th scope="col">Nivel</th></tr></thead>
        <tbody>
          <?php foreach (Catalogo::valores('criterio_rubrica') as $clave => $c): ?>
            <?php $p = $rubrica['puntos'][$clave] ?? 0; ?>
            <tr>
              <th scope="row" data-columna="Criterio"><?= Html::e($c['texto']) ?></th>
              <td data-columna="Puntos"><?= $p ?: '—' ?></td>
              <td data-columna="Nivel"><?= $p ? Html::e($c['niveles'][$nivel($p)] ?? '') : '' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><th scope="row">Total</th><td data-columna="Total"><?= $rubrica['total'] ?> / <?= RubricaServicio::MAXIMO ?></td><td></td></tr>
        </tfoot>
      </table>
    </div>
    <?php $fecha = new DateTime($rubrica['guardado_en']); ?>
    <p class="campo-ayuda">Evaluado por <?= Html::e($rubrica['autor']) ?> el <?= Fecha::legible($fecha) ?> a las <?= $fecha->format('H:i') ?>.</p>
  <?php endif; ?>
  <p class="solo-impresion campo-ayuda">Impreso el <?= Fecha::legible(new DateTime()) ?> desde Casos de Prueba.</p>
</div>
