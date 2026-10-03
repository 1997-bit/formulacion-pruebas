<?php
declare(strict_types=1);

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
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/base.css">
<link rel="stylesheet" href="/assets/css/componentes.css">
<script>
  // Tema antes de pintar, para que no parpadee: el elegido por la persona o, si no eligió, el del sistema.
  (() => {
    let t = null;
    try { t = localStorage.getItem('tema'); } catch (e) {}
    document.documentElement.dataset.tema = t || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  })();
</script>
</head>
<body>
<main class="acceso">
  <img class="acceso-logo" src="/Frame.svg" alt="Safeguard">
  <?= $contenido ?>
</main>
</body>
</html>
