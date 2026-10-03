<?php
declare(strict_types=1);

use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;
use App\Services\CasoServicio;

/**
 * Estado, resultado y evidencias nuevas (RF-24). Va dentro de un form multipart.
 *
 * @var array<string, mixed> $datos  evidencias: tipos marcados
 * @var array<string, string> $errores
 * @var ?int $previas  evidencias que ya tiene el caso
 */

$previas ??= 0;
$marcadas = $datos['evidencias'] ?? [];
$campo = fn (string $nombre, string $etiqueta, array $extra = []): string => Vista::capturar('partials/campo', $extra + [
    'nombre' => $nombre,
    'etiqueta' => $etiqueta,
    'valor' => $datos[$nombre] ?? '',
    'error' => $errores[$nombre] ?? '',
]);
$marcado = fn (string $campo, int $valor): string => (string) $valor === ($datos[$campo] ?? '') ? ' checked' : '';
// Error y ayuda enlazados (RNF-07); en radios y casillas van en el fieldset.
$describir = fn (string $id, string $nombre): string => isset($errores[$nombre])
    ? ' aria-invalid="true" aria-describedby="' . $id . '-error ' . $id . '-ayuda"'
    : ' aria-describedby="' . $id . '-ayuda"';
$error = fn (string $id, string $nombre): string => isset($errores[$nombre])
    ? '<p class="campo-error" id="' . $id . '-error">' . Icono::svg('circle-x') . Html::e($errores[$nombre]) . '</p>'
    : '';
$aria = fn (string $nombre): string => isset($errores[$nombre]) ? ' aria-describedby="f-' . $nombre . '-error"' : '';
// Un bloque por tipo marcado: ícono, control, atributos, ayuda y ejemplo.
$evidencias = [
    1 => ['image', 'file', 'accept=".png,.jpg,.jpeg" data-vista-previa="f-captura-previa"', 'PNG o JPG, máximo 5 MB.', 'Pantalla de login con el mensaje de error'],
    2 => ['terminal', 'file', 'accept=".txt,.log"', 'Salida de consola o del servidor: TXT o LOG, máximo 5 MB.', 'Registro del servidor durante el intento'],
    4 => ['link', 'url', 'maxlength="500" placeholder="https://…"', 'Video, carpeta de Drive o ejecución en CI.', 'Video del intento de inicio de sesión'],
];
?>
<fieldset class="resultado"<?= $aria('estado') ?>>
  <legend>Estado <span class="requerido" aria-hidden="true">*</span></legend>
  <label class="resultado-opcion resultado-pendiente">
    <input type="radio" name="estado" value="0" required<?= $marcado('estado', 0) ?>>
    <?= Icono::svg('circle-alert') ?> Pendiente
  </label>
  <label class="resultado-opcion resultado-ok">
    <input type="radio" name="estado" value="1"<?= $marcado('estado', 1) ?>>
    <?= Icono::svg('circle-check') ?> OK
  </label>
  <label class="resultado-opcion resultado-fault">
    <input type="radio" name="estado" value="2"<?= $marcado('estado', 2) ?>>
    <?= Icono::svg('circle-x') ?> FAULT
  </label>
  <?= $error('f-estado', 'estado') ?>
</fieldset>
<div class="campos">
  <?= $campo('resultado_obtenido', 'Resultado obtenido', ['tipo' => 'textarea', 'requerido' => false, 'atributos' => 'placeholder="Qué hizo el sistema"']) ?>
  <?= $campo('observaciones', 'Observaciones', ['tipo' => 'textarea', 'requerido' => false, 'atributos' => 'placeholder="Notas adicionales"']) ?>
</div>

<fieldset class="grupo" aria-describedby="<?= isset($errores['evidencias']) ? 'f-evidencias-error ' : '' ?>f-evidencias-ayuda">
  <legend>Evidencias</legend>
  <div class="grupo grupo-fila">
    <?php foreach (Catalogo::valores('tipo_evidencia') as $clave => $tipo): ?>
      <label class="opcion"><input type="checkbox" name="evidencias[]" value="<?= $clave ?>"<?= in_array((string) $clave, $marcadas, true) ? ' checked' : '' ?>> <?= Icono::svg($evidencias[$clave][0]) ?> <?= Html::e($tipo['texto']) ?></label>
    <?php endforeach; ?>
  </div>
  <p class="campo-ayuda" id="f-evidencias-ayuda">Marque las que va a agregar. Con OK o FAULT hace falta al menos una<?= $previas > 0 ? '; el caso ya tiene ' . $previas : '' ?>.</p>
  <?= $error('f-evidencias', 'evidencias') ?>
</fieldset>
<?php foreach ($evidencias as $clave => [$icono, $control, $atributos, $ayuda, $ejemplo]): ?>
  <?php
  $nombre = CasoServicio::EVIDENCIAS[$clave];
  $id = 'f-' . $nombre;
  ?>
  <div class="tarjeta pila" data-cuando="evidencias[]=<?= $clave ?>">
    <p class="titulo-seccion fila" style="margin:0;justify-content:flex-start"><?= Icono::svg($icono) ?> <?= Html::e(Catalogo::texto('tipo_evidencia', $clave)) ?></p>
    <div class="campos">
      <div class="campo">
        <label for="<?= $id ?>"><?= $control === 'url' ? 'Dirección' : 'Archivo' ?> <span class="requerido" aria-hidden="true">*</span></label>
        <input class="control" id="<?= $id ?>" name="evidencia_<?= $nombre ?>" type="<?= $control ?>" <?= $atributos ?><?= $control === 'url' ? ' value="' . Html::e($datos['evidencia_enlace'] ?? '') . '"' : '' ?><?= $describir($id, 'evidencia_' . $nombre) ?>>
        <p class="campo-ayuda" id="<?= $id ?>-ayuda"><?= Html::e($ayuda) ?></p>
        <?= $error($id, 'evidencia_' . $nombre) ?>
        <?php if ($clave === 1): ?><img id="f-captura-previa" class="vista-previa" alt="" hidden><?php endif; ?>
      </div>
      <div class="campo">
        <label for="<?= $id ?>-desc">Qué muestra <span class="requerido" aria-hidden="true">*</span></label>
        <input class="control" id="<?= $id ?>-desc" name="descripcion_<?= $nombre ?>" maxlength="255" placeholder="<?= Html::e($ejemplo) ?>" value="<?= Html::e($datos['descripcion_' . $nombre] ?? '') ?>"<?= $describir($id . '-desc', 'descripcion_' . $nombre) ?>>
        <p class="campo-ayuda" id="<?= $id ?>-desc-ayuda">Texto alternativo (RNF-07).</p>
        <?= $error($id . '-desc', 'descripcion_' . $nombre) ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
