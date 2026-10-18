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
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#FFFFFF" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#18181B" media="(prefers-color-scheme: dark)">
<title><?= empty($errores) ? '' : 'Error: ' ?><?= Html::e($titulo) ?> · Safeguard</title>
<link rel="icon" type="image/svg+xml" href="<?= Vista::estatico('/favicon.svg') ?>">
<link rel="manifest" href="/manifest.webmanifest">
<?php foreach (Vista::estilos(false) as $css): ?>
<link rel="stylesheet" href="<?= Vista::estatico('/assets/css/' . $css . '.css') ?>">
<?php endforeach ?>
<script src="<?= Vista::estatico('/assets/js/tema.js') ?>"></script>
<script type="module" src="<?= Vista::estatico('/assets/js/clave.js') ?>"></script>
</head>
<body>
<main class="acceso">
  <img class="acceso-logo" src="/Frame.svg" alt="Safeguard">
  <?= $contenido ?>
</main>
</body>
</html>
