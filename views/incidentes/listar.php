<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Paginacion;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var list<array<string, mixed>> $incidentes
 * @var ?Paginacion $paginacion  null en el tablero
 * @var ?string $flash
 * @var string $vista lista o tablero
 * @var array<string, string> $filtros estado, severidad, stopper, mios
 */

$filtrado = array_filter($filtros, fn (string $v): bool => $v !== '') !== [];
$usados = array_filter($filtros, fn (string $v): bool => $v !== '');
$enlaceVista = fn (string $v): string => '/formularios/incidentes?' . http_build_query(['vista' => $v] + $usados);
$marcado = fn (string $filtro): string => $filtros[$filtro] === '1' ? ' checked' : '';
?>
<header class="encabezado">
  <div>
    <h1>Incidentes</h1>
    <p>Los defectos de los casos de sus proyectos. Abiertos y más graves primero.</p>
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
    <form class="filtros" method="get" action="/formularios/incidentes" role="search" aria-label="Filtrar incidentes">
      <input type="hidden" name="vista" value="<?= Html::e($vista) ?>">
      <?php if ($vista === 'lista'): ?>
        <div class="campo">
          <label for="fl-estado">Estado</label>
          <div class="select"><select class="control" id="fl-estado" name="estado"><option value="">Todos</option><?= Catalogo::opciones('estado_incidente', $filtros['estado'] === '' ? null : $filtros['estado']) ?></select></div>
        </div>
      <?php endif; ?>
      <div class="campo">
        <label for="fl-severidad">Severidad</label>
        <div class="select"><select class="control" id="fl-severidad" name="severidad"><option value="">Todas</option><?= Catalogo::opciones('severidad', $filtros['severidad'] === '' ? null : $filtros['severidad']) ?></select></div>
      </div>
      <div class="grupo grupo-fila filtros-casillas">
        <label class="opcion"><input type="checkbox" name="stopper" value="1"<?= $marcado('stopper') ?>> Solo stoppers</label>
        <label class="opcion"><input type="checkbox" name="mios" value="1"<?= $marcado('mios') ?>> Asignados a mí</label>
      </div>
      <div class="acciones">
        <button class="btn btn-secundario" type="submit">Filtrar</button>
        <?php if ($filtrado): ?><a class="btn btn-fantasma" href="/formularios/incidentes?vista=<?= Html::e($vista) ?>">Limpiar</a><?php endif; ?>
      </div>
    </form>
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
    <p class="campo-ayuda">Arrastre una tarjeta a otra columna, o use "Mover a" en la tarjeta.</p>
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
                <span class="tarjeta-caso-pie"><span><?= Html::e($i['proyecto']) ?> · <?= Html::e($i['caso']) ?></span><span><?= Html::e($i['asignado'] ?? 'Sin asignar') ?></span></span>
                <form class="mover" method="post" action="/formularios/incidentes/ver">
                  <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
                  <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
                  <input type="hidden" name="asignado_id" value="<?= $i['asignado_id'] === null ? '' : (int) $i['asignado_id'] ?>">
                  <input type="hidden" name="es_stopper" value="<?= (int) $i['es_stopper'] ?>">
                  <input type="hidden" name="volver" value="tablero">
                  <div class="select">
                    <select class="control" name="estado" aria-label="Mover <?= Html::e($i['codigo']) ?> a">
                      <?= Catalogo::opciones('estado_incidente', $estado) ?>
                    </select>
                  </div>
                  <button class="btn btn-secundario btn-sm" type="submit">Mover</button>
                </form>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endforeach; ?>
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
