<?php
declare(strict_types=1);

use App\Helpers\Html;

/** @var array<string, array<string, string>> $grupos  grupo => slug => título */
?>
<div class="articulo">
  <header class="encabezado">
    <div>
      <h1>Ayuda</h1>
      <p>En cada pantalla, Ayuda del menú abre su artículo.</p>
    </div>
  </header>
  <?php foreach ($grupos as $grupo => $articulos): ?>
    <h2><?= Html::e($grupo) ?></h2>
    <ul>
      <?php foreach ($articulos as $slug => $texto): ?>
        <li><a href="/ayuda?a=<?= Html::e($slug) ?>"><?= Html::e($texto) ?></a></li>
      <?php endforeach; ?>
    </ul>
  <?php endforeach; ?>
</div>
