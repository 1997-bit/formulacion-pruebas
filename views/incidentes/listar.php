<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Paginacion;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var list<array<string, mixed>> $incidentes
 * @var ?Paginacion $paginacion  null en el tablero
 * @var ?string $flash
 * @var string $vista lista o tablero
 * @var array<string, string> $filtros estado, severidad, stopper, mios, todos, q
 * @var array{activo: ?int} $usuario
 */

$filtrado = array_filter($filtros, fn (string $v): bool => $v !== '') !== [];
$usados = array_filter($filtros, fn (string $v): bool => $v !== '');
$enlaceVista = fn (string $v): string => '/formularios/incidentes?' . http_build_query(['vista' => $v] + $usados);
// Vistas rápidas. El tablero ya muestra todos los estados
$rapidas = $vista === 'lista'
    ? ['Abiertos' => [], 'Asignados a mí' => ['mios' => '1'], 'Stoppers' => ['stopper' => '1'], 'Todos' => ['todos' => '1']]
    : ['Todos' => [], 'Asignados a mí' => ['mios' => '1'], 'Stoppers' => ['stopper' => '1']];
$rapidaActual = array_filter(array_intersect_key($filtros, ['estado' => '', 'stopper' => '', 'mios' => '', 'todos' => '']), fn (string $v): bool => $v !== '');
$enlaceRapida = fn (array $f): string => '/formularios/incidentes?' . http_build_query(['vista' => $vista] + $f);
$total = $paginacion !== null ? $paginacion->total : count($incidentes);
$ahora = new DateTimeImmutable();
// Con proyecto activo sobra la columna Proyecto
$todos = $usuario['activo'] === null;
// Iniciales decorativas, nombre en texto
$asignado = fn (?string $nombre): string => $nombre === null
    ? 'Sin asignar'
    : '<span class="avatar" aria-hidden="true">' . Html::e(Html::iniciales($nombre)) . '</span>' . Html::e($nombre);
$chips = array_filter([
    'estado' => $filtros['estado'] === '' || $vista === 'tablero' ? '' : 'Estado: ' . Catalogo::texto('estado_incidente', $filtros['estado']),
    'severidad' => $filtros['severidad'] === '' ? '' : 'Severidad: ' . Catalogo::texto('severidad', $filtros['severidad']),
    'stopper' => $filtros['stopper'] === '1' ? 'Solo stoppers' : '',
    'mios' => $filtros['mios'] === '1' ? 'Asignados a mí' : '',
    'todos' => $filtros['todos'] === '1' && $vista === 'lista' ? 'Con cerrados' : '',
    'q' => $filtros['q'] === '' ? '' : 'Busca: ' . $filtros['q'],
]);
?>
<header class="encabezado">
  <div>
    <h1>Incidentes</h1>
    <p>Los defectos de los casos de sus proyectos. Stoppers y más graves primero.</p>
  </div>
  <div class="acciones"><a class="btn btn-primario" href="/formularios/incidentes/registrar" data-atajo="c"><?= Icono::svg('plus') ?> Registrar incidente</a></div>
</header>
<nav class="pestanas" aria-label="Vistas de incidentes">
  <?php foreach (['lista' => ['Lista', 'list-checks'], 'tablero' => ['Tablero', 'folder-kanban']] as $clave => [$texto, $icono]): ?>
    <a href="<?= Html::e($enlaceVista($clave)) ?>"<?= $clave === $vista ? ' aria-current="page"' : '' ?>><?= Icono::svg($icono) ?> <?= $texto ?></a>
  <?php endforeach; ?>
