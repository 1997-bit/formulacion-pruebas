<?php
declare(strict_types=1);

use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * RF-23: avance de los proyectos del usuario, y lo que falta por hacer.
 *
 * @var array{total: array<string, int>, proyectos: list<array<string, mixed>>} $avance
 * @var list<array<string, mixed>> $asignados  incidentes abiertos asignados al usuario
 * @var list<array<string, mixed>> $atrasados  casos pendientes con la fecha final pasada
 */

$t = $avance['total'];
$porcentaje = fn (int $n, int $de): int => $de === 0 ? 0 : (int) round($n / $de * 100);
$cifras = [
    ['Casos', 'clipboard-list', $t['casos'], count($avance['proyectos']) . ' proyecto(s)', ''],
    ['OK', 'circle-check', $t['ok'], $t['aprobacion'] . ' % de aprobación', '1'],
    ['FAULT', 'circle-x', $t['fault'], $t['incidentes_abiertos'] . ' incidente(s) abierto(s)', '2'],
    ['Pendientes', 'circle-alert', $t['pendientes'], 'Sin resultado todavía', '0'],
];
// Por atender: lo que impide el Go, por proyecto. Cada fila lleva al cierre del proyecto.
$tareas = [];
foreach ($avance['proyectos'] as $p) {
    foreach ([
        ['stoppers', 'triangle-alert', 'stopper(s) abierto(s)'],
        ['fault_sin_incidente', 'bug', 'FAULT sin incidente'],
        ['sin_evidencia', 'image', 'con resultado y sin evidencia'],
    ] as [$clave, $icono, $texto]) {
        if ($p[$clave] > 0) {
            $tareas[] = [$icono, $p[$clave] . ' ' . $texto, $p['nombre'], '/reportes/cierre?proyecto=' . (int) $p['id']];
        }
    }
}
?>
<header class="encabezado">
  <div>
    <h1>Panel</h1>
    <p>Avance de los casos de sus proyectos.</p>
  </div>
  <div class="acciones"><a class="btn btn-secundario" href="/reportes/cierre"><?= Icono::svg('chart-column') ?> Reporte de cierre</a></div>
