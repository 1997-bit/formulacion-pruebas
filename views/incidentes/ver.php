<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Defecto y seguimiento a la izquierda, datos a la derecha
 *
 * @var array<string, mixed> $incidente
 * @var list<array<string, mixed>> $asignables
 * @var ?string $flash
 * @var array<string, string> $errores
 * @var array<string, string> $datos
 * @var array{id: int} $usuario
 * @var list<string> $commits  ya usados en el proyecto
 */

$creado = new DateTime($incidente['creado_en']);
$opciones = '';
foreach ($asignables as $a) {
    $opciones .= '<option value="' . $a['id'] . '"' . ((string) $a['id'] === $datos['asignado_id'] ? ' selected' : '') . '>' . Html::e($a['nombre']) . '</option>';
}
?>
<header class="encabezado" data-reciente="<?= Html::e($incidente['codigo']) ?>" data-reciente-texto="<?= Html::e($incidente['titulo']) ?>">
  <div>
    <p class="antetitulo fila">
      <span class="codigo"><?= Html::e($incidente['codigo']) ?></span>
      <?= Catalogo::insignia('estado_incidente', $incidente['estado']) ?>
      <?= $incidente['es_stopper'] ? '<span class="insignia insignia-peligro">Stopper</span>' : '' ?>
    </p>
    <h1><?= Html::e($incidente['titulo']) ?></h1>
  </div>
  <?php if ($incidente['asignado_id'] !== $usuario['id'] && in_array($usuario['id'], array_column($asignables, 'id'), true)): ?>
    <form class="acciones" method="post" action="/formularios/incidentes/ver">
      <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
      <input type="hidden" name="id" value="<?= (int) $incidente['id'] ?>">
      <input type="hidden" name="estado" value="<?= (int) $incidente['estado'] ?>">
      <input type="hidden" name="asignado_id" value="<?= (int) $usuario['id'] ?>">
      <input type="hidden" name="es_stopper" value="<?= (int) $incidente['es_stopper'] ?>">
      <button class="btn btn-secundario" type="submit" data-atajo="a" data-atajo-texto="Asignarme el incidente"><?= Icono::svg('users') ?> Asignarme</button>
    </form>
  <?php endif; ?>
</header>

<div class="pila">
  <?php $estado = $incidente['estado']; ?>
  <?= Vista::capturar('partials/pasos', ['etiqueta' => 'Avance del incidente', 'pasos' => [
      ['Abierto', $estado === 0 ? 'actual' : 'hecho'],
      ['En progreso', $estado === 1 ? 'actual' : ($estado === 2 ? 'hecho' : 'pendiente')],
      ['Cerrado', $estado === 2 ? 'hecho' : 'pendiente'],
  ]]) ?>
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash, 'errores' => $errores]) ?>
  <div class="vista-detalle">
    <aside class="tarjeta" aria-label="Datos del incidente">
      <dl class="detalle detalle-apilado">
        <dt>Estado</dt><dd><?= Catalogo::insignia('estado_incidente', $incidente['estado']) ?></dd>
        <dt>Severidad</dt><dd><?= Catalogo::insignia('severidad', $incidente['severidad']) ?></dd>
        <dt>Prioridad</dt><dd><?= Catalogo::insignia('prioridad', $incidente['prioridad']) ?></dd>
        <dt>Caso</dt><dd><a class="codigo" href="/casos/resultado?id=<?= (int) $incidente['caso_id'] ?>"><?= Html::e($incidente['caso']) ?></a> <?= Html::e($incidente['caso_objetivo']) ?></dd>
        <dt>Proyecto</dt><dd><?= Html::e($incidente['proyecto']) ?></dd>
        <dt>Módulo</dt><dd><?= Html::e($incidente['modulo']) ?></dd>
        <dt>Asignado a</dt><dd><?= Html::e($incidente['asignado'] ?? 'Sin asignar') ?></dd>
        <dt>Commit afectado</dt><dd><span class="codigo" title="<?= Html::e($incidente['commit_afectado']) ?>"><?= Html::e(substr($incidente['commit_afectado'], 0, 7)) ?></span></dd>
        <?php if ($incidente['commit_corregido'] !== null): ?>
          <dt>Commit corregido</dt><dd><span class="codigo" title="<?= Html::e($incidente['commit_corregido']) ?>"><?= Html::e(substr($incidente['commit_corregido'], 0, 7)) ?></span></dd>
        <?php endif; ?>
        <dt>Registrado por</dt><dd><?= Html::e($incidente['autor']) ?> · <?= Fecha::legible($creado) ?> <?= Fecha::hora($creado) ?></dd>
      </dl>
    </aside>
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
            <?= Vista::capturar('partials/campo', [
                'nombre' => 'commit_corregido',
                'etiqueta' => 'Commit corregido',
                'valor' => $datos['commit_corregido'] ?? '',
                'clase' => 'codigo',
                'ayuda' => 'Hash que lo corrige. Al cerrar.',
                'error' => $errores['commit_corregido'] ?? '',
                'atributos' => 'list="commits-proyecto" maxlength="40" pattern="[0-9a-fA-F]{7,40}" autocomplete="off" spellcheck="false"',
            ]) ?>
          </div>
          <datalist id="commits-proyecto"><?php foreach ($commits as $c): ?><option value="<?= Html::e($c) ?>"><?php endforeach; ?></datalist>
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
  </div>
</div>