</nav>
<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash]) ?>
  <?php if ($incidentes || $filtrado): ?>
    <search aria-label="Filtrar incidentes">
      <form class="filtros" method="get" action="/formularios/incidentes" data-autoenviar>
        <input type="hidden" name="vista" value="<?= Html::e($vista) ?>">
        <?php foreach (['stopper', 'mios', 'todos'] as $fijo): ?>
          <?php if ($filtros[$fijo] === '1'): ?><input type="hidden" name="<?= $fijo ?>" value="1"><?php endif; ?>
        <?php endforeach; ?>
        <div class="campo">
          <label for="fl-q">Buscar</label>
          <div class="control-con-icono"><?= Icono::svg('search') ?><input class="control" type="search" id="fl-q" name="q" value="<?= Html::e($filtros['q']) ?>" placeholder="Código o título" maxlength="150"></div>
        </div>
        <?php if ($vista === 'lista'): ?>
          <div class="campo">
            <label for="fl-estado">Estado</label>
            <div class="select"><select class="control" id="fl-estado" name="estado"><option value="">Sin cerrar</option><?= Catalogo::opciones('estado_incidente', $filtros['estado'] === '' ? null : $filtros['estado']) ?></select></div>
          </div>
        <?php endif; ?>
        <div class="campo">
          <label for="fl-severidad">Severidad</label>
          <div class="select"><select class="control" id="fl-severidad" name="severidad"><option value="">Todas</option><?= Catalogo::opciones('severidad', $filtros['severidad'] === '' ? null : $filtros['severidad']) ?></select></div>
        </div>
        <div class="filtros-pie">
          <p class="filtros-atajos"><span>Ver:</span>
            <?php foreach ($rapidas as $texto => $f): ?>
              <a class="btn btn-fantasma btn-sm" href="<?= Html::e($enlaceRapida($f)) ?>"<?= $rapidaActual == $f ? ' aria-current="true"' : '' ?>><?= $texto ?></a>
            <?php endforeach; ?>
          </p>
          <div class="acciones">
            <?php if ($filtrado): ?><a class="btn btn-fantasma" href="/formularios/incidentes?vista=<?= Html::e($vista) ?>">Limpiar</a><?php endif; ?>
            <button class="btn btn-secundario" type="submit">Filtrar</button>
          </div>
        </div>
      </form>
    </search>
    <?= Vista::capturar('partials/filtros_activos', ['ruta' => '/formularios/incidentes', 'fijos' => ['vista' => $vista], 'filtros' => $filtros, 'textos' => $chips]) ?>
    <p class="campo-ayuda" role="status"><?= Html::cantidad($total, 'incidente', 'incidentes') ?></p>
  <?php endif; ?>
  <?php if (!$incidentes && $filtrado): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('search') ?></span>
      <h2>Ningún incidente coincide con los filtros</h2>
      <p>Cambie los filtros o límpielos para ver todos los incidentes.</p>
      <a class="btn btn-secundario" href="/formularios/incidentes?vista=<?= Html::e($vista) ?>">Limpiar filtros</a>
    </div>
  <?php elseif (!$incidentes): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>Todavía no hay incidentes</h2>
      <p>Cuando un caso falle, registre el defecto desde el caso.</p>
      <a class="btn btn-primario" href="/formularios/incidentes/registrar"><?= Icono::svg('plus') ?> Registrar incidente</a>
    </div>
  <?php elseif ($vista === 'tablero'): ?>
    <?php
    $columnas = array_fill_keys(array_keys(Catalogo::valores('estado_incidente')), []);
    foreach ($incidentes as $i) {
        $columnas[$i['estado']][] = $i;
    }
    ?>
    <p class="campo-ayuda">Arrastre una tarjeta a otra columna, o use los botones de la tarjeta.</p>
    <form id="mover" method="post" action="/formularios/incidentes/mover"><input type="hidden" name="csrf" value="<?= Csrf::token() ?>"></form>
    <div class="tablero" data-tablero>
      <?php foreach ($columnas as $estado => $lista): ?>
        <section class="tablero-columna" aria-labelledby="col-<?= $estado ?>" data-columna-estado="<?= $estado ?>">
          <h2 class="tablero-titulo" id="col-<?= $estado ?>"><?= Catalogo::insignia('estado_incidente', $estado) ?> <span><?= count($lista) ?></span></h2>
          <?php if (!$lista): ?><p class="tablero-vacio">Sin incidentes.</p><?php endif; ?>
          <ul class="tablero-lista">
            <?php foreach ($lista as $i): ?>
              <li class="tarjeta tarjeta-caso" draggable="true" data-arrastrable>
                <span class="tarjeta-caso-cabeza">
                  <a class="codigo" href="/formularios/incidentes/ver?id=<?= (int) $i['id'] ?>"><?= Html::e($i['codigo']) ?></a>
                  <span class="fila"><?= $i['es_stopper'] ? '<span class="insignia insignia-peligro">Stopper</span>' : '' ?><?= Catalogo::insignia('severidad', $i['severidad']) ?></span>
                </span>
                <span class="tarjeta-caso-texto"><?= Html::e($i['titulo']) ?></span>
                <span class="tarjeta-caso-pie"><span><?= Html::e($i['proyecto']) ?> · <?= Html::e($i['caso']) ?></span><span class="fila"><?= $asignado($i['asignado']) ?></span></span>
                <span class="mover"><?php foreach (Catalogo::valores('estado_incidente') as $destino => $d): ?><?php if ($destino !== $estado): ?><button class="btn btn-secundario btn-sm" form="mover" name="destino" value="<?= (int) $i['id'] ?>-<?= $destino ?>" aria-label="Mover <?= Html::e($i['codigo']) ?> a <?= Html::e($d['texto']) ?>"><?= $destino > $estado ? Html::e($d['texto']) . ' →' : '← ' . Html::e($d['texto']) ?></button><?php endif; ?><?php endforeach; ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="tabla-contenedor encabezado-fijo">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Incidentes</caption>
        <thead>
          <tr><th scope="col">Código</th><th scope="col">Título</th><th scope="col">Caso</th><?php if ($todos): ?><th scope="col">Proyecto</th><?php endif; ?><th scope="col">Severidad</th><th scope="col">Prioridad</th><th scope="col">Asignado a</th><th scope="col">Estado</th><th scope="col">Creado</th></tr>
        </thead>
        <tbody>
          <?php foreach ($incidentes as $i): ?>
            <?php $creado = new DateTimeImmutable($i['creado_en']); ?>
            <tr>
              <td data-columna="Código"><span class="codigo"><?= Html::e($i['codigo']) ?></span></td>
              <td data-columna="Título" class="celda-larga"><a href="/formularios/incidentes/ver?id=<?= (int) $i['id'] ?>"><?= Html::e($i['titulo']) ?></a><?= $i['es_stopper'] ? ' <span class="insignia insignia-peligro">Stopper</span>' : '' ?></td>
              <td data-columna="Caso"><a class="codigo" href="/casos/resultado?id=<?= (int) $i['caso_id'] ?>"><?= Html::e($i['caso']) ?></a></td>
              <?php if ($todos): ?><td data-columna="Proyecto"><?= Html::e($i['proyecto']) ?></td><?php endif; ?>
              <td data-columna="Severidad"><?= Catalogo::insignia('severidad', $i['severidad']) ?></td>
              <td data-columna="Prioridad"><?= Html::e(Catalogo::texto('prioridad', $i['prioridad'])) ?></td>
              <td data-columna="Asignado a"><span class="fila"><?= $asignado($i['asignado']) ?></span></td>
              <td data-columna="Estado"><?= Catalogo::insignia('estado_incidente', $i['estado']) ?></td>
              <td data-columna="Creado"><time datetime="<?= $creado->format('Y-m-d\TH:i') ?>" title="<?= Fecha::legible($creado) . ' ' . Fecha::hora($creado) ?>"><?= Fecha::relativa($creado, $ahora) ?></time></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= Vista::capturar('partials/paginacion', ['paginacion' => $paginacion]) ?>
  <?php endif; ?>
</div>
