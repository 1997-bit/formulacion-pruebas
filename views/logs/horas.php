<?php
declare(strict_types=1);

use App\Helpers\Fecha;
use App\Helpers\Html;

/**
 * Barras apiladas por hora. Serie de arriba primero. data-dato: tooltip. Clic: la Lista de esa hora
 *
 * @var string $titulo
 * @var list<array<string, int>> $horas  24 horas, clave de serie => cantidad
 * @var array<string, array{0: string, 1: string}> $series  clave => texto, clase de la barra
 * @var array{0: string, 1: string} $unidad  singular, plural
 * @var Closure(int): string $enlace  hora => URL
 */

$total = array_map('array_sum', $horas);
$max = max(1, ...$total);
$reloj = fn (int $h, int $m = 0): string => Fecha::hora(new DateTimeImmutable("{$h}:{$m}"));
?>
<section class="tarjeta grafico" aria-labelledby="gr-horas">
  <header class="tarjeta-encabezado">
    <h2 class="tarjeta-titulo" id="gr-horas"><?= Html::e($titulo) ?></h2>
  </header>
  <ul class="leyenda" aria-hidden="true">
    <?php foreach ($series as [$texto, $clase]): ?><li><span class="leyenda-muestra <?= $clase ?>"></span><?= Html::e($texto) ?></li><?php endforeach; ?>
  </ul>
  <div class="grafico-barras grafico-pilas" style="--max:<?= $max ?>">
    <?php foreach ($horas as $h => $hora): ?>
      <?php $detalle = implode(', ', array_filter(array_map(fn (string $k): string => $hora[$k] > 0 ? $series[$k][0] . ' ' . $hora[$k] : '', array_keys($series)))); ?>
      <?php $dato = $reloj($h) . ' a ' . $reloj($h, 59) . ' · ' . Html::cantidad($total[$h], ...$unidad) . ($detalle !== '' ? ': ' . $detalle : ''); ?>
      <a href="<?= Html::e($enlace($h)) ?>" style="--v:<?= $total[$h] ?>" data-dato="<?= Html::e($dato) ?>" aria-label="<?= Html::e($dato) ?>">
        <?php foreach ($series as $k => [, $clase]): ?><?php if ($hora[$k] > 0): ?><span class="<?= $clase ?>" style="flex-grow:<?= $hora[$k] ?>"></span><?php endif; ?><?php endforeach; ?>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="grafico-eje" aria-hidden="true"><span><?= $reloj(0) ?></span><span><?= $reloj(6) ?></span><span><?= $reloj(12) ?></span><span><?= $reloj(18) ?></span><span><?= $reloj(23, 59) ?></span></div>
</section>
