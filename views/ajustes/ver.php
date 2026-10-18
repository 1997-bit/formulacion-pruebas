<?php
declare(strict_types=1);

use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Sin formulario: cada opción se guarda al marcarla, app.js. Una página por pestaña
 *
 * @var string $pestana apariencia o accesibilidad
 */

$pestanas = ['apariencia' => ['Apariencia', '/ajustes'], 'accesibilidad' => ['Accesibilidad', '/ajustes/accesibilidad']];
// Radios: nombre => [título, valores]. Switches: nombre => [título, texto, ayuda, por defecto]
// Switch: role="switch", marcado = 'si'
// Switch por defecto: sin animación al cargar
$grupos = [
    'apariencia' => [
        'tema' => ['Tema', [
            'sistema' => ['Sistema', 'Igual que el sistema operativo.'],
            'light' => ['Claro', ''],
            'dark' => ['Oscuro', ''],
        ]],
        'sidebar' => ['Menú lateral', [
            'expandido' => ['Expandido', 'Íconos y nombres.'],
            'colapsado' => ['Colapsado', 'Solo íconos. También con Ctrl/⌘+B.'],
        ]],
        'densidad' => ['Densidad', [
            'comoda' => ['Cómoda', ''],
            'compacta' => ['Compacta', 'Filas más bajas en tablas y listas. Más datos en pantalla.'],
        ]],
        'hora' => ['Formato de hora', [
            '24' => ['24 horas', '14:30'],
            '12' => ['12 horas', '2:30 p. m.'],
        ]],
    ],
    'accesibilidad' => [],
];
$interruptores = [
    'accesibilidad' => [
        'atajos' => ['Teclado', 'Atajos de una tecla', '? ayuda, / filtro, C crear, G + letra ir a. Apáguelos si usa dictado por voz (WCAG 2.1.4); quedan Ctrl/⌘+B, Ctrl/⌘+K y Ctrl/⌘+Enter.', true],
        'paleta' => ['Teclado', 'Paleta de comandos', 'Ctrl/⌘+K abre una lista para ir a una página o hacer una acción escribiendo.', true],
        'subrayar' => ['Contenido', 'Subrayar enlaces', 'Los enlaces del contenido se ven sin depender del color.', false],
        'movimiento' => ['Movimiento', 'Reducir movimiento', 'Sin animaciones ni transiciones. Si el sistema operativo pide menos movimiento, ya están apagadas.', false],
    ],
];
// Ícono de cada tema
$iconos = ['sistema' => 'monitor', 'light' => 'sun', 'dark' => 'moon'];
// Un fieldset por título de switch
$porTitulo = [];
foreach ($interruptores[$pestana] ?? [] as $nombre => [$titulo, $texto, $ayuda, $defecto]) {
    $porTitulo[$titulo][$nombre] = [$texto, $ayuda, $defecto];
}
?>
<header class="encabezado">
  <div>
    <h1>Ajustes</h1>
    <p>Se guardan en este navegador.</p>
  </div>
  <div class="acciones"><button class="btn btn-secundario" type="button" data-ver-atajos>Ver atajos</button></div>
</header>
<nav class="pestanas" aria-label="Secciones de ajustes">
  <?php foreach ($pestanas as $clave => [$texto, $ruta]): ?>
    <a href="<?= $ruta ?>"<?= $clave === $pestana ? ' aria-current="page"' : '' ?>><?= $texto ?></a>
  <?php endforeach; ?>
</nav>
<div class="pila">
  <?php foreach ($grupos[$pestana] as $nombre => [$titulo, $valores]): ?>
    <fieldset class="tarjeta grupo ajuste">
      <legend><?= $titulo ?></legend>
      <?php foreach ($valores as $valor => [$texto, $ayuda]): ?>
        <label class="opcion">
          <?php if ($nombre === 'tema'): ?><span class="opcion-icono"><?= Icono::svg($iconos[$valor]) ?></span><?php endif; ?>
          <span><?= $texto ?><?php if ($ayuda !== ''): ?><small><?= $ayuda ?></small><?php endif; ?></span>
          <input type="radio" name="<?= $nombre ?>" value="<?= $valor ?>" data-ajuste>
        </label>
      <?php endforeach; ?>
    </fieldset>
  <?php endforeach; ?>
  <?php foreach ($porTitulo as $titulo => $lista): ?>
    <fieldset class="tarjeta grupo ajuste">
      <legend><?= Html::e($titulo) ?></legend>
      <?php foreach ($lista as $nombre => [$texto, $ayuda, $defecto]): ?>
        <label class="ajuste-switch">
          <span><?= $texto ?><small><?= $ayuda ?></small></span>
          <input class="switch" type="checkbox" role="switch" name="<?= $nombre ?>" value="si" data-ajuste<?= $defecto ? ' checked' : '' ?>>
        </label>
      <?php endforeach; ?>
    </fieldset>
  <?php endforeach; ?>
  <?php if ($pestana === 'accesibilidad'): ?>
    <section class="tarjeta" aria-labelledby="h-atajos">
      <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="h-atajos">Atajos de esta app</h2></header>
      <div data-lista-atajos></div>
    </section>
  <?php endif; ?>
  <output class="ajuste-estado" id="ajuste-estado"></output>
</div>
