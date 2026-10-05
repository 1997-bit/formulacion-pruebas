<?php
declare(strict_types=1);

use App\Core\Vista;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 4 (RF-11) de solo lectura. Al imprimir es la plantilla de la guía.
 *
 * @var array<string, mixed> $requerimiento
 * @var ?array{condiciones: list<array{texto: string, reglas: list<string>}>, acciones: list<array{texto: string, reglas: list<string>}>} $tabla
 * @var ?array{autor: string, guardado_en: string} $guardado
 * @var ?string $flash
 */

$editar = '/formularios/tabla_decision/editar?requerimiento=' . (int) $requerimiento['id'];
?>
<header class="encabezado">
  <div>
    <p class="antetitulo fila"><span class="codigo"><?= Html::e($requerimiento['codigo']) ?></span> <?= Html::e($requerimiento['proyecto']) ?></p>
    <h1>Tabla de decisión</h1>
    <p><?= Html::e($requerimiento['descripcion']) ?></p>
    <p class="solo-impresion">Formulario 4 · Tabla de decisión</p>
  </div>
  <?php if ($tabla): ?>
    <div class="acciones">
      <a class="btn btn-secundario" href="<?= $editar ?>"><?= Icono::svg('pencil') ?> Editar</a>
      <button class="btn btn-secundario" type="button" data-imprimir><?= Icono::svg('printer') ?> Imprimir</button>
    </div>
  <?php endif; ?>
</header>

<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash]) ?>
  <?php if (!$tabla): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>Este requerimiento no tiene tabla</h2>
      <p>Anote las condiciones, las acciones y qué acción toca en cada regla.</p>
      <a class="btn btn-primario" href="<?= $editar ?>"><?= Icono::svg('plus') ?> Crear tabla</a>
    </div>
  <?php else: ?>
    <?php $reglas = 2 ** count($tabla['condiciones']); ?>
    <div class="tabla-contenedor">
      <table class="tabla">
        <caption class="solo-lector">Condiciones y acciones por regla</caption>
        <thead>
          <tr>
            <th scope="col">Condición / Acción</th>
            <?php for ($r = 1; $r <= $reglas; $r++): ?><th scope="col" class="celda-centro">Regla <?= $r ?></th><?php endfor; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach (['Condiciones' => $tabla['condiciones'], 'Acciones' => $tabla['acciones']] as $titulo => $filas): ?>
            <tr class="fila-grupo"><th scope="colgroup" colspan="<?= $reglas + 1 ?>"><?= $titulo ?></th></tr>
            <?php foreach ($filas as $fila): ?>
              <tr>
                <th scope="row" class="multilinea"><?= Html::e($fila['texto']) ?></th>
                <?php foreach ($fila['reglas'] as $valor): ?>
                  <td class="celda-centro"><?= $valor === '-' ? '—' : Html::e($valor) ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($guardado !== null): ?>
      <?php $fecha = new DateTime($guardado['guardado_en']); ?>
      <p class="campo-ayuda">Guardado por <?= Html::e($guardado['autor']) ?> el <?= Fecha::legible($fecha) ?> a las <?= $fecha->format('H:i') ?>.</p>
    <?php endif; ?>
  <?php endif; ?>
  <p class="solo-impresion campo-ayuda">Impreso el <?= Fecha::legible(new DateTime()) ?> desde Casos de Prueba.</p>
</div>
