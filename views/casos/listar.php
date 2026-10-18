<?php
declare(strict_types=1);

use App\Core\Paginacion;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var list<array<string, mixed>> $casos
 * @var ?Paginacion $paginacion  null en tablero y cronograma
 * @var string $vista lista, tablero o cronograma
 * @var DateTimeImmutable $mes  primer día del mes del cronograma
 * @var ?string $flash
 * @var array<string, string> $filtros proyecto, requerimiento, estado, desde, hasta
 * @var list<array<string, mixed>> $proyectos
 * @var list<array<string, mixed>> $requerimientos
 * @var array{activo: ?int} $usuario
 */

$filtrado = array_filter($filtros, fn (string $v): bool => $v !== '') !== [];
// Las pestañas conservan los filtros
$vistas = ['lista' => ['Lista', 'list-checks'], 'tablero' => ['Tablero', 'folder-kanban'], 'cronograma' => ['Cronograma', 'chart-column']];
$enlaceVista = fn (string $v): string => '/casos/listar?' . http_build_query(['vista' => $v] + array_filter($filtros, fn (string $x): bool => $x !== ''));
$elegido = fn (string $filtro, int|string $valor): string => (string) $valor === $filtros[$filtro] ? ' selected' : '';

// Códigos repetidos entre proyectos: agrupados. Con 1, sin grupos
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
// Con proyecto activo sobra la columna Proyecto
$todos = $usuario['activo'] === null;
$nombreProyecto = array_column($proyectos, 'nombre', 'id')[$filtros['proyecto']] ?? null;
$codigoReq = array_column($requerimientos, 'codigo', 'id')[$filtros['requerimiento']] ?? null;
// Atajos de fechas: cambian desde y hasta, lo demás queda
$hoy = new DateTimeImmutable('today');
$atajos = [
    'Esta semana' => [$hoy->modify('monday this week'), $hoy->modify('sunday this week')],
    'Próximos 7 días' => [$hoy, $hoy->modify('+6 days')],
    'Este mes' => [$hoy->modify('first day of this month'), $hoy->modify('last day of this month')],
];
$enlaceAtajo = fn (array $r): string => '/casos/listar?' . http_build_query(['vista' => $vista] + ($vista === 'cronograma' ? ['mes' => $mes->format('Y-m')] : [])
    + ['desde' => $r[0]->format('Y-m-d'), 'hasta' => $r[1]->format('Y-m-d')] + array_filter($filtros, fn (string $x): bool => $x !== ''));
$legible = fn (string $iso): string => Fecha::legible(new DateTime($iso));
$chips = array_filter([
    'desde' => $filtros['desde'] === '' ? '' : 'Desde: ' . $legible($filtros['desde']),
    'hasta' => $filtros['hasta'] === '' ? '' : 'Hasta: ' . $legible($filtros['hasta']),
    'proyecto' => $nombreProyecto === null ? '' : 'Proyecto: ' . $nombreProyecto,
    'requerimiento' => $codigoReq === null ? '' : 'Requerimiento: ' . $codigoReq,
    'estado' => $filtros['estado'] === '' ? '' : 'Estado: ' . Catalogo::texto('estado_caso', $filtros['estado']),
]);
?>
<header class="encabezado">
  <div>
    <h1>Casos de prueba</h1>
    <p>Todos los casos de sus proyectos, ordenados por código.</p>
  </div>
  <div class="acciones"><a class="btn btn-primario" href="/casos/registrar" data-atajo="c"><?= Icono::svg('plus') ?> Registrar caso</a></div>
</header>
<nav class="pestanas" aria-label="Vistas de casos">
  <?php foreach ($vistas as $clave => [$texto, $icono]): ?>
    <a href="<?= Html::e($enlaceVista($clave)) ?>"<?= $clave === $vista ? ' aria-current="page"' : '' ?>><?= Icono::svg($icono) ?> <?= $texto ?></a>
  <?php endforeach; ?>
