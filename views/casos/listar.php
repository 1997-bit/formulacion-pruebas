<?php
declare(strict_types=1);

use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var list<array<string, mixed>> $casos
 * @var ?string $flash
 */
?>
<header class="encabezado">
  <div>
    <h1>Casos de prueba</h1>
    <p>Todos los casos de sus proyectos, ordenados por código.</p>
  </div>
  <div class="acciones"><a class="btn btn-primario" href="/casos/registrar"><?= Icono::svg('plus') ?> Registrar caso</a></div>
</header>
<div class="pila">
  <?php if ($flash): ?>
    <div class="alerta alerta-exito" role="status"><?= Icono::svg('circle-check') ?>
      <p class="alerta-titulo"><?= Html::e($flash) ?></p>
    </div>
  <?php endif; ?>
  <?php if (!$casos): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>Todavía no hay casos de prueba</h2>
      <p>Registre el primer caso para empezar a darle seguimiento.</p>
      <a class="btn btn-primario" href="/casos/registrar"><?= Icono::svg('plus') ?> Registrar caso</a>
    </div>
  <?php else: ?>
    <div class="tabla-contenedor">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Casos de prueba</caption>
        <thead>
          <tr>
            <th scope="col">Código</th><th scope="col">Módulo</th><th scope="col">Requerimiento</th>
            <th scope="col">Tipo</th><th scope="col">Sub-técnica</th><th scope="col">Fechas</th><th scope="col">Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($casos as $c): ?>
            <tr>
              <td data-columna="Código"><span class="codigo"><?= Html::e($c['codigo']) ?></span></td>
              <td data-columna="Módulo" class="celda-larga"><?= Html::e($c['modulo']) ?></td>
              <td data-columna="Requerimiento"><?= Html::e($c['requerimiento']) ?></td>
              <td data-columna="Tipo"><?= Html::e(Catalogo::texto('tipo_prueba', $c['tipo_prueba'])) ?></td>
              <td data-columna="Sub-técnica" class="celda-larga"><?= Html::e(Catalogo::texto('subtecnica', $c['subtecnica'])) ?></td>
              <td data-columna="Fechas"><?= Fecha::legible(new DateTime($c['fecha_inicio'])) ?> – <?= Fecha::legible(new DateTime($c['fecha_fin'])) ?></td>
              <td data-columna="Estado"><?= Catalogo::insignia('estado_caso', $c['estado']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
