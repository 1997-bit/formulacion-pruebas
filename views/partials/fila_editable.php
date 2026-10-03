<?php
declare(strict_types=1);

use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Fila de una tabla editable (formularios 2 a 5). Sin $indice es la plantilla vacía.
 * Errores con clave "fila.columna", junto a la celda (RNF-07).
 *
 * @var array<string, array{0: string, 1: string, 2: ?int}> $columnas  nombre => [etiqueta, text o textarea, maxlength]
 * @var ?array<string, mixed> $valores
 * @var ?int $indice
 * @var ?array<string, string> $errores
 */

$valores ??= [];
$errores ??= [];
?>
<tr>
  <?php foreach ($columnas as $nombre => [$etiqueta, $control, $maximo]): ?>
    <?php
    $error = isset($indice) ? ($errores["{$indice}.{$nombre}"] ?? '') : '';
    $id = isset($indice) ? "f-{$indice}-{$nombre}" : '';
    $atributos = 'class="control" name="' . $nombre . '[]" aria-label="' . Html::e($etiqueta) . '" required'
        . ($maximo !== null ? ' maxlength="' . $maximo . '"' : '')
        . ($error !== '' ? ' aria-invalid="true" aria-describedby="' . $id . '-error"' : '');
    $valor = Html::e((string) ($valores[$nombre] ?? ''));
    ?>
    <td data-columna="<?= Html::e($etiqueta) ?>">
      <?php if ($control === 'textarea'): ?>
        <textarea <?= $atributos ?>><?= $valor ?></textarea>
      <?php else: ?>
        <input <?= $atributos ?> type="text" value="<?= $valor ?>">
      <?php endif; ?>
      <?php if ($error !== ''): ?>
        <p class="campo-error" id="<?= $id ?>-error"><?= Icono::svg('circle-x') ?><?= Html::e($error) ?></p>
      <?php endif; ?>
    </td>
  <?php endforeach; ?>
  <td class="celda-acciones"><button class="btn btn-fantasma btn-icono" type="button" data-quitar-fila aria-label="Quitar fila" data-tooltip="Quitar fila" data-tooltip-alinear="fin"><?= Icono::svg('trash-2') ?></button></td>
</tr>
