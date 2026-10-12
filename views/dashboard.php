<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * RF-23. Sin proyecto activo: "Mi trabajo", una tarjeta por proyecto. Con proyecto activo: su resumen.
 *
 * @var array{total: array<string, int>, proyectos: list<array<string, mixed>>} $avance
 * @var list<array<string, mixed>> $asignados  incidentes abiertos asignados al usuario
 * @var list<array<string, mixed>> $atrasados  casos pendientes con la fecha final pasada
 * @var list<array<string, mixed>> $misPendientes  tester: sus casos sin resultado
 * @var list<array{id: int, nombre: string, sin_miembros: bool, sin_plan: bool}> $incompletos  admin
 * @var array{rol: int, activo: ?int} $usuario
 */

$t = $avance['total'];
$enProyecto = $usuario['activo'] !== null && $avance['proyectos'] !== [];
$porcentaje = fn (int $n, int $de): int => $de === 0 ? 0 : (int) round($n / $de * 100);
$cifras = [
    ['Casos', 'clipboard-list', $t['casos'], $enProyecto ? 'En este proyecto' : count($avance['proyectos']) . ' proyecto(s)', ''],
    ['OK', 'circle-check', $t['ok'], $t['aprobacion'] . ' % de aprobación', '1'],
    ['FAULT', 'circle-x', $t['fault'], $t['incidentes_abiertos'] . ' incidente(s) abierto(s)', '2'],
    ['Pendientes', 'circle-alert', $t['pendientes'], 'Sin resultado todavía', '0'],
];
// Por atender: lo que impide el Go, por proyecto. Cada fila lleva al cierre. Stoppers en la alerta de arriba.
$tareas = [];
foreach ($avance['proyectos'] as $p) {
    foreach ([
        ['fault_sin_incidente', 'bug', 'FAULT sin incidente'],
        ['sin_evidencia', 'image', 'con resultado y sin evidencia'],
    ] as [$clave, $icono, $texto]) {
        if ($p[$clave] > 0) {
            $tareas[] = [$icono, $p[$clave] . ' ' . $texto, $enProyecto ? '' : $p['nombre'], '/reportes/cierre?proyecto=' . (int) $p['id']];
        }
    }
}
?>
<header class="encabezado">
  <div>
    <h1><?= $enProyecto ? Html::e($avance['proyectos'][0]['nombre']) : 'Mi trabajo' ?></h1>
    <p><?= $enProyecto ? 'Resumen del proyecto activo.' : 'Lo que le toca en todos sus proyectos.' ?></p>
  </div>
  <div class="acciones"><a class="btn btn-secundario" href="/reportes/cierre"><?= Icono::svg('chart-column') ?> Reporte de cierre</a></div>
</header>
<div class="pila">
  <?php if ($t['stoppers'] > 0): ?>
    <div class="alerta alerta-error"><?= Icono::svg('triangle-alert') ?>
      <p class="alerta-titulo"><?= (int) $t['stoppers'] ?> stopper(s) abierto(s)</p>
      <p class="alerta-descripcion">Con un stopper abierto el proyecto es No-Go.</p>
      <a class="btn btn-secundario btn-sm alerta-accion" href="/formularios/incidentes?stopper=1">Ver stoppers</a>
    </div>
  <?php endif; ?>
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
              <li><a href="<?= Html::e($ruta) ?>"><?= Icono::svg($icono) ?> <span><?= Html::e($texto) ?></span><?php if ($proyecto !== ''): ?> <small><?= Html::e($proyecto) ?></small><?php endif; ?></a></li>
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
      <?php if ($usuario['rol'] !== 1): ?>
        <section class="tarjeta" aria-labelledby="h-mis-pendientes">
          <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="h-mis-pendientes">Mis casos sin resultado</h2></header>
          <div class="tarjeta-contenido">
            <?php if (!$misPendientes): ?>
              <p class="tarea-vacia"><?= Icono::svg('circle-check') ?> Todos tienen resultado.</p>
            <?php else: ?>
              <ul class="tareas">
                <?php foreach ($misPendientes as $c): ?>
                  <li><a href="/casos/resultado?id=<?= (int) $c['id'] ?>"><span class="codigo"><?= Html::e($c['codigo']) ?></span> <span><?= Html::e($c['objetivo']) ?></span> <small>Hasta el <?= Fecha::legible(new DateTime($c['fecha_fin'])) ?></small></a></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </section>
      <?php else: ?>
        <section class="tarjeta" aria-labelledby="h-incompletos">
          <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="h-incompletos">Proyectos por completar</h2></header>
          <div class="tarjeta-contenido">
            <?php if (!$incompletos): ?>
              <p class="tarea-vacia"><?= Icono::svg('circle-check') ?> Todos tienen miembros y plan.</p>
            <?php else: ?>
              <ul class="tareas">
                <?php foreach ($incompletos as $p): ?>
                  <?php if ($p['sin_miembros']): ?>
                    <li><a href="/admin/proyectos/editar?id=<?= $p['id'] ?>"><?= Icono::svg('users') ?> <span>Sin miembros</span> <small><?= Html::e($p['nombre']) ?></small></a></li>
                  <?php endif; ?>
                  <?php if ($p['sin_plan']): ?>
                    <li><a href="/formularios/plan_pruebas?proyecto=<?= $p['id'] ?>"><?= Icono::svg('file-text') ?> <span>Sin plan de pruebas</span> <small><?= Html::e($p['nombre']) ?></small></a></li>
                  <?php endif; ?>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </section>
      <?php endif; ?>
      <section class="tarjeta" aria-labelledby="h-recientes" data-recientes hidden>
        <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="h-recientes">Vistos hace poco</h2></header>
        <div class="tarjeta-contenido"><ul class="tareas"></ul></div>
      </section>
    </div>

    <?php if (!$enProyecto): ?>
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
            <?php // Abrir = elegirlo en el selector del sidebar. ?>
            <form method="post" action="/proyecto">
              <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
              <input type="hidden" name="volver" value="/dashboard">
              <button class="btn btn-primario btn-sm" type="submit" name="proyecto" value="<?= (int) $p['id'] ?>"
                aria-label="<?= Html::e('Abrir ' . $p['nombre']) ?>">Abrir</button>
            </form>
            <a class="btn btn-secundario btn-sm" href="/reportes/cierre?proyecto=<?= (int) $p['id'] ?>"
              aria-label="<?= Html::e('Ver cierre de ' . $p['nombre']) ?>">Ver cierre</a>
          </footer>
        </section>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
