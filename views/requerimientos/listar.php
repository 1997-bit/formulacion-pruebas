<?php
declare(strict_types=1);

use App\Core\Paginacion;
use App\Core\Vista;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var list<array<string, mixed>> $requerimientos
 * @var Paginacion $paginacion
 * @var ?string $flash
 */
?>
<header class="encabezado">
  <div>
    <h1>Requerimientos</h1>
    <p>Los requerimientos de sus proyectos. Cada caso de prueba valida uno.</p>
  </div>
  <div class="acciones"><a class="btn btn-primario" href="/requerimientos/registrar"><?= Icono::svg('plus') ?> Registrar requerimiento</a></div>
</header>
<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash]) ?>
  <?php if (!$requerimientos): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>Todavía no hay requerimientos</h2>
      <p>Registre el primer requerimiento para poder registrar casos de prueba sobre él.</p>
      <a class="btn btn-primario" href="/requerimientos/registrar"><?= Icono::svg('plus') ?> Registrar requerimiento</a>
    </div>
  <?php else: ?>
    <div class="tabla-contenedor">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Requerimientos</caption>
        <thead>
          <tr><th scope="col">Código</th><th scope="col">Descripción</th><th scope="col">Tipo</th><th scope="col">Proyecto</th><th scope="col">Casos</th></tr>
        </thead>
        <tbody>
          <?php foreach ($requerimientos as $r): ?>
            <tr>
              <td data-columna="Código"><span class="codigo"><?= Html::e($r['codigo']) ?></span></td>
              <td data-columna="Descripción" class="celda-larga"><?= Html::e($r['descripcion']) ?></td>
              <td data-columna="Tipo"><span class="insignia insignia-borde"><?= $r['no_funcional'] ? 'No funcional' : 'Funcional' ?></span></td>
              <td data-columna="Proyecto"><?= Html::e($r['proyecto']) ?></td>
              <td data-columna="Casos"><?= (int) $r['casos'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= Vista::capturar('partials/paginacion', ['paginacion' => $paginacion]) ?>
  <?php endif; ?>
</div>
