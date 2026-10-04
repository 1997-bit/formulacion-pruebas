<?php
declare(strict_types=1);

use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 5 (RF-12) de solo lectura. El porcentaje es el guardado. Al imprimir es la plantilla de la guía.
 *
 * @var array<string, mixed> $requerimiento
 * @var array<int, array<string, mixed>> $filas  metrica => fila
 * @var ?array{autor: string, guardado_en: string} $guardado
 * @var ?string $flash
 */

$editar = '/formularios/cobertura_blanca/editar?requerimiento=' . (int) $requerimiento['id'];
?>
<header class="encabezado">
  <div>
    <p class="antetitulo fila"><span class="codigo"><?= Html::e($requerimiento['codigo']) ?></span> <?= Html::e($requerimiento['proyecto']) ?></p>
    <h1>Cobertura de caja blanca</h1>
    <p><?= Html::e($requerimiento['descripcion']) ?></p>
    <p class="solo-impresion">Formulario 5 · Cobertura de caja blanca</p>
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
      <h2>Este requerimiento no tiene cobertura</h2>
      <p>Anote, por métrica, cuántos elementos hay, cuántos cubren las pruebas y con qué herramienta.</p>
      <a class="btn btn-primario" href="<?= $editar ?>"><?= Icono::svg('plus') ?> Crear cobertura</a>
    </div>
  <?php else: ?>
    <div class="tabla-contenedor">
      <table class="tabla">
        <caption class="solo-lector">Cobertura alcanzada por métrica</caption>
        <thead>
          <tr><th scope="col">Métrica</th><th scope="col">Total</th><th scope="col">Cubiertos</th><th scope="col">Cobertura</th><th scope="col">Herramienta</th></tr>
        </thead>
        <tbody>
          <?php foreach ($filas as $metrica => $fila): ?>
            <?php $nombre = Catalogo::texto('subtecnica', $metrica); ?>
            <tr>
              <th scope="row"><?= Html::e($nombre) ?></th>
              <td><?= (int) $fila['total'] ?></td>
              <td><?= (int) $fila['cubiertos'] ?></td>
              <td>
                <div class="progreso-celda">
                  <meter class="progreso" min="0" max="100" low="70" high="90" optimum="100" value="<?= (int) $fila['porcentaje'] ?>" aria-label="<?= Html::e($nombre) ?>"></meter>
                  <span><?= (int) $fila['porcentaje'] ?> %</span>
                </div>
              </td>
              <td class="multilinea"><?= Html::e($fila['herramienta']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="campo-ayuda">Verde desde 90 %, amarillo de 70 a 89 %, rojo bajo 70 %: los cortes de la rúbrica.</p>
    <?php if ($guardado !== null): ?>
      <?php $fecha = new DateTime($guardado['guardado_en']); ?>
      <p class="campo-ayuda">Guardado por <?= Html::e($guardado['autor']) ?> el <?= Fecha::legible($fecha) ?> a las <?= $fecha->format('H:i') ?>.</p>
    <?php endif; ?>
  <?php endif; ?>
  <p class="solo-impresion campo-ayuda">Impreso el <?= Fecha::legible(new DateTime()) ?> desde Casos de Prueba.</p>
</div>
