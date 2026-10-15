<?php
declare(strict_types=1);

use App\Helpers\Catalogo;
use App\Helpers\Html;

/**
 * RF-23. Gráficos del cierre: resultados por día, avance acumulado e incidentes por día.
 * Barras con CSS: --v es el valor y --max el tope. data-dato: tooltip al pasar el mouse; con teclado o lector, la tabla de abajo. Curvas con SVG. Cada gráfico tiene su tabla para lector de pantalla. RNF-07.
 *
 * @var array<string, mixed> $g  ReporteServicio::graficos()
 * @var array<string, mixed> $p  proyecto del cierre
 */

$dias = $g['dias'];
$n = count($dias);
$dm = fn (string $dia): string => (new DateTime($dia))->format('d/m');
$coma = fn (float|int $x): string => str_replace('.', ',', (string) $x);
// Eje: primer día, el del medio y el último.
$eje = '<div class="grafico-eje" aria-hidden="true"><span>' . $dm($dias[0]['dia']) . '</span><span>' . $dm($dias[intdiv($n - 1, 2)]['dia']) . '</span><span>' . $dm($dias[$n - 1]['dia']) . '</span></div>';
$rango = $dm($dias[0]['dia']) . ' al ' . $dm($dias[$n - 1]['dia']);

$maxAnotados = max(1, ...array_column($dias, 'anotados'));
$pico = $g['pico'];

// Avance: x de 0 a 300, y de 100 a 0.
$pasados = array_values(array_filter($dias, fn (array $d): bool => $d['real_acum'] !== null));
$ultimo = $pasados === [] ? null : end($pasados);
$maxAcum = max(1, $dias[$n - 1]['plan_acum'], $ultimo['real_acum'] ?? 0);
$punto = fn (int $i, int $v): string => round($n === 1 ? 150 : $i / ($n - 1) * 300, 1) . ',' . round(100 - $v / $maxAcum * 100, 1);
$plan = implode(' ', array_map(fn (int $i): string => $punto($i, $dias[$i]['plan_acum']), array_keys($dias)));
$real = implode(' ', array_map(fn (int $i): string => $punto($i, $pasados[$i]['real_acum']), array_keys($pasados)));
$finReal = $pasados === [] ? 0 : round((count($pasados) - 1) / max(1, $n - 1) * 300, 1);
$dif = $g['diferencia'];

