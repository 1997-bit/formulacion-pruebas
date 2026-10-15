<?php
declare(strict_types=1);

use App\Helpers\Fecha;
use App\Helpers\Html;

/**
 * RF-20. Cambios del caso como línea de tiempo: hora a la izquierda, punto y cambio a la derecha.
 * Punto de color en los cambios de estado.
 *
 * @var list<array<string, mixed>> $historial  Historial::deCaso()
 * @var string $codigo
 */

$ahora = new DateTimeImmutable();
?>
<?php if (!$historial): ?>
  <p class="campo-ayuda">Sin cambios todavía.</p>
<?php else: ?>
  <ol class="linea-tiempo" aria-label="<?= Html::e('Cambios de ' . $codigo) ?>">
    <?php foreach ($historial as $h): ?>
      <?php $fecha = new DateTime($h['fecha']); ?>
      <li<?= $h['campo'] === 'estado' ? ' class="linea-tiempo-estado"' : '' ?>>
        <time datetime="<?= $fecha->format('Y-m-d\TH:i') ?>" title="<?= Fecha::legible($fecha) . ' ' . $fecha->format('H:i') ?>"><?= Fecha::relativa($fecha, $ahora) ?></time>
        <div>
          <p class="linea-tiempo-cabeza"><strong><?= Html::e($h['usuario']) ?></strong> cambió <?= Html::e($h['etiqueta']) ?></p>
          <p><span class="solo-lector">Antes: </span><del class="cambio"><?= Html::e($h['antes']) ?></del> <span aria-hidden="true">→</span><span class="solo-lector">. Después: </span> <ins class="cambio"><?= Html::e($h['despues']) ?></ins></p>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>
<?php endif; ?>
