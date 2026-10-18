<?php
declare(strict_types=1);

use App\Core\Vista;
use App\Helpers\Html;

/**
 * @var string $titulo
 * @var string $articulo slug validado en AyudaControlador
 */
?>
<article class="articulo">
  <header class="encabezado"><h1><?= Html::e($titulo) ?></h1></header>
  <?= Vista::capturar('ayuda/articulos/' . $articulo) ?>
  <p class="articulo-pie"><a href="/ayuda">Todos los artículos</a></p>
</article>
