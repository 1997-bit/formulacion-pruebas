<?php
declare(strict_types=1);

use App\Core\Paginacion;
use App\Core\Vista;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var string $vista lista o matriz
 * @var list<array<string, mixed>> $matriz  RequerimientoServicio::matriz()
 * @var list<array<string, mixed>> $requerimientos
 * @var ?Paginacion $paginacion
 * @var ?string $flash
 * @var array{activo: ?int} $usuario
 */
// Con proyecto activo la columna Proyecto sobra.
$todos = $usuario['activo'] === null;
?>
<header class="encabezado">
  <div>
    <h1>Requerimientos</h1>
    <p>Los requerimientos de sus proyectos. Cada caso de prueba valida uno.</p>
  </div>
  <div class="acciones"><a class="btn btn-primario" href="/requerimientos/registrar" data-atajo="c"><?= Icono::svg('plus') ?> Registrar requerimiento</a></div>
</header>
<nav class="pestanas" aria-label="Vistas de requerimientos">
  <a href="/requerimientos/listar"<?= $vista === 'lista' ? ' aria-current="page"' : '' ?>><?= Icono::svg('list-checks') ?> Lista</a>
  <a href="/requerimientos/listar?vista=matriz"<?= $vista === 'matriz' ? ' aria-current="page"' : '' ?>><?= Icono::svg('layout-grid') ?> Matriz de cobertura</a>
</nav>
<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash]) ?>
  <?php if ($vista === 'matriz'): ?>
    <?= Vista::capturar('requerimientos/matriz', ['matriz' => $matriz]) ?>
  <?php elseif (!$requerimientos): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>Todavía no hay requerimientos</h2>
      <p>Registre el primer requerimiento para poder registrar casos de prueba sobre él.</p>
      <a class="btn btn-primario" href="/requerimientos/registrar"><?= Icono::svg('plus') ?> Registrar requerimiento</a>
    </div>
  <?php else: ?>
    <div class="tabla-contenedor encabezado-fijo">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Requerimientos</caption>
        <thead>
          <tr><th scope="col">Código</th><th scope="col">Descripción</th><th scope="col">Tipo</th><?php if ($todos): ?><th scope="col">Proyecto</th><?php endif; ?><th scope="col" class="num">Casos</th></tr>
        </thead>
        <tbody>
          <?php foreach ($requerimientos as $r): ?>
            <tr>
              <td data-columna="Código"><span class="codigo"><?= Html::e($r['codigo']) ?></span></td>
              <td data-columna="Descripción" class="celda-larga"><?= Html::e($r['descripcion']) ?></td>
              <td data-columna="Tipo"><?= $r['no_funcional'] ? '<span class="insignia insignia-borde">No funcional</span>' : '<span class="sin-dato">Funcional</span>' ?></td>
              <?php if ($todos): ?><td data-columna="Proyecto"><?= Html::e($r['proyecto']) ?></td><?php endif; ?>
              <td data-columna="Casos" class="num"><?= (int) $r['casos'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($paginacion !== null): ?><?= Vista::capturar('partials/paginacion', ['paginacion' => $paginacion]) ?><?php endif; ?>
  <?php endif; ?>
</div>
