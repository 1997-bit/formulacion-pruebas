<?php
declare(strict_types=1);

use App\Core\Paginacion;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var list<array<string, mixed>> $casos
 * @var Paginacion $paginacion
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
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash]) ?>
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
          <tr><th scope="col">Código</th><th scope="col">Caso</th><th scope="col">Proyecto</th><th scope="col">Tipo</th><th scope="col">Creado por</th><th scope="col">Estado</th></tr>
        </thead>
        <tbody>
          <?php foreach ($casos as $c): ?>
            <tr>
              <td data-columna="Código"><span class="codigo"><?= Html::e($c['codigo']) ?></span></td>
              <td data-columna="Caso" class="celda-larga"><?= Html::e($c['objetivo']) ?></td>
              <td data-columna="Proyecto"><?= Html::e($c['proyecto']) ?></td>
              <td data-columna="Tipo"><?= Html::e(Catalogo::texto('tipo_prueba', $c['tipo_prueba'])) ?></td>
              <td data-columna="Creado por"><?= Html::e($c['autor']) ?></td>
              <td data-columna="Estado"><?= Catalogo::insignia('estado_caso', $c['estado']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= Vista::capturar('partials/paginacion', ['paginacion' => $paginacion]) ?>
  <?php endif; ?>
</div>
