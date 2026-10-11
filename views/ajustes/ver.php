<?php
declare(strict_types=1);

// Sin formulario: cada opción se guarda en localStorage al marcarla (app.js, data-ajuste).
$opciones = [
    'tema' => ['Tema', [
        'sistema' => ['Sistema', 'Igual que el sistema operativo.'],
        'light' => ['Claro', ''],
        'dark' => ['Oscuro', ''],
    ]],
    'atajos' => ['Atajos de teclado', [
        'estandar' => ['Estándar', 'Los de GitHub, Jira y Linear: ? ayuda, / filtro, J/K filas, C crear, G + letra ir a.'],
        'avanzado' => ['Avanzados', 'Estándar más los de LazyVim: líder Space, H/L páginas, gg/G primera y última fila, 5j baja 5 filas.'],
        'ninguno' => ['Solo con Ctrl', 'Apaga los atajos de una tecla (WCAG 2.1.4). Quedan Ctrl+B, Ctrl+Enter y Esc.'],
    ]],
];
// Switch (role="switch"): marcado = 'si'. Van al final de la tarjeta indicada.
$interruptores = [
    'atajos' => [
        'pista' => ['Pista de teclas', 'Después de G o Space, una ventana dice qué teclas siguen (which-key de LazyVim). Apagada, la secuencia vence a 1 s, como en Vim.'],
    ],
];
?>
<header class="encabezado">
  <div>
    <h1>Ajustes</h1>
    <p>Se guardan en este navegador.</p>
  </div>
  <div class="acciones"><button class="btn btn-secundario" type="button" data-ver-atajos>Ver atajos</button></div>
</header>
<div class="pila">
  <?php foreach ($opciones as $nombre => [$titulo, $valores]): ?>
    <fieldset class="tarjeta grupo ajuste">
      <legend><?= $titulo ?></legend>
      <?php foreach ($valores as $valor => [$texto, $ayuda]): ?>
        <label class="opcion">
          <input type="radio" name="<?= $nombre ?>" value="<?= $valor ?>" data-ajuste>
          <span><?= $texto ?><?php if ($ayuda !== ''): ?><small><?= $ayuda ?></small><?php endif; ?></span>
        </label>
      <?php endforeach; ?>
      <?php foreach ($interruptores[$nombre] ?? [] as $clave => [$texto, $ayuda]): ?>
        <label class="ajuste-switch">
          <span><?= $texto ?><small><?= $ayuda ?></small></span>
          <input class="switch" type="checkbox" role="switch" name="<?= $clave ?>" value="si" data-ajuste>
        </label>
      <?php endforeach; ?>
    </fieldset>
  <?php endforeach; ?>
  <p role="status" class="ajuste-estado" id="ajuste-estado"></p>
</div>
