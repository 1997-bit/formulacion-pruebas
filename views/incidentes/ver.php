<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;

/**
 * Detalle del incidente, como el del caso: el defecto a la izquierda, con el seguimiento; los datos a la derecha.
 *
 * @var array<string, mixed> $incidente
 * @var list<array<string, mixed>> $asignables
 * @var ?string $flash
 * @var array<string, string> $errores
 * @var array<string, string> $datos
 */

$creado = new DateTime($incidente['creado_en']);
$opciones = '';
foreach ($asignables as $a) {
    $opciones .= '<option value="' . $a['id'] . '"' . ((string) $a['id'] === $datos['asignado_id'] ? ' selected' : '') . '>' . Html::e($a['nombre']) . '</option>';
}
?>
<header class="encabezado">
  <div>
    <p class="antetitulo fila">
      <span class="codigo"><?= Html::e($incidente['codigo']) ?></span>
      <?= Catalogo::insignia('estado_incidente', $incidente['estado']) ?>
      <?= $incidente['es_stopper'] ? '<span class="insignia insignia-peligro">Stopper</span>' : '' ?>
    </p>
    <h1><?= Html::e($incidente['titulo']) ?></h1>
  </div>
</header>

<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash, 'errores' => $errores]) ?>
  <div class="vista-detalle">
    <div class="pila">
      <section class="tarjeta" aria-labelledby="dt-defecto">
        <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="dt-defecto">Defecto</h2></header>
        <dl class="detalle">
          <dt>Descripción</dt><dd class="multilinea"><?= Html::e($incidente['descripcion']) ?></dd>
          <dt>Pasos para reproducir</dt><dd class="multilinea"><?= Html::e($incidente['pasos']) ?></dd>
          <dt>Resultado esperado</dt><dd class="multilinea"><?= Html::e($incidente['resultado_esperado']) ?></dd>
          <dt>Resultado obtenido</dt><dd class="multilinea"><?= Html::e($incidente['resultado_obtenido']) ?></dd>
        </dl>
      </section>

      <section class="tarjeta pila" aria-labelledby="dt-seguimiento">
        <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="dt-seguimiento">Seguimiento</h2></header>
        <form class="pila" method="post" action="/formularios/incidentes/ver">
          <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
          <input type="hidden" name="id" value="<?= (int) $incidente['id'] ?>">
          <div class="campos">
            <?= Vista::capturar('partials/campo', [
                'nombre' => 'estado',
                'etiqueta' => 'Estado',
                'tipo' => 'select',
                'requerido' => true,
                'opciones' => Catalogo::opciones('estado_incidente', $datos['estado']),
                'error' => $errores['estado'] ?? '',
            ]) ?>
            <?= Vista::capturar('partials/campo', [
                'nombre' => 'asignado_id',
                'etiqueta' => 'Asignado a',
                'tipo' => 'select',
                'opciones' => $opciones,
                'ayuda' => 'Miembros del proyecto. Vacío: sin asignar.',
                'error' => $errores['asignado_id'] ?? '',
            ]) ?>
          </div>
          <fieldset class="grupo">
            <legend>Bloqueo</legend>
            <label class="opcion"><input type="checkbox" name="es_stopper" value="1"<?= $datos['es_stopper'] === '1' ? ' checked' : '' ?>> Es stopper: impide cerrar el plan</label>
          </fieldset>
          <div class="acciones">
            <button class="btn btn-primario" type="submit">Guardar</button>
          </div>
        </form>
      </section>
    </div>

    <aside class="tarjeta" aria-label="Datos del incidente">
      <dl class="detalle detalle-apilado">
        <dt>Estado</dt><dd><?= Catalogo::insignia('estado_incidente', $incidente['estado']) ?></dd>
        <dt>Severidad</dt><dd><?= Catalogo::insignia('severidad', $incidente['severidad']) ?></dd>
        <dt>Prioridad</dt><dd><?= Catalogo::insignia('prioridad', $incidente['prioridad']) ?></dd>
        <dt>Caso</dt><dd><a class="codigo" href="/casos/resultado?id=<?= (int) $incidente['caso_id'] ?>"><?= Html::e($incidente['caso']) ?></a> <?= Html::e($incidente['caso_objetivo']) ?></dd>
        <dt>Proyecto</dt><dd><?= Html::e($incidente['proyecto']) ?></dd>
        <dt>Módulo</dt><dd><?= Html::e($incidente['modulo']) ?></dd>
        <dt>Asignado a</dt><dd><?= Html::e($incidente['asignado'] ?? 'Sin asignar') ?></dd>
        <dt>Registrado por</dt><dd><?= Html::e($incidente['autor']) ?> · <?= Fecha::legible($creado) ?> <?= $creado->format('H:i') ?></dd>
      </dl>
    </aside>
  </div>
</div>
