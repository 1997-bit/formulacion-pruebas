<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 10. Sin caso: primero elegir uno
 *
 * @var ?array<string, mixed> $caso
 * @var ?string $codigo  solo sin caso: lo buscado
 * @var ?list<array<string, mixed>> $casos  solo sin caso: los que tienen ese código
 * @var ?list<array<string, mixed>> $fallidos  solo sin caso: FAULT que coinciden
 * @var ?list<array<string, mixed>> $asignables
 * @var ?array<string, string> $errores
 * @var ?array<string, mixed> $datos
 * @var ?list<string> $commits  ya usados en el proyecto
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
// Radios de catálogo en segmentado con el color de la variante. Error en el fieldset, RNF-07
$radios = function (string $nombre, string $legenda) use ($datos, $errores): string {
    $error = $errores[$nombre] ?? '';
    $html = '<fieldset class="grupo"' . ($error !== '' ? ' aria-describedby="f-' . $nombre . '-error"' : '') . '>'
        . '<legend>' . $legenda . ' <span class="requerido" aria-hidden="true">*</span></legend><div class="segmentado">';
    foreach (Catalogo::valores($nombre) as $clave => $valor) {
        $html .= '<label><input type="radio" name="' . $nombre . '" value="' . $clave . '"'
            . ($clave === array_key_first(Catalogo::valores($nombre)) ? ' required' : '')
            . ($error !== '' ? ' aria-invalid="true"' : '')
            . ((string) $clave === ($datos[$nombre] ?? '') ? ' checked' : '') . '>'
            . '<span class="punto insignia-' . Html::e($valor['variante']) . '" aria-hidden="true"></span>' . Html::e($valor['texto']) . '</label>';
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
    <p><?= $caso === null ? 'Elija el caso que falló.' : 'Describa el defecto para que otra persona lo pueda reproducir.' ?></p>
  </div>
</header>

<?php if ($caso === null): ?>
  <?php $sinCoincidencia = $codigo !== '' && !$casos && !$fallidos; ?>
  <div class="pila">
    <search aria-label="Buscar caso">
      <form class="filtros" method="get" action="/formularios/incidentes/registrar">
        <div class="campo">
          <label for="f-codigo">Buscar caso</label>
          <input class="control" type="search" id="f-codigo" name="codigo" value="<?= Html::e($codigo) ?>" maxlength="100" placeholder="SIS-001 o parte del objetivo"
                 aria-describedby="f-codigo-ayuda<?= $sinCoincidencia ? ' f-codigo-error' : '' ?>"<?= $sinCoincidencia ? ' aria-invalid="true"' : '' ?>>
          <p class="campo-ayuda" id="f-codigo-ayuda">Un código exacto abre el formulario. El incidente queda en el proyecto del caso.</p>
          <?php if ($sinCoincidencia): ?>
            <p class="campo-error" id="f-codigo-error"><?= Icono::svg('circle-x') ?>Ningún caso FAULT coincide en sus proyectos.</p>
          <?php endif; ?>
        </div>
        <div class="acciones">
          <?php if ($codigo !== ''): ?><a class="btn btn-fantasma" href="/formularios/incidentes/registrar">Limpiar</a><?php endif; ?>
          <button class="btn btn-secundario" type="submit">Buscar</button>
        </div>
      </form>
    </search>
    <?php if ($casos): ?>
      <section class="tarjeta pila" aria-labelledby="t-varios">
        <h2 id="t-varios">El código <?= Html::e($codigo) ?> está en varios proyectos</h2>
        <ul class="pila">
          <?php foreach ($casos as $c): ?>
            <li><a href="/formularios/incidentes/registrar?caso=<?= (int) $c['id'] ?>"><?= Html::e($c['proyecto'] . ' · ' . $c['codigo'] . ' · ' . $c['objetivo']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php elseif ($fallidos): ?>
      <div class="tabla-contenedor">
        <table class="tabla tabla-tarjetas">
          <caption class="solo-lector">Casos FAULT</caption>
          <thead>
            <tr><th scope="col">Caso</th><th scope="col">Objetivo</th><th scope="col">Proyecto</th><th scope="col">Incidentes abiertos</th><th scope="col">Anotado</th></tr>
          </thead>
          <tbody>
            <?php foreach ($fallidos as $c): ?>
              <tr>
                <td data-columna="Caso"><span class="codigo"><?= Html::e($c['codigo']) ?></span></td>
                <td data-columna="Objetivo" class="celda-larga"><a href="/formularios/incidentes/registrar?caso=<?= (int) $c['id'] ?>"><?= Html::e($c['objetivo']) ?></a></td>
                <td data-columna="Proyecto"><?= Html::e($c['proyecto']) ?></td>
                <td data-columna="Incidentes abiertos" class="num"><?= (int) $c['abiertos'] ?: '<span class="sin-dato">Ninguno</span>' ?></td>
                <td data-columna="Anotado"><?= $c['anotado_en'] === null ? '<span class="sin-dato">—</span>' : Fecha::relativa(new DateTimeImmutable($c['anotado_en']), new DateTimeImmutable()) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php elseif ($codigo === ''): ?>
      <div class="vacio">
        <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
        <h2>No hay casos FAULT</h2>
        <p>Un incidente sale de un caso que falló. Busque el caso por código si no está anotado como FAULT.</p>
      </div>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="pila">
    <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
    <?php // Grupos con legend, igual que el formulario del caso ?>
    <form class="tarjeta pila" method="post" action="/formularios/incidentes/registrar">
      <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
      <input type="hidden" name="envio" value="<?= Csrf::envio() ?>">
      <input type="hidden" name="caso_id" value="<?= (int) $caso['id'] ?>">
      <fieldset class="seccion pila">
        <legend class="titulo-seccion">Qué pasó</legend>
        <?= $campo('titulo', 'Título', ['atributos' => 'maxlength="150"', 'ayuda' => 'Una frase: qué falla y dónde.']) ?>
        <?= $campo('descripcion', 'Descripción', ['tipo' => 'textarea', 'ayuda' => 'Detalle del problema.']) ?>
        <?= $campo('pasos', 'Pasos para reproducir', ['tipo' => 'textarea']) ?>
        <div class="campos">
          <?= $campo('resultado_esperado', 'Resultado esperado', ['tipo' => 'textarea']) ?>
          <?= $campo('resultado_obtenido', 'Resultado obtenido', ['tipo' => 'textarea']) ?>
        </div>
      </fieldset>

      <hr class="separador">
      <fieldset class="seccion pila">
        <legend class="titulo-seccion">Clasificación</legend>
        <?= $radios('severidad', 'Severidad') ?>
        <?= $radios('prioridad', 'Prioridad') ?>
        <label class="opcion"><input type="checkbox" name="es_stopper" value="1"<?= ($datos['es_stopper'] ?? '') === '1' ? ' checked' : '' ?>> Es stopper: impide cerrar el plan</label>
      </fieldset>

      <hr class="separador">
      <fieldset class="seccion pila">
        <legend class="titulo-seccion">Dónde se encontró</legend>
        <div class="campos">
          <div class="campo">
            <label for="f-codigo">ID del incidente</label>
            <input class="control codigo" id="f-codigo" placeholder="BUG-001" disabled aria-describedby="f-codigo-ayuda">
            <p class="campo-ayuda" id="f-codigo-ayuda">Se genera al guardar: BUG y un consecutivo del proyecto.</p>
          </div>
          <?= $campo('modulo', 'Módulo', ['atributos' => 'maxlength="100"', 'ayuda' => 'Componente afectado. Sale del caso; se puede cambiar.']) ?>
          <?= $campo('commit_afectado', 'Commit afectado', [
              'clase' => 'codigo',
              'ayuda' => 'Hash del build donde sale el defecto. Sale del caso; se puede cambiar.',
              'atributos' => 'list="commits-proyecto" maxlength="40" pattern="[0-9a-fA-F]{7,40}" autocomplete="off" spellcheck="false" placeholder="3426060"',
          ]) ?>
        </div>
        <datalist id="commits-proyecto"><?php foreach ($commits ?? [] as $c): ?><option value="<?= Html::e($c) ?>"><?php endforeach; ?></datalist>
      </fieldset>

      <hr class="separador">
      <fieldset class="seccion pila">
        <legend class="titulo-seccion">Seguimiento</legend>
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
      </fieldset>
      <p class="campo-ayuda"><span class="requerido" aria-hidden="true">*</span> Campo obligatorio</p>
      <div class="acciones">
        <a class="btn btn-secundario" href="/casos/resultado?id=<?= (int) $caso['id'] ?>">Cancelar</a>
        <button class="btn btn-primario" type="submit">Registrar incidente</button>
      </div>
    </form>
  </div>
<?php endif; ?>
