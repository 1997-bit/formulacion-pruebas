<?php
declare(strict_types=1);

// Sin formulario: cada opción se guarda en localStorage al marcarla (app.js, data-ajuste).
$opciones = [
    'tema' => ['Tema', [
        'sistema' => ['Sistema', 'Igual que el sistema operativo.'],
        'light' => ['Claro', ''],
        'dark' => ['Oscuro', ''],
    ]],
    'atajos' => ['Atajos de teclado', []],
];
// Switch (role="switch"): marcado = 'si'. Van al final de la tarjeta indicada. Salen marcados (el valor por defecto): así no se animan al cargar.
$interruptores = [
    'atajos' => [
        'atajos' => ['Atajos de una tecla', '? ayuda, / filtro, C crear, G + letra ir a. Apáguelos si usa dictado por voz (WCAG 2.1.4); quedan Ctrl+B y Ctrl+Enter.'],
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
          <input class="switch" type="checkbox" role="switch" name="<?= $clave ?>" value="si" data-ajuste checked>
        </label>
      <?php endforeach; ?>
    </fieldset>
  <?php endforeach; ?>
  <p role="status" class="ajuste-estado" id="ajuste-estado"></p>
</div>
