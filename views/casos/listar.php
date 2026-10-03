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
 * @var array<string, string> $filtros proyecto, requerimiento, estado
 * @var list<array<string, mixed>> $proyectos
 * @var list<array<string, mixed>> $requerimientos
 */

$filtrado = array_filter($filtros, fn (string $v): bool => $v !== '') !== [];
$elegido = fn (string $filtro, int|string $valor): string => (string) $valor === $filtros[$filtro] ? ' selected' : '';

// Los códigos se repiten entre proyectos: se agrupan, sin grupos si hay uno solo.
$porProyecto = [];
foreach ($requerimientos as $r) {
    $porProyecto[$r['proyecto']][] = $r;
}
$opcionesReq = '';
foreach ($porProyecto as $proyecto => $reqs) {
    $opcionesReq .= count($porProyecto) > 1 ? '<optgroup label="' . Html::e($proyecto) . '">' : '';
    foreach ($reqs as $r) {
        $opcionesReq .= '<option value="' . $r['id'] . '"' . $elegido('requerimiento', $r['id']) . '>' . Html::e($r['codigo']) . '</option>';
    }
    $opcionesReq .= count($porProyecto) > 1 ? '</optgroup>' : '';
}
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
  <?php if ($casos || $filtrado): ?>
    <form class="filtros" method="get" action="/casos/listar" role="search" aria-label="Filtrar casos">
      <div class="campo">
        <label for="fl-proyecto">Proyecto</label>
        <div class="select">
          <select class="control" id="fl-proyecto" name="proyecto">
            <option value="">Todos</option>
            <?php foreach ($proyectos as $p): ?>
              <option value="<?= (int) $p['id'] ?>"<?= $elegido('proyecto', $p['id']) ?>><?= Html::e($p['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="campo">
        <label for="fl-req">Requerimiento</label>
        <div class="select"><select class="control" id="fl-req" name="requerimiento"><option value="">Todos</option><?= $opcionesReq ?></select></div>
      </div>
      <div class="campo">
        <label for="fl-estado">Estado</label>
        <div class="select"><select class="control" id="fl-estado" name="estado"><option value="">Todos</option><?= Catalogo::opciones('estado_caso', $filtros['estado'] === '' ? null : $filtros['estado']) ?></select></div>
      </div>
      <div class="acciones">
        <button class="btn btn-secundario" type="submit">Filtrar</button>
        <?php if ($filtrado): ?><a class="btn btn-fantasma" href="/casos/listar">Limpiar</a><?php endif; ?>
      </div>
    </form>
  <?php endif; ?>
  <?php if (!$casos && $filtrado): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('search') ?></span>
      <h2>Ningún caso coincide con los filtros</h2>
      <p>Cambie los filtros o límpielos para ver todos los casos.</p>
      <a class="btn btn-secundario" href="/casos/listar">Limpiar filtros</a>
    </div>
  <?php elseif (!$casos): ?>
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
              <td data-columna="Código"><a class="codigo" href="/casos/resultado?id=<?= (int) $c['id'] ?>"><?= Html::e($c['codigo']) ?></a></td>
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
