<?php
declare(strict_types=1);

use App\Core\Paginacion;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var list<array<string, mixed>> $incidentes
 * @var Paginacion $paginacion
 * @var ?string $flash
 */
?>
<header class="encabezado">
  <div>
    <h1>Incidentes</h1>
    <p>Los defectos de los casos de sus proyectos. Abiertos y más graves primero.</p>
  </div>
  <div class="acciones"><a class="btn btn-primario" href="/formularios/incidentes/registrar"><?= Icono::svg('plus') ?> Registrar incidente</a></div>
</header>
<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash]) ?>
  <?php if (!$incidentes): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>Todavía no hay incidentes</h2>
      <p>Cuando un caso falle, registre el defecto desde el caso.</p>
      <a class="btn btn-primario" href="/formularios/incidentes/registrar"><?= Icono::svg('plus') ?> Registrar incidente</a>
    </div>
  <?php else: ?>
    <div class="tabla-contenedor">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Incidentes</caption>
        <thead>
          <tr><th scope="col">Código</th><th scope="col">Título</th><th scope="col">Caso</th><th scope="col">Proyecto</th><th scope="col">Severidad</th><th scope="col">Prioridad</th><th scope="col">Asignado a</th><th scope="col">Estado</th></tr>
        </thead>
        <tbody>
          <?php foreach ($incidentes as $i): ?>
            <tr>
              <td data-columna="Código"><a class="codigo" href="/formularios/incidentes/ver?id=<?= (int) $i['id'] ?>"><?= Html::e($i['codigo']) ?></a></td>
              <td data-columna="Título" class="celda-larga"><?= Html::e($i['titulo']) ?><?= $i['es_stopper'] ? ' <span class="insignia insignia-peligro">Stopper</span>' : '' ?></td>
              <td data-columna="Caso"><a class="codigo" href="/casos/resultado?id=<?= (int) $i['caso_id'] ?>"><?= Html::e($i['caso']) ?></a></td>
              <td data-columna="Proyecto"><?= Html::e($i['proyecto']) ?></td>
              <td data-columna="Severidad"><?= Catalogo::insignia('severidad', $i['severidad']) ?></td>
              <td data-columna="Prioridad"><?= Catalogo::insignia('prioridad', $i['prioridad']) ?></td>
              <td data-columna="Asignado a"><?= Html::e($i['asignado'] ?? 'Sin asignar') ?></td>
              <td data-columna="Estado"><?= Catalogo::insignia('estado_incidente', $i['estado']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= Vista::capturar('partials/paginacion', ['paginacion' => $paginacion]) ?>
  <?php endif; ?>
</div>
