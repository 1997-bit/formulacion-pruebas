<?php
declare(strict_types=1);

use App\Core\Vista;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;
use App\Services\ClasesEquivalenciaServicio;

/**
 * Formulario 2 (RF-09) de solo lectura. Al imprimir es la plantilla de la guía.
 *
 * @var array<string, mixed> $requerimiento
 * @var list<array<string, mixed>> $filas
 * @var ?array{autor: string, guardado_en: string} $guardado
 * @var ?string $flash
 */

$editar = '/formularios/clases_equivalencia/editar?requerimiento=' . (int) $requerimiento['id'];
?>
<header class="encabezado">
  <div>
    <p class="antetitulo fila"><span class="codigo"><?= Html::e($requerimiento['codigo']) ?></span> <?= Html::e($requerimiento['proyecto']) ?></p>
    <h1>Matriz de clases de equivalencia</h1>
    <p><?= Html::e($requerimiento['descripcion']) ?></p>
    <p class="solo-impresion">Formulario 2 · Matriz de clases de equivalencia</p>
  </div>
  <?php if ($filas): ?>
    <div class="acciones">
      <a class="btn btn-secundario" href="<?= $editar ?>"><?= Icono::svg('pencil') ?> Editar</a>
      <button class="btn btn-secundario" type="button" data-imprimir><?= Icono::svg('printer') ?> Imprimir</button>
    </div>
  <?php endif; ?>
</header>

<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash]) ?>
  <?php if (!$filas): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>Este requerimiento no tiene matriz</h2>
      <p>Divida cada campo de entrada en clases válidas e inválidas.</p>
      <a class="btn btn-primario" href="<?= $editar ?>"><?= Icono::svg('plus') ?> Crear matriz</a>
    </div>
  <?php else: ?>
    <div class="tabla-contenedor">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Clases de equivalencia por campo</caption>
        <thead>
          <tr><?php foreach (ClasesEquivalenciaServicio::COLUMNAS as [$etiqueta]): ?><th scope="col"><?= Html::e($etiqueta) ?></th><?php endforeach; ?></tr>
        </thead>
        <tbody>
          <?php foreach ($filas as $fila): ?>
            <tr>
              <?php foreach (ClasesEquivalenciaServicio::COLUMNAS as $nombre => [$etiqueta]): ?>
                <?php if ($nombre === 'campo'): ?>
                  <th scope="row" data-columna="<?= Html::e($etiqueta) ?>"><?= Html::e($fila[$nombre]) ?></th>
                <?php else: ?>
                  <td data-columna="<?= Html::e($etiqueta) ?>" class="multilinea"><?= Html::e($fila[$nombre]) ?></td>
                <?php endif; ?>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($guardado !== null): ?>
      <?php $fecha = new DateTime($guardado['guardado_en']); ?>
      <p class="campo-ayuda">Guardada por <?= Html::e($guardado['autor']) ?> el <?= Fecha::legible($fecha) ?> a las <?= $fecha->format('H:i') ?>.</p>
    <?php endif; ?>
  <?php endif; ?>
  <p class="solo-impresion campo-ayuda">Impreso el <?= Fecha::legible(new DateTime()) ?> desde Casos de Prueba.</p>
</div>
