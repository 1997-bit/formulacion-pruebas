<?php
declare(strict_types=1);

use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * RF-23: avance de los proyectos del usuario.
 *
 * @var array{total: array<string, int>, proyectos: list<array<string, mixed>>} $avance
 */

$t = $avance['total'];
$porcentaje = fn (int $n, int $de): int => $de === 0 ? 0 : (int) round($n / $de * 100);
$cifras = [
    ['Casos', 'clipboard-list', $t['casos'], count($avance['proyectos']) . ' proyecto(s)'],
    ['OK', 'circle-check', $t['ok'], $porcentaje($t['ok'], $t['casos']) . ' % del total'],
    ['FAULT', 'circle-x', $t['fault'], $t['incidentes_abiertos'] . ' incidente(s) abierto(s)'],
    ['Pendientes', 'circle-alert', $t['pendientes'], 'Sin resultado todavía'],
];
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
    <?php foreach ($cifras as [$etiqueta, $icono, $valor, $nota]): ?>
      <div class="tarjeta cifra">
        <p class="cifra-etiqueta"><?= Html::e($etiqueta) ?> <?= Icono::svg($icono) ?></p>
        <p class="cifra-valor"><?= (int) $valor ?></p>
        <p class="cifra-nota"><?= Html::e($nota) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (!$avance['proyectos']): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>No está en ningún proyecto</h2>
      <p>Un administrador lo agrega como miembro.</p>
    </div>
  <?php else: ?>
    <div class="rejilla-panel">
      <?php foreach ($avance['proyectos'] as $p): ?>
        <section class="tarjeta" aria-labelledby="av-<?= (int) $p['id'] ?>">
          <header class="tarjeta-encabezado">
            <h2 class="tarjeta-titulo" id="av-<?= (int) $p['id'] ?>"><?= Html::e($p['nombre']) ?></h2>
            <p class="tarjeta-descripcion"><?= (int) $p['casos'] ?> caso(s) · <?= (int) $p['stoppers'] ?> stopper(s) abierto(s)</p>
            <div class="acciones"><?= $p['go'] ? '<span class="insignia insignia-exito">Go</span>' : '<span class="insignia insignia-peligro">No-Go</span>' ?></div>
          </header>
          <div class="tarjeta-contenido pila">
            <?php foreach ([['OK', 'ok', 'progreso-exito'], ['FAULT', 'fault', 'progreso-peligro'], ['Pendiente', 'pendientes', 'progreso-aviso']] as [$estado, $clave, $clase]):
                $id = 'av-' . (int) $p['id'] . '-' . $clave; ?>
              <div>
                <div class="progreso-fila"><label for="<?= $id ?>"><?= $estado ?></label><span><?= (int) $p[$clave] ?> · <?= $porcentaje($p[$clave], $p['casos']) ?> %</span></div>
                <progress class="progreso <?= $clase ?>" id="<?= $id ?>" max="<?= max(1, (int) $p['casos']) ?>" value="<?= (int) $p[$clave] ?>"><?= (int) $p[$clave] ?> de <?= (int) $p['casos'] ?></progress>
              </div>
            <?php endforeach; ?>
          </div>
          <footer class="tarjeta-pie">
            <a class="btn btn-secundario btn-sm" href="/reportes/cierre?proyecto=<?= (int) $p['id'] ?>"
              aria-label="<?= Html::e('Ver cierre de ' . $p['nombre']) ?>">Ver cierre</a>
          </footer>
        </section>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