$severidades = Catalogo::valores('severidad');
$totalDia = fn (array $d): int => array_sum($d['severidad']);
$maxIncidentes = max(1, ...array_map($totalDia, $dias));
$maxStoppers = max(1, ...array_column($dias, 'stoppers'));
$horas = $g['horas_cierre'];
?>
<div class="graficos">
  <section class="tarjeta grafico" aria-labelledby="gr-resultados">
    <header class="tarjeta-encabezado">
      <h2 class="tarjeta-titulo" id="gr-resultados">Resultados por día</h2>
      <p class="tarjeta-descripcion"><?= $rango ?></p>
    </header>
    <p class="grafico-cifra"><?= (int) $p['ok'] + (int) $p['fault'] ?></p>
    <p class="grafico-nota">resultados anotados · promedio <span class="numero"><?= $coma($g['promedio']) ?></span> por día</p>
    <div class="grafico-barras" style="--max:<?= $maxAnotados ?>" role="img"
         aria-label="<?= $pico['anotados'] > 0 ? 'Pico el ' . $dm($pico['dia']) . ' con ' . $pico['anotados'] . '. ' : '' ?>Promedio <?= $coma($g['promedio']) ?> por día.">
      <?php foreach ($dias as $i => $d): ?>
        <?php $esPico = $pico['anotados'] > 0 && $d['dia'] === $pico['dia']; ?>
        <span style="--v:<?= $d['anotados'] ?>"<?= $esPico ? ' class="pico"' : '' ?> data-dato="<?= $dm($d['dia']) ?> · <?= $d['anotados'] ?> resultado(s)"><?php if ($esPico): ?><span class="grafico-etiqueta<?= $i > $n * 2 / 3 ? ' al-fin' : '' ?>"><?= $dm($d['dia']) ?> · <?= $d['anotados'] ?></span><?php endif; ?></span>
      <?php endforeach; ?>
      <?php if ($g['promedio'] > 0): ?><span class="grafico-promedio" style="--v:<?= $g['promedio'] ?>"><span>prom. <?= $coma($g['promedio']) ?></span></span><?php endif; ?>
    </div>
    <?= $eje ?>
    <dl class="grafico-pie">
      <div><dt>Día pico</dt><dd><?= $pico['anotados'] > 0 ? $dm($pico['dia']) . ' · ' . $pico['anotados'] : '—' ?></dd></div>
      <div><dt>Promedio diario</dt><dd><?= $coma($g['promedio']) ?></dd></div>
      <div><dt>Días sin anotar</dt><dd><?= $g['sin_anotar'] ?></dd></div>
    </dl>
  </section>

  <section class="tarjeta grafico" aria-labelledby="gr-avance">
    <header class="tarjeta-encabezado">
      <h2 class="tarjeta-titulo" id="gr-avance">Avance contra el plan</h2>
      <p class="tarjeta-descripcion"><?= $rango ?></p>
    </header>
    <p class="grafico-cifra"><?= $ultimo['real_acum'] ?? 0 ?></p>
    <p class="grafico-nota"><?php if ($dif === 0): ?>al día con el plan<?php else: ?><span class="numero <?= $dif > 0 ? 'grafico-bien' : 'grafico-mal' ?>"><?= $dif > 0 ? '+' . $dif : '−' . abs($dif) ?></span> <?= $dif > 0 ? 'adelante del' : 'atrás del' ?> plan a hoy<?php endif; ?></p>
    <svg class="grafico-linea" viewBox="0 0 300 100" preserveAspectRatio="none" role="img"
         aria-label="Avance acumulado. Real <?= $ultimo['real_acum'] ?? 0 ?>, plan <?= $ultimo['plan_acum'] ?? 0 ?> a hoy. Plan total <?= $dias[$n - 1]['plan_acum'] ?>.">
      <polyline class="grafico-plan" points="<?= $plan ?>"/>
      <?php if ($pasados !== []): ?>
        <polygon class="grafico-area" points="<?= $real ?> <?= $finReal ?>,100 0,100"/>
        <polyline class="grafico-real" points="<?= $real ?>"/>
      <?php endif; ?>
    </svg>
    <?= $eje ?>
    <ul class="leyenda" aria-hidden="true">
      <li><span class="leyenda-muestra barra-exito"></span>Real</li>
      <li><span class="leyenda-muestra grafico-muestra-plan"></span>Plan: casos con fecha final hasta ese día</li>
    </ul>
    <dl class="grafico-pie">
      <div><dt>Plan a hoy</dt><dd><?= $ultimo['plan_acum'] ?? 0 ?></dd></div>
      <div><dt>Plan total</dt><dd><?= $dias[$n - 1]['plan_acum'] ?></dd></div>
      <div><dt>Diferencia</dt><dd class="<?= $dif > 0 ? 'grafico-bien' : ($dif < 0 ? 'grafico-mal' : '') ?>"><?= $dif > 0 ? '+' . $dif : ($dif < 0 ? '−' . abs($dif) : '0') ?></dd></div>
    </dl>
  </section>

  <section class="tarjeta grafico grafico-ancho" aria-labelledby="gr-incidentes">
    <header class="tarjeta-encabezado">
      <h2 class="tarjeta-titulo" id="gr-incidentes">Incidentes por día</h2>
      <p class="tarjeta-descripcion">Por severidad. Debajo de la línea: stoppers.</p>
    </header>
    <p class="grafico-cifra"><?= $g['incidentes'] ?></p>
    <p class="grafico-nota">incidentes registrados · <span class="numero"><?= (int) $p['incidentes_abiertos'] ?></span> abiertos</p>
    <?php if ($g['incidentes'] > 0): ?>
    <ul class="leyenda" aria-hidden="true">
      <?php foreach ($severidades as $v): ?><li><span class="leyenda-muestra barra-<?= $v['variante'] ?>"></span><?= $v['texto'] ?></li><?php endforeach; ?>
      <li><span class="leyenda-muestra barra-stopper"></span>Stopper</li>
    </ul>
    <div class="grafico-barras grafico-pilas" style="--max:<?= $maxIncidentes ?>" role="img" aria-label="Incidentes por día y severidad. El detalle está en la tabla.">
      <?php foreach ($dias as $d): ?>
        <?php $detalle = implode(', ', array_filter(array_map(fn (int $sv): string => $d['severidad'][$sv] > 0 ? $severidades[$sv]['texto'] . ' ' . $d['severidad'][$sv] : '', [4, 3, 2, 1]))); ?>
        <span style="--v:<?= $totalDia($d) ?>" data-dato="<?= Html::e($dm($d['dia']) . ' · ' . $totalDia($d) . ' incidente(s)' . ($detalle !== '' ? ': ' . $detalle : '') . ($d['stoppers'] > 0 ? '. Stoppers ' . $d['stoppers'] : '')) ?>">
          <?php foreach ([4, 3, 2, 1] as $s): ?><?php if ($d['severidad'][$s] > 0): ?><span class="barra-<?= $severidades[$s]['variante'] ?>" style="flex-grow:<?= $d['severidad'][$s] ?>"></span><?php endif; ?><?php endforeach; ?>
        </span>
      <?php endforeach; ?>
    </div>
    <div class="grafico-stoppers" style="--max:<?= $maxStoppers ?>" aria-hidden="true">
      <?php foreach ($dias as $d): ?><span style="--v:<?= $d['stoppers'] ?>"></span><?php endforeach; ?>
    </div>
    <?= $eje ?>
    <?php endif; ?>
    <dl class="grafico-pie">
      <div><dt>Abiertos</dt><dd><?= (int) $p['incidentes_abiertos'] ?></dd></div>
      <div><dt>Stoppers abiertos</dt><dd class="<?= $p['stoppers'] > 0 ? 'grafico-mal' : '' ?>"><?= (int) $p['stoppers'] ?></dd></div>
      <div><dt>Cerrados</dt><dd><?= $g['incidentes'] - (int) $p['incidentes_abiertos'] ?></dd></div>
      <div><dt>Tiempo medio de cierre</dt><dd><?= $horas === null ? '—' : ($horas < 24 ? $coma(round($horas, 1)) . ' h' : $coma(round($horas / 24, 1)) . ' d') ?></dd></div>
    </dl>
  </section>

  <table class="solo-lector">
    <caption>Datos por día de los gráficos</caption>
    <thead><tr><th scope="col">Día</th><th scope="col">Resultados</th><th scope="col">Real acumulado</th><th scope="col">Plan acumulado</th><th scope="col">Incidentes</th><th scope="col">Stoppers</th></tr></thead>
    <tbody>
      <?php foreach ($dias as $d): ?>
        <tr><th scope="row"><?= $dm($d['dia']) ?></th><td><?= $d['anotados'] ?></td><td><?= $d['real_acum'] ?? '—' ?></td><td><?= $d['plan_acum'] ?></td><td><?= $totalDia($d) ?></td><td><?= $d['stoppers'] ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
