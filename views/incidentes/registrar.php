<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 10 de la paleta. Sin caso, primero se elige uno.
 *
 * @var ?array<string, mixed> $caso
 * @var ?list<array<string, mixed>> $casos  solo sin caso
 * @var ?list<array<string, mixed>> $asignables
 * @var ?array<string, string> $errores
 * @var ?array<string, mixed> $datos
 */

$errores ??= [];
$datos ??= [];
$campo = fn (string $nombre, string $etiqueta, array $extra = []): string => Vista::capturar('partials/campo', $extra + [
    'nombre' => $nombre,
    'etiqueta' => $etiqueta,
    'valor' => $datos[$nombre] ?? '',
    'error' => $errores[$nombre] ?? '',
    'requerido' => true,
]);
// Radios de un catálogo; error en el fieldset (RNF-07).
$radios = function (string $nombre, string $legenda) use ($datos, $errores): string {
    $error = $errores[$nombre] ?? '';
    $html = '<fieldset class="grupo"' . ($error !== '' ? ' aria-describedby="f-' . $nombre . '-error"' : '') . '>'
        . '<legend>' . $legenda . ' <span class="requerido" aria-hidden="true">*</span></legend><div class="grupo grupo-fila">';
    foreach (Catalogo::valores($nombre) as $clave => $valor) {
        $html .= '<label class="opcion"><input type="radio" name="' . $nombre . '" value="' . $clave . '"'
            . ($clave === array_key_first(Catalogo::valores($nombre)) ? ' required' : '')
            . ($error !== '' ? ' aria-invalid="true"' : '')
            . ((string) $clave === ($datos[$nombre] ?? '') ? ' checked' : '') . '> ' . Html::e($valor['texto']) . '</label>';
    }
    $html .= '</div>';
    if ($error !== '') {
        $html .= '<p class="campo-error" id="f-' . $nombre . '-error">' . Icono::svg('circle-x') . Html::e($error) . '</p>';
    }

    return $html . '</fieldset>';
};
?>
<header class="encabezado">
  <div>
    <?php if ($caso !== null): ?>
      <p class="antetitulo fila"><span class="codigo"><?= Html::e($caso['codigo']) ?></span> <?= Catalogo::insignia('estado_caso', $caso['estado']) ?></p>
    <?php endif; ?>
    <h1>Registrar incidente</h1>
    <p>Formulario 10</p>
  </div>
</header>

<?php if ($caso === null): ?>
  <?php
  $porProyecto = [];
  foreach ($casos as $c) {
      $porProyecto[$c['proyecto']][] = $c;
  }
  ?>
  <?php if (!$casos): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>Todavía no hay casos</h2>
      <p>Un incidente sale de un caso de prueba. Registre un caso primero.</p>
      <a class="btn btn-primario" href="/casos/registrar"><?= Icono::svg('plus') ?> Registrar caso</a>
    </div>
  <?php else: ?>
    <form class="tarjeta pila" method="get" action="/formularios/incidentes/registrar">
      <div class="campo">
        <label for="f-caso">Caso de prueba <span class="requerido" aria-hidden="true">*</span></label>
        <div class="select">
          <select class="control" id="f-caso" name="caso" required aria-describedby="f-caso-ayuda">
            <option value="">Elegir…</option>
            <?php foreach ($porProyecto as $proyecto => $lista): ?>
              <?= count($porProyecto) > 1 ? '<optgroup label="' . Html::e($proyecto) . '">' : '' ?>
              <?php foreach ($lista as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= Html::e($c['codigo'] . ' · ' . $c['objetivo']) ?></option>
              <?php endforeach; ?>
              <?= count($porProyecto) > 1 ? '</optgroup>' : '' ?>
            <?php endforeach; ?>
          </select>
        </div>
        <p class="campo-ayuda" id="f-caso-ayuda">El incidente queda en el proyecto del caso.</p>
      </div>
      <div class="acciones">
        <button class="btn btn-primario" type="submit">Continuar</button>
      </div>
    </form>
  <?php endif; ?>
<?php else: ?>
  <div class="pila">
    <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
    <form class="tarjeta pila" method="post" action="/formularios/incidentes/registrar">
      <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
      <input type="hidden" name="caso_id" value="<?= (int) $caso['id'] ?>">
      <div class="campos">
        <div class="campo">
          <label for="f-codigo">ID del incidente</label>
          <input class="control codigo" id="f-codigo" placeholder="BUG-001" disabled aria-describedby="f-codigo-ayuda">
          <p class="campo-ayuda" id="f-codigo-ayuda">Se genera al guardar: BUG y un consecutivo del proyecto.</p>
        </div>
        <?= $campo('modulo', 'Módulo', ['atributos' => 'maxlength="100"', 'ayuda' => 'Componente afectado. Sale del caso; se puede cambiar.']) ?>
      </div>
      <?= $campo('titulo', 'Título', ['atributos' => 'maxlength="150"']) ?>
      <?= $radios('severidad', 'Severidad') ?>
      <?= $radios('prioridad', 'Prioridad') ?>
      <?= $campo('descripcion', 'Descripción', ['tipo' => 'textarea', 'ayuda' => 'Detalle del problema.']) ?>
      <?= $campo('pasos', 'Pasos para reproducir', ['tipo' => 'textarea']) ?>
      <div class="campos">
        <?= $campo('resultado_esperado', 'Resultado esperado', ['tipo' => 'textarea']) ?>
        <?= $campo('resultado_obtenido', 'Resultado obtenido', ['tipo' => 'textarea']) ?>
      </div>
      <div class="campos">
        <?= $campo('estado', 'Estado', ['tipo' => 'select', 'opciones' => Catalogo::opciones('estado_incidente', $datos['estado'] ?? null)]) ?>
        <?php
        $opciones = '';
        foreach ($asignables as $a) {
            $opciones .= '<option value="' . $a['id'] . '"' . ((string) $a['id'] === ($datos['asignado_id'] ?? '') ? ' selected' : '') . '>' . Html::e($a['nombre']) . '</option>';
        }
        ?>
        <?= $campo('asignado_id', 'Asignado a', ['tipo' => 'select', 'requerido' => false, 'opciones' => $opciones, 'ayuda' => 'Miembros del proyecto. Vacío: sin asignar.']) ?>
      </div>
      <fieldset class="grupo">
        <legend>Bloqueo</legend>
        <label class="opcion"><input type="checkbox" name="es_stopper" value="1"<?= ($datos['es_stopper'] ?? '') === '1' ? ' checked' : '' ?>> Es stopper: impide cerrar el plan</label>
      </fieldset>
      <p class="campo-ayuda"><span class="requerido" aria-hidden="true">*</span> Campo obligatorio</p>
      <div class="acciones">
        <a class="btn btn-secundario" href="/casos/resultado?id=<?= (int) $caso['id'] ?>">Cancelar</a>
        <button class="btn btn-primario" type="submit">Registrar incidente</button>
      </div>
    </form>
  </div>
<?php endif; ?>
