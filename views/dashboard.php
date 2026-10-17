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
 * @var list<array<string, mixed>> $misPendientes  tester: sus casos sin resultado que no han vencido
 * @var list<array{id: int, nombre: string, sin_miembros: bool, sin_plan: bool}> $incompletos  admin
 * @var array{id: int, rol: int, activo: ?int} $usuario
 */

$t = $avance['total'];
$enProyecto = $usuario['activo'] !== null && $avance['proyectos'] !== [];
$porcentaje = fn (int $n, int $de): int => $de === 0 ? 0 : (int) round($n / $de * 100);
// Hoy: casos creados o anotados hoy. 0 no se muestra.
$cifras = [
    ['Casos', 'clipboard-list', $t['casos'], $enProyecto ? 'En este proyecto' : Html::cantidad(count($avance['proyectos']), 'proyecto', 'proyectos'), '', '', $t['casos_hoy']],
    ['OK', 'circle-check', $t['ok'], 'Pasaron la prueba', '1', 'cifra-exito', $t['ok_hoy']],
    ['FAULT', 'circle-x', $t['fault'], Html::cantidad((int) $t['incidentes_abiertos'], 'incidente abierto', 'incidentes abiertos'), '2', 'cifra-peligro', $t['fault_hoy']],
    ['Pendientes', 'circle-alert', $t['pendientes'], 'Sin resultado todavía', '0', 'cifra-aviso', 0],
];
// Por atender: lo que impide el Go y los incidentes sin dueño, por proyecto. Stoppers en la alerta de arriba.
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
    if ($p['sin_asignar'] > 0) {
        $tareas[] = ['users', Html::cantidad((int) $p['sin_asignar'], 'incidente abierto', 'incidentes abiertos') . ' sin asignar', $enProyecto ? '' : $p['nombre'], '/formularios/incidentes?estado=0'];
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
      <p class="alerta-titulo"><?= Html::cantidad((int) $t['stoppers'], 'stopper abierto', 'stoppers abiertos') ?></p>
      <p class="alerta-descripcion">Con un stopper abierto el proyecto es No-Go.</p>
      <a class="btn btn-secundario btn-sm alerta-accion" href="/formularios/incidentes?stopper=1">Ver stoppers</a>
    </div>
  <?php endif; ?>
  <div class="cifras">
    <a class="tarjeta cifra cifra-enlace cifra-principal" href="/reportes/cierre">
      <p class="cifra-etiqueta">Aprobación <?= Icono::svg('chart-column') ?></p>
      <p class="cifra-valor"><?= (int) $t['aprobacion'] ?> %</p>
      <p class="cifra-nota"><?= (int) $t['ok'] ?> OK de <?= (int) ($t['ok'] + $t['fault']) ?> con resultado</p>
    </a>
    <?php foreach ($cifras as [$etiqueta, $icono, $valor, $nota, $estado, $tono, $hoy]): ?>
      <a class="tarjeta cifra cifra-enlace <?= $tono ?>" href="/casos/listar<?= $estado === '' ? '' : '?estado=' . $estado ?>">
        <p class="cifra-etiqueta"><?= Html::e($etiqueta) ?> <?= Icono::svg($icono) ?></p>
        <p class="cifra-valor"><?= (int) $valor ?></p>
        <p class="cifra-nota"><?php if ($hoy > 0): ?><span class="cifra-hoy">+<?= (int) $hoy ?> hoy</span> · <?php endif; ?><?= Html::e($nota) ?></p>
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
    <?php // "Todo al día" solo si no queda nada: sin tareas, stoppers, atrasos ni proyectos en No-Go. ?>
    <?php if (!$tareas && !$asignados && !$atrasados && !$misPendientes && !$incompletos && $t['stoppers'] === 0 && !in_array(false, array_column($avance['proyectos'], 'go'), true)): ?>
      <p class="tarjeta tarea-vacia al-dia"><?= Icono::svg('circle-check') ?> Todo al día: todos los proyectos están en Go.</p>
    <?php endif; ?>
    <?php if ($tareas): ?>
      <section class="tarjeta" aria-labelledby="h-atender">
        <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="h-atender">Por atender</h2></header>
        <div class="tarjeta-contenido">
          <ul class="tareas">
            <?php foreach ($tareas as [$icono, $texto, $proyecto, $ruta]): ?>
              <li><a href="<?= Html::e($ruta) ?>"><?= Icono::svg($icono) ?> <span><?= Html::e($texto) ?></span><?php if ($proyecto !== ''): ?> <small><?= Html::e($proyecto) ?></small><?php endif; ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </section>
    <?php endif; ?>

    <div class="rejilla-panel">
      <?php if ($asignados): ?>
        <section class="tarjeta" aria-labelledby="h-asignados">
          <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="h-asignados">Incidentes asignados a mí</h2></header>
          <div class="tarjeta-contenido">
            <ul class="tareas">
              <?php foreach ($asignados as $i): ?>
                <li><a href="/formularios/incidentes/ver?id=<?= (int) $i['id'] ?>"><span class="codigo"><?= Html::e($i['codigo']) ?></span> <span><?= Html::e($i['titulo']) ?></span> <?= Catalogo::insignia('severidad', $i['severidad']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </section>
      <?php endif; ?>
      <?php if ($atrasados): ?>
        <section class="tarjeta" aria-labelledby="h-atrasados">
          <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="h-atrasados">Casos atrasados</h2></header>
          <div class="tarjeta-contenido">
            <ul class="tareas">
              <?php foreach ($atrasados as $c): ?>
                <li><a href="/casos/resultado?id=<?= (int) $c['id'] ?>"><span class="codigo"><?= Html::e($c['codigo']) ?></span> <span><?= Html::e($c['objetivo']) ?></span> <small><?= (int) $c['creado_por'] === $usuario['id'] ? 'Tuyo · ' : '' ?>Vencía el <?= Fecha::legible(new DateTime($c['fecha_fin'])) ?></small></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </section>
      <?php endif; ?>
      <?php if ($usuario['rol'] !== 1 && $misPendientes): ?>
        <section class="tarjeta" aria-labelledby="h-mis-pendientes">
          <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="h-mis-pendientes">Mis casos por vencer</h2></header>
          <div class="tarjeta-contenido">
            <ul class="tareas">
              <?php foreach ($misPendientes as $c): ?>
                <li><a href="/casos/resultado?id=<?= (int) $c['id'] ?>"><span class="codigo"><?= Html::e($c['codigo']) ?></span> <span><?= Html::e($c['objetivo']) ?></span> <small>Hasta el <?= Fecha::legible(new DateTime($c['fecha_fin'])) ?></small></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </section>
      <?php elseif ($usuario['rol'] === 1 && $incompletos): ?>
        <section class="tarjeta" aria-labelledby="h-incompletos">
          <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="h-incompletos">Proyectos por completar</h2></header>
          <div class="tarjeta-contenido">
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
        $partes = [['OK', 'ok', 'exito'], ['FAULT', 'fault', 'peligro'], ['Pendiente', 'pendientes', 'secundaria']];
        $resumen = implode(', ', array_map(fn (array $x): string => $x[0] . ' ' . (int) $p[$x[1]], $partes)) . ' de ' . (int) $p['casos'];
        ?>
        <section class="tarjeta" aria-labelledby="av-<?= (int) $p['id'] ?>">
          <header class="tarjeta-encabezado">
            <h3 class="tarjeta-titulo" id="av-<?= (int) $p['id'] ?>"><?= Html::e($p['nombre']) ?></h3>
            <p class="tarjeta-descripcion"><?= Html::cantidad((int) $p['casos'], 'caso', 'casos') ?> · <?= Html::cantidad((int) $p['stoppers'], 'stopper abierto', 'stoppers abiertos') ?></p>
            <div class="acciones"><?= $p['go'] ? '<span class="insignia insignia-exito">Go</span>' : '<span class="insignia insignia-peligro">No-Go</span>' ?></div>
          </header>
          <div class="tarjeta-contenido pila">
            <?php // Un cuadro por caso. Más de 200: un cuadro por cada $por casos. ?>
            <?php $por = max(1, (int) ceil($p['casos'] / 200)); ?>
            <div class="cuadricula" role="img" aria-label="<?= Html::e($resumen . ($por > 1 ? ". Cada cuadro: $por casos" : '')) ?>">
              <?php foreach ($partes as [, $clave, $variante]): ?>
                <?= str_repeat('<span class="barra-' . $variante . '"></span>', (int) ceil($p[$clave] / $por)) ?>
              <?php endforeach; ?>
            </div>
            <ul class="leyenda" aria-hidden="true">
              <?php foreach ($partes as [$estado, $clave, $variante]): ?>
                <li><span class="leyenda-muestra barra-<?= $variante ?>"></span><?= $estado ?> <?= (int) $p[$clave] ?> · <?= $porcentaje($p[$clave], $p['casos']) ?> %</li>
              <?php endforeach; ?>
            </ul>
            <?php if ($p['pendientes'] > 0): ?>
              <?php $plan = new DateTime($p['fin_plan']); ?>
              <?php if ($p['fin_estimado'] === null): ?>
                <p class="proyeccion">Nadie anotó resultados en 7 días · plan: <span class="numero<?= $plan < new DateTime('today') ? ' proyeccion-tarde' : '' ?>"><?= Fecha::legible($plan) ?></span></p>
              <?php else: ?>
                <?php $estimado = new DateTime($p['fin_estimado']); $tarde = (int) $plan->diff($estimado)->format('%r%a'); ?>
                <p class="proyeccion">A <span class="numero"><?= str_replace('.', ',', (string) $p['ritmo']) ?></span> casos/día termina el <span class="numero<?= $tarde > 0 ? ' proyeccion-tarde' : '' ?>"><?= Fecha::legible($estimado) ?></span>
                  · plan: <?= Fecha::legible($plan) ?><?= $tarde > 0 ? " · <span class=\"proyeccion-tarde\">$tarde d tarde</span>" : '' ?></p>
              <?php endif; ?>
            <?php endif; ?>
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
