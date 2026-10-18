<?php
declare(strict_types=1);

use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Chips. La ✕ enlaza a la lista sin ese filtro
 *
 * @var string $ruta  /casos/listar
 * @var array<string, string> $fijos  parámetros que no son filtros: vista, mes
 * @var array<string, string> $filtros  todos los filtros, vacíos incluidos
 * @var array<string, string> $textos  filtro => texto del chip, solo los activos
 */

$usados = array_filter($filtros, fn (string $v): bool => $v !== '');
?>
<?php if ($textos): ?>
  <ul class="chips" aria-label="Filtros activos">
    <?php foreach ($textos as $filtro => $texto): ?>
      <li class="chip"><?= Html::e($texto) ?>
        <a class="chip-quitar" href="<?= Html::e($ruta . '?' . http_build_query($fijos + array_diff_key($usados, [$filtro => '']))) ?>"
          aria-label="<?= Html::e('Quitar filtro ' . $texto) ?>"><?= Icono::svg('x') ?></a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