</nav>
<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash]) ?>
  <?php if ($casos || $filtrado || $vista === 'cronograma'): ?>
    <search aria-label="Filtrar casos">
      <form class="filtros" method="get" action="/casos/listar">
        <input type="hidden" name="vista" value="<?= Html::e($vista) ?>">
        <?php if ($vista === 'cronograma'): ?><input type="hidden" name="mes" value="<?= $mes->format('Y-m') ?>"><?php endif; ?>
        <?php if ($todos): ?>
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
        <?php endif; ?>
        <div class="campo">
          <label for="fl-req">Requerimiento</label>
          <div class="select"><select class="control" id="fl-req" name="requerimiento"><option value="">Todos</option><?= $opcionesReq ?></select></div>
        </div>
        <div class="campo">
          <label for="fl-estado">Estado</label>
          <div class="select"><select class="control" id="fl-estado" name="estado"><option value="">Todos</option><?= Catalogo::opciones('estado_caso', $filtros['estado'] === '' ? null : $filtros['estado']) ?></select></div>
        </div>
        <div class="campo">
          <label for="fl-desde">Desde</label>
          <input class="control" type="date" id="fl-desde" name="desde" value="<?= Html::e($filtros['desde']) ?>">
        </div>
        <div class="campo">
          <label for="fl-hasta">Hasta</label>
          <input class="control" type="date" id="fl-hasta" name="hasta" value="<?= Html::e($filtros['hasta']) ?>">
        </div>
        <div class="filtros-pie">
          <p class="filtros-atajos"><span>Fechas:</span>
            <?php foreach ($atajos as $texto => $r): ?>
              <?php $activo = $filtros['desde'] === $r[0]->format('Y-m-d') && $filtros['hasta'] === $r[1]->format('Y-m-d'); ?>
              <a class="btn btn-fantasma btn-sm" href="<?= Html::e($enlaceAtajo($r)) ?>"<?= $activo ? ' aria-current="true"' : '' ?>><?= $texto ?></a>
            <?php endforeach; ?>
          </p>
          <div class="acciones">
            <?php if ($filtrado): ?><a class="btn btn-fantasma" href="/casos/listar?vista=<?= Html::e($vista) ?>">Limpiar</a><?php endif; ?>
            <button class="btn btn-secundario" type="submit">Filtrar</button>
          </div>
        </div>
      </form>
    </search>
    <?= Vista::capturar('partials/filtros_activos', [
        'ruta' => '/casos/listar',
        'fijos' => ['vista' => $vista] + ($vista === 'cronograma' ? ['mes' => $mes->format('Y-m')] : []),
        'filtros' => $filtros,
        'textos' => $chips,
    ]) ?>
  <?php endif; ?>
  <?php if ($vista === 'cronograma'): ?>
    <?= Vista::capturar('casos/cronograma', ['casos' => $casos, 'mes' => $mes, 'filtros' => $filtros]) ?>
  <?php elseif (!$casos && $filtrado): ?>
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
  <?php elseif ($vista === 'tablero'): ?>
    <?= Vista::capturar('casos/tablero', ['casos' => $casos]) ?>
  <?php else: ?>
    <div class="tabla-contenedor encabezado-fijo">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Casos de prueba</caption>
        <thead>
          <tr><th scope="col">Código</th><th scope="col">Caso</th><?php if ($todos): ?><th scope="col">Proyecto</th><?php endif; ?><th scope="col">Tipo</th><th scope="col">Creado por</th><th scope="col">Estado</th></tr>
        </thead>
        <tbody>
          <?php foreach ($casos as $c): ?>
            <tr>
              <td data-columna="Código"><a class="codigo" href="/casos/resultado?id=<?= (int) $c['id'] ?>"><?= Html::e($c['codigo']) ?></a></td>
              <td data-columna="Caso" class="celda-larga"><?= Html::e($c['objetivo']) ?></td>
              <?php if ($todos): ?><td data-columna="Proyecto"><?= Html::e($c['proyecto']) ?></td><?php endif; ?>
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
