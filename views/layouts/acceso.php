<?php
declare(strict_types=1);

use App\Core\Vista;
use App\Helpers\Html;

/**
 * @var string $titulo
 * @var string $contenido
 */
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= Html::e($titulo) ?> · Casos de Prueba</title>
<link rel="icon" type="image/svg+xml" href="<?= Vista::estatico('/favicon.svg') ?>">
<link rel="stylesheet" href="<?= Vista::estatico('/assets/css/tokens.css') ?>">
<link rel="stylesheet" href="<?= Vista::estatico('/assets/css/base.css') ?>">
<link rel="stylesheet" href="<?= Vista::estatico('/assets/css/componentes.css') ?>">
<script src="<?= Vista::estatico('/assets/js/tema.js') ?>"></script>
</head>
<body>
<main class="acceso">
  <img class="acceso-logo" src="/Frame.svg" alt="Casos de Prueba">
  <?= $contenido ?>
</main>
</body>
</html>
