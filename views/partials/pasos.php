<?php
declare(strict_types=1);

use App\Helpers\Html;

/**
 * Pasos del caso o del incidente
 * Estado: hecho, actual, pendiente o mal. Actual con aria-current="step"
 *
 * @var list<array{0: string, 1: string}> $pasos  texto y estado
 * @var string $etiqueta  nombre de la lista para el lector
 */

$leer = ['hecho' => 'hecho', 'actual' => 'paso actual', 'pendiente' => 'pendiente', 'mal' => 'con falla'];
?>
<ol class="pasos" aria-label="<?= Html::e($etiqueta) ?>">
  <?php foreach ($pasos as [$texto, $estado]): ?>
    <li class="paso paso-<?= $estado ?>"<?= $estado === 'actual' ? ' aria-current="step"' : '' ?>><?= Html::e($texto) ?><span class="solo-lector">: <?= $leer[$estado] ?></span></li>
  <?php endforeach; ?>
</ol>
