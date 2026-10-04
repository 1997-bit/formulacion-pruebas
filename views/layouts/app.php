<?php
declare(strict_types=1);

use App\Core\Vista;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var string $titulo
 * @var string $contenido
 * @var array{nombre: string, usuario: string, rol: int} $usuario
 * @var list<array{texto: string, ruta?: string}> $migas
 */

$rutaActual = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
// Sidebar desde la cookie, sin parpadeo.
$colapsado = ($_COOKIE['sidebar'] ?? '') === 'colapsado';
?>
<!doctype html>
<html lang="es" data-sidebar="<?= $colapsado ? 'colapsado' : 'expandido' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= Html::e($titulo) ?> · Casos de Prueba</title>
<link rel="stylesheet" href="<?= Vista::estatico('/assets/css/tokens.css') ?>">
<link rel="stylesheet" href="<?= Vista::estatico('/assets/css/base.css') ?>">
<link rel="stylesheet" href="<?= Vista::estatico('/assets/css/componentes.css') ?>">
<link rel="stylesheet" href="<?= Vista::estatico('/assets/css/app.css') ?>">
<script src="<?= Vista::estatico('/assets/js/tema.js') ?>"></script>
<script src="<?= Vista::estatico('/assets/js/app.js') ?>" defer></script>
</head>
<body class="app">
<a class="saltar" href="#principal">Saltar al contenido</a>

<?= Vista::capturar('partials/sidebar', ['usuario' => $usuario, 'rutaActual' => $rutaActual]) ?>
<div class="sidebar-fondo velo" id="sidebar-fondo" hidden></div>

<div class="app-cuerpo">
  <header class="app-barra">
    <button class="barra-boton" id="sidebar-alternar" type="button"
            aria-controls="sidebar" aria-expanded="<?= $colapsado ? 'false' : 'true' ?>"
            aria-label="Mostrar u ocultar menú"
            data-tooltip="Menú (Ctrl+B)" data-tooltip-lado="abajo" data-tooltip-alinear="inicio">
      <?= Icono::svg('panel-left') ?>
    </button>
    <span class="barra-separador" aria-hidden="true"></span>
    <nav aria-label="Ruta de navegación">
      <ol class="migas">
        <?php foreach ($migas as $i => $miga): ?>
          <?php $ultima = $i === array_key_last($migas); ?>
          <li>
            <?php if ($ultima): ?>
              <span aria-current="page"><?= Html::e($miga['texto']) ?></span>
            <?php elseif (isset($miga['ruta'])): ?>
              <a href="<?= Html::e($miga['ruta']) ?>"><?= Html::e($miga['texto']) ?></a>
            <?php else: ?>
              <span><?= Html::e($miga['texto']) ?></span>
            <?php endif; ?>
            <?php if (!$ultima): ?><?= Icono::svg('chevron-right', 'icono miga-separador') ?><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    </nav>
    <button class="btn btn-fantasma btn-icono barra-fin" id="tema" type="button" aria-pressed="false" aria-label="Modo oscuro"
            data-tooltip="Cambiar a oscuro" data-tooltip-lado="abajo" data-tooltip-alinear="fin">
      <?= Icono::svg('moon', 'icono tema-luna') ?><?= Icono::svg('sun', 'icono tema-sol') ?>
    </button>
  </header>

  <main class="app-principal" id="principal" tabindex="-1">
    <?= $contenido ?>
  </main>
</div>
</body>
</html>
