<?php
declare(strict_types=1);

use App\Core\Vista;
use App\Helpers\Html;
use App\Helpers\Icono;
use App\Helpers\Impresion;
use App\Services\RequerimientoServicio;

/**
 * @var string $titulo
 * @var string $contenido
 * @var array{nombre: string, usuario: string, rol: int} $usuario
 * @var list<array{texto: string, ruta?: string}> $migas
 * @var array{titulo: string, proyecto: string, marca: string}|null $documento  documento imprimible: además firmas al final
 */

$rutaActual = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
// Sidebar desde la cookie, sin parpadeo
$colapsado = ($_COOKIE['sidebar'] ?? '') === 'colapsado';
// Para el selector de proyecto y la paleta
$proyectos = RequerimientoServicio::proyectos($usuario);
$activo = array_values(array_filter($proyectos, fn (array $p): bool => $p['id'] === ($usuario['activo'] ?? null)))[0] ?? null;
// Encabezado y pie al imprimir. Sin documento: título y proyecto activo
$hoja = Impresion::variables($documento ?? ['titulo' => $titulo, 'proyecto' => $activo['nombre'] ?? 'Todos los proyectos', 'marca' => ''], $usuario['nombre'], new DateTimeImmutable());
?>
<!doctype html>
<html lang="es" data-sidebar="<?= $colapsado ? 'colapsado' : 'expandido' ?>" style="<?= Html::e($hoja) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#FFFFFF" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#18181B" media="(prefers-color-scheme: dark)">
<?php // PDF: el navegador usa el <title> como nombre del archivo ?>
<title><?= empty($errores) ? '' : 'Error: ' ?><?= Html::e(isset($documento) ? $documento['titulo'] . ' · ' . $documento['proyecto'] : $titulo) ?> · Safeguard</title>
<link rel="icon" type="image/svg+xml" href="<?= Vista::estatico('/favicon.svg') ?>">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="preload" href="/assets/fonts/InterVariable.woff2" as="font" type="font/woff2" crossorigin>
<?php foreach (Vista::estilos() as $css): ?>
<link rel="stylesheet" href="<?= Vista::estatico('/assets/css/' . $css . '.css') ?>">
<?php endforeach ?>
<script src="<?= Vista::estatico('/assets/js/tema.js') ?>"></script>
<script type="module" src="<?= Vista::estatico('/assets/js/clave.js') ?>"></script>
<script type="module" src="<?= Vista::estatico('/assets/js/app.js') ?>"></script>
<script type="module" src="<?= Vista::estatico('/assets/js/atajos.js') ?>"></script>
<?php // Precarga al pasar el mouse. Solo GET: todo cambio es POST ?>
<script type="speculationrules">{"prefetch": [{"where": {"and": [{"href_matches": "/*"}, {"not": {"href_matches": "/evidencias/*"}}, {"not": {"selector_matches": "[target], [download]"}}]}, "eagerness": "moderate"}]}</script>
</head>
<body class="app">
<?= Vista::capturar('partials/sidebar', ['usuario' => $usuario, 'rutaActual' => $rutaActual, 'proyectos' => $proyectos, 'activo' => $activo]) ?>
<div class="sidebar-fondo velo" id="sidebar-fondo" hidden></div>

<div class="app-cuerpo">
  <header class="app-barra">
    <button class="barra-boton" id="sidebar-alternar" type="button"
            aria-controls="sidebar" aria-expanded="<?= $colapsado ? 'false' : 'true' ?>"
            aria-label="Mostrar u ocultar menú"
            data-atajo="Mod+b" data-atajo-texto="Mostrar u ocultar menú" data-tooltip="Menú" data-tooltip-lado="abajo" data-tooltip-alinear="inicio">
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
  </header>

  <main class="app-principal" id="principal" tabindex="-1">
    <?= $contenido ?>
    <?php if (isset($documento)): ?><?= Vista::capturar('partials/firmas') ?><?php endif; ?>
  </main>
</div>
<?= Vista::capturar('partials/paleta', ['usuario' => $usuario, 'proyectos' => $proyectos]) ?>
</body>
</html>