</header>
<div class="pila">
  <div class="cifras">
    <?php foreach ($cifras as [$etiqueta, $icono, $valor, $nota, $estado]): ?>
      <a class="tarjeta cifra cifra-enlace" href="/casos/listar<?= $estado === '' ? '' : '?estado=' . $estado ?>">
        <p class="cifra-etiqueta"><?= Html::e($etiqueta) ?> <?= Icono::svg($icono) ?></p>
        <p class="cifra-valor"><?= (int) $valor ?></p>
        <p class="cifra-nota"><?= Html::e($nota) ?></p>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$avance['proyectos']): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>No está en ningún proyecto</h2>
      <p>Un administrador lo agrega como miembro.</p>
    </div>
  <?php else: ?>
    <section class="tarjeta" aria-labelledby="h-atender">
      <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="h-atender">Por atender</h2></header>
      <div class="tarjeta-contenido">
        <?php if (!$tareas): ?>
          <p class="tarea-vacia"><?= Icono::svg('circle-check') ?> Todo al día.</p>
        <?php else: ?>
          <ul class="tareas">
            <?php foreach ($tareas as [$icono, $texto, $proyecto, $ruta]): ?>
              <li><a href="<?= Html::e($ruta) ?>"><?= Icono::svg($icono) ?> <span><?= Html::e($texto) ?></span> <small><?= Html::e($proyecto) ?></small></a></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>

    <div class="rejilla-panel">
      <section class="tarjeta" aria-labelledby="h-asignados">
        <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="h-asignados">Incidentes asignados a mí</h2></header>
        <div class="tarjeta-contenido">
          <?php if (!$asignados): ?>
            <p class="tarea-vacia"><?= Icono::svg('circle-check') ?> Ninguno abierto.</p>
          <?php else: ?>
            <ul class="tareas">
              <?php foreach ($asignados as $i): ?>
                <li><a href="/formularios/incidentes/ver?id=<?= (int) $i['id'] ?>"><span class="codigo"><?= Html::e($i['codigo']) ?></span> <span><?= Html::e($i['titulo']) ?></span> <?= Catalogo::insignia('severidad', $i['severidad']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </section>
      <section class="tarjeta" aria-labelledby="h-atrasados">
        <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="h-atrasados">Casos atrasados</h2></header>
        <div class="tarjeta-contenido">
          <?php if (!$atrasados): ?>
            <p class="tarea-vacia"><?= Icono::svg('circle-check') ?> Ninguno pasó su fecha final.</p>
          <?php else: ?>
            <ul class="tareas">
              <?php foreach ($atrasados as $c): ?>
                <li><a href="/casos/resultado?id=<?= (int) $c['id'] ?>"><span class="codigo"><?= Html::e($c['codigo']) ?></span> <span><?= Html::e($c['objetivo']) ?></span> <small>Vencía el <?= Fecha::legible(new DateTime($c['fecha_fin'])) ?></small></a></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </section>
      <section class="tarjeta" aria-labelledby="h-recientes" data-recientes hidden>
        <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="h-recientes">Vistos hace poco</h2></header>
        <div class="tarjeta-contenido"><ul class="tareas"></ul></div>
      </section>
    </div>

    <h2 class="titulo-seccion">Proyectos</h2>
    <div class="rejilla-panel">
      <?php foreach ($avance['proyectos'] as $p): ?>
        <?php
        $partes = [['OK', 'ok', 'exito'], ['FAULT', 'fault', 'peligro'], ['Pendiente', 'pendientes', 'aviso']];
        $resumen = implode(', ', array_map(fn (array $x): string => $x[0] . ' ' . (int) $p[$x[1]], $partes)) . ' de ' . (int) $p['casos'];
        ?>
        <section class="tarjeta" aria-labelledby="av-<?= (int) $p['id'] ?>">
          <header class="tarjeta-encabezado">
            <h3 class="tarjeta-titulo" id="av-<?= (int) $p['id'] ?>"><?= Html::e($p['nombre']) ?></h3>
            <p class="tarjeta-descripcion"><?= (int) $p['casos'] ?> caso(s) · <?= (int) $p['stoppers'] ?> stopper(s) abierto(s)</p>
            <div class="acciones"><?= $p['go'] ? '<span class="insignia insignia-exito">Go</span>' : '<span class="insignia insignia-peligro">No-Go</span>' ?></div>
          </header>
          <div class="tarjeta-contenido pila">
            <div class="barra-apilada" role="img" aria-label="<?= Html::e($resumen) ?>">
              <?php foreach ($partes as [, $clave, $variante]): ?>
                <?php if ($p[$clave] > 0): ?><span class="barra-<?= $variante ?>" style="flex-grow:<?= (int) $p[$clave] ?>"></span><?php endif; ?>
              <?php endforeach; ?>
            </div>
            <ul class="leyenda" aria-hidden="true">
              <?php foreach ($partes as [$estado, $clave, $variante]): ?>
                <li><span class="leyenda-muestra barra-<?= $variante ?>"></span><?= $estado ?> <?= (int) $p[$clave] ?> · <?= $porcentaje($p[$clave], $p['casos']) ?> %</li>
              <?php endforeach; ?>
            </ul>
          </div>
          <footer class="tarjeta-pie">
            <a class="btn btn-secundario btn-sm" href="/casos/listar?proyecto=<?= (int) $p['id'] ?>&amp;vista=cronograma">Cronograma</a>
            <a class="btn btn-secundario btn-sm" href="/reportes/cierre?proyecto=<?= (int) $p['id'] ?>"
              aria-label="<?= Html::e('Ver cierre de ' . $p['nombre']) ?>">Ver cierre</a>
          </footer>
        </section>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
