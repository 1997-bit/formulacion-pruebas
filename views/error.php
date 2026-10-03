<?php
declare(strict_types=1);

use App\Helpers\Html;
use App\Helpers\Icono;

/** @var int $codigo */

[$icono, $titulo, $texto] = match ($codigo) {
    403 => ['lock', 'No tiene permiso para ver esta página', 'Pida acceso al administrador del proyecto.'],
    404 => ['file-question-mark', 'No encontramos esta página', 'Puede que el caso se haya eliminado o que la dirección esté mal.'],
    default => ['triangle-alert', 'Algo salió mal', 'Intente de nuevo en unos minutos.'],
};
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
<script src="/assets/js/tema.js"></script>
</head>
<body>
<main class="vacio" style="max-width:32rem;margin:10vh auto">
  <span class="vacio-icono"><?= Icono::svg($icono) ?></span>
  <h1><?= Html::e($titulo) ?></h1>
  <p><?= Html::e($texto) ?></p>
  <a class="btn btn-secundario" href="/dashboard">Volver al panel</a>
</main>
</body>
</html>
