<?php
declare(strict_types=1);

use App\Helpers\Html;

/**
 * Barras apiladas por hora. Serie de arriba primero. data-dato: tooltip
 *
 * @var string $titulo
 * @var list<array<string, int>> $horas  24 horas, clave de serie => cantidad
 * @var array<string, array{0: string, 1: string}> $series  clave => texto, clase de la barra
 * @var array{0: string, 1: string} $unidad  singular, plural
 */

$total = array_map('array_sum', $horas);
$max = max(1, ...$total);
$pico = array_search(max($total), $total, true);
?>
<section class="tarjeta grafico" aria-labelledby="gr-horas">
  <header class="tarjeta-encabezado">
    <h2 class="tarjeta-titulo" id="gr-horas"><?= Html::e($titulo) ?></h2>
  </header>
  <ul class="leyenda" aria-hidden="true">
    <?php foreach ($series as [$texto, $clase]): ?><li><span class="leyenda-muestra <?= $clase ?>"></span><?= Html::e($texto) ?></li><?php endforeach; ?>
  </ul>
  <div class="grafico-barras grafico-pilas" style="--max:<?= $max ?>" role="img"
       aria-label="<?= array_sum($total) === 0 ? 'Sin datos.' : Html::e('Hora con más: ' . $pico . ':00, con ' . Html::cantidad($total[$pico], ...$unidad) . '.') ?>">
    <?php foreach ($horas as $h => $hora): ?>
      <?php $detalle = implode(', ', array_filter(array_map(fn (string $k): string => $hora[$k] > 0 ? $series[$k][0] . ' ' . $hora[$k] : '', array_keys($series)))); ?>
      <span style="--v:<?= $total[$h] ?>" data-dato="<?= Html::e(sprintf('%d:00 a %d:59 · %s', $h, $h, Html::cantidad($total[$h], ...$unidad)) . ($detalle !== '' ? ': ' . $detalle : '')) ?>">
        <?php foreach ($series as $k => [, $clase]): ?><?php if ($hora[$k] > 0): ?><span class="<?= $clase ?>" style="flex-grow:<?= $hora[$k] ?>"></span><?php endif; ?><?php endforeach; ?>
      </span>
    <?php endforeach; ?>
  </div>
  <div class="grafico-eje" aria-hidden="true"><span>0:00</span><span>6:00</span><span>12:00</span><span>18:00</span><span>23:59</span></div>
</section>
