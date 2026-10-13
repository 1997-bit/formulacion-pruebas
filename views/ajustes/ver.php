<?php
declare(strict_types=1);

use App\Helpers\Html;

/**
 * Sin formulario: cada opción se guarda al marcarla, app.js y data-ajuste. Una página por pestaña.
 *
 * @var string $pestana apariencia o accesibilidad
 */

$pestanas = ['apariencia' => ['Apariencia', '/ajustes'], 'accesibilidad' => ['Accesibilidad', '/ajustes/accesibilidad']];
// Radios: nombre => [título, valores]. Switches: nombre => [título, texto, ayuda, marcado por defecto].
// Switch: role="switch", marcado = 'si'.
// Switch con su valor por defecto: no se anima al cargar.
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
    ],
    'accesibilidad' => [
        'movimiento' => ['Movimiento', [
            'sistema' => ['Sistema', 'Igual que el sistema operativo.'],
            'reducido' => ['Reducir', 'Sin animaciones ni transiciones.'],
        ]],
    ],
];
$interruptores = [
    'accesibilidad' => [
        'atajos' => ['Teclado', 'Atajos de una tecla', '? ayuda, / filtro, C crear, G + letra ir a. Apáguelos si usa dictado por voz (WCAG 2.1.4); quedan Ctrl/⌘+B, Ctrl/⌘+K y Ctrl/⌘+Enter.', true],
        'paleta' => ['Teclado', 'Paleta de comandos', 'Ctrl/⌘+K abre una lista para ir a una página o hacer una acción escribiendo.', true],
        'subrayar' => ['Contenido', 'Subrayar enlaces', 'Los enlaces del contenido se ven sin depender del color.', false],
    ],
];
// Los switches se agrupan por su título en un fieldset cada uno.
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
          <input type="radio" name="<?= $nombre ?>" value="<?= $valor ?>" data-ajuste>
          <span><?= $texto ?><?php if ($ayuda !== ''): ?><small><?= $ayuda ?></small><?php endif; ?></span>
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
  <p role="status" class="ajuste-estado" id="ajuste-estado"></p>
</div>
