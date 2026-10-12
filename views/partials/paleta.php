<?php
declare(strict_types=1);

use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Paleta de comandos (Ctrl/⌘+K, atajos.js). PHP escribe las opciones fijas; JS suma Recientes y Esta página, y filtra.
 * Patrón ARIA combobox: el foco queda en el campo, la opción activa va en aria-activedescendant.
 *
 * @var array{rol: int} $usuario
 */

$rol = $usuario['rol'];
$permitido = fn (array $x): bool => !isset($x['rol']) || $x['rol'] === $rol;
// [grupo, texto, ruta o acción de JS]. Sin íconos: la paleta va en cada página y cada SVG pesa.
$opciones = [];
foreach (require RAIZ . '/config/menu.php' as $grupo) {
    if (!$permitido($grupo)) {
        continue;
    }
    foreach ($grupo['elementos'] as $el) {
        if (!$permitido($el)) {
            continue;
        }
        foreach (isset($el['hijos']) ? array_filter($el['hijos'], $permitido) : [$el] as $hijo) {
            $texto = isset($el['hijos']) ? $el['texto'] . ' › ' . $hijo['texto'] : $el['texto'];
            $opciones[] = ['Ir a', $texto, $hijo['ruta']];
        }
    }
}
$opciones[] = ['Ir a', 'Ajustes › Apariencia', '/ajustes'];
$opciones[] = ['Ir a', 'Ajustes › Accesibilidad', '/ajustes/accesibilidad'];
$opciones[] = ['Ir a', 'Casos › Tablero', '/casos/listar?vista=tablero'];
$opciones[] = ['Ir a', 'Casos › Cronograma', '/casos/listar?vista=cronograma'];
$opciones[] = ['Ir a', 'Incidentes › Tablero', '/formularios/incidentes?vista=tablero'];
$opciones[] = ['Ir a', 'Incidentes asignados a mí', '/formularios/incidentes?mios=1'];
$opciones[] = ['Acciones', 'Cambiar tema claro u oscuro', 'accion:tema'];
$opciones[] = ['Acciones', 'Mostrar u ocultar menú', 'accion:menu'];
$opciones[] = ['Acciones', 'Ver atajos de teclado', 'accion:atajos'];
$opciones[] = ['Acciones', 'Cerrar sesión', '/salir'];
?>
<dialog class="dialogo paleta" id="paleta" aria-label="Paleta de comandos">
  <div class="paleta-campo">
    <?= Icono::svg('search') ?>
    <input class="paleta-entrada" type="text" role="combobox" aria-expanded="true" aria-controls="paleta-lista" aria-autocomplete="list"
           aria-label="Buscar página o acción" placeholder="Buscar página o acción…" autocomplete="off" spellcheck="false">
    <kbd>Esc</kbd>
  </div>
  <ul class="paleta-lista" id="paleta-lista" role="listbox" aria-label="Resultados">
    <?php foreach ($opciones as $i => [$grupo, $texto, $destino]): ?>
      <li role="option" id="paleta-<?= $i ?>" data-grupo="<?= $grupo ?>" data-destino="<?= Html::e($destino) ?>"><span><?= Html::e($texto) ?></span></li>
    <?php endforeach; ?>
  </ul>
  <p class="paleta-vacio" hidden>Sin resultados.</p>
  <p class="solo-lector" role="status" id="paleta-estado"></p>
</dialog>
