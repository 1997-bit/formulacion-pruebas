<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Prueba y resultado a la izquierda, datos a la derecha
 * Al imprimir: formulario 1, RF-08. Una columna, sin el form del resultado
 *
 * @var array<string, mixed> $caso  con evidencias
 * @var bool $editable
 * @var ?string $flash
 * @var array<string, string> $errores
 * @var array<string, mixed> $datos
 * @var array{rol: int} $usuario
 * @var array{0: ?array<string, mixed>, 1: ?array<string, mixed>} $vecinos  caso anterior y siguiente
 * @var list<array<string, mixed>> $historial
 * @var list<string> $commits  ya usados en el proyecto
 */

$hora = fn (string $fecha): string => Fecha::legible(new DateTime($fecha)) . ' ' . (new DateTime($fecha))->format('H:i');
$iconos = [1 => 'image', 2 => 'terminal', 4 => 'link'];
$texto = fn (?string $valor): string => $valor === null || $valor === '' ? '—' : Html::e($valor);
// Pasos: Registrado, resultado y cierre. FAULT: incidente antes del cierre
$conIncidente = $caso['incidentes'] !== [];
$abiertos = count(array_filter($caso['incidentes'], fn (array $i): bool => $i['estado'] !== 2));
// Paso actual: lo que falta. Terminado: el nombre del resultado
$pasos = match ($caso['estado']) {
    0 => [['Registrado', 'hecho'], ['Por ejecutar', 'actual'], ['Cerrado', 'pendiente']],
    1 => [['Registrado', 'hecho'], ['OK', 'hecho'], ['Cerrado', 'hecho']],
    default => [['Registrado', 'hecho'], ['FAULT', 'mal'], $conIncidente ? ['Incidente', 'hecho'] : ['Registrar incidente', 'actual'],
        !$conIncidente ? ['Cerrado', 'pendiente'] : ($abiertos === 0 ? ['Cerrado', 'hecho'] : ['Cerrar incidente', 'actual'])],
};
// Pendiente vencido: días de atraso
$atraso = $caso['estado'] === 0 ? (int) (new DateTime($caso['fecha_fin']))->diff(new DateTime('today'))->format('%r%a') : 0;
// Incidente abierto: el botón principal lleva a él
$abierto = array_values(array_filter($caso['incidentes'], fn (array $i): bool => $i['estado'] !== 2))[0] ?? null;
?>
<header class="encabezado" data-reciente="<?= Html::e($caso['codigo']) ?>" data-reciente-texto="<?= Html::e($caso['objetivo']) ?>">
  <div>
    <p class="antetitulo fila"><span class="codigo"><?= Html::e($caso['codigo']) ?></span> <?= Catalogo::insignia('estado_caso', $caso['estado']) ?><?php if ($atraso > 0): ?> <span class="insignia insignia-peligro">Atrasado <?= $atraso ?> d</span><?php endif; ?></p>
    <h1><?= Html::e($caso['modulo']) ?></h1>
  </div>
  <div class="acciones">
    <?php foreach ([[$vecinos[0], 'k', 'Caso anterior', 'chevron-left'], [$vecinos[1], 'j', 'Caso siguiente', 'chevron-right']] as [$v, $tecla, $etiqueta, $icono]): ?>
      <?php if ($v !== null): ?>
        <a class="btn btn-fantasma btn-icono" href="/casos/resultado?id=<?= (int) $v['id'] ?>" aria-label="<?= $etiqueta ?>: <?= Html::e($v['codigo']) ?>"
           data-tooltip="<?= $etiqueta ?>: <?= Html::e($v['codigo']) ?>" data-atajo="<?= $tecla ?>" data-atajo-texto="<?= $etiqueta ?>"><?= Icono::svg($icono) ?></a>
      <?php else: ?>
        <span class="btn btn-fantasma btn-icono" aria-disabled="true" role="link" aria-label="<?= $etiqueta ?>: no hay" data-tooltip="<?= $etiqueta ?>: no hay"><?= Icono::svg($icono) ?></span>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($abierto !== null): ?>
      <a class="btn btn-primario" href="/formularios/incidentes/ver?id=<?= (int) $abierto['id'] ?>"><?= Icono::svg('bug') ?> Ver <?= Html::e($abierto['codigo']) ?></a>
    <?php endif; ?>
    <a class="btn <?= $caso['estado'] === 2 && $abierto === null ? 'btn-primario' : 'btn-secundario' ?>" href="/formularios/incidentes/registrar?caso=<?= (int) $caso['id'] ?>" data-atajo="i"><?= Icono::svg('bug') ?> Registrar incidente</a>
    <?php if ($editable): ?>
      <a class="btn btn-secundario" href="/casos/editar?id=<?= (int) $caso['id'] ?>" data-atajo="e"><?= Icono::svg('pencil') ?> Editar</a>
    <?php endif; ?>
    <?= Vista::capturar('partials/imprimir') ?>
    <?php if ($usuario['rol'] === 1): ?>
      <?= Vista::capturar('partials/eliminar', [
          'dialogo' => 'dlg-eliminar', 'etiqueta' => 'Eliminar caso', 'accion' => '/casos/eliminar', 'id' => (int) $caso['id'],
          'titulo' => '¿Eliminar el caso ' . $caso['codigo'] . '?', 'texto' => 'Esta acción no se puede deshacer.', 'confirmar' => (string) $caso['codigo'],
          'bloqueo' => $caso['evidencias'] !== [] || $conIncidente ? 'Tiene evidencias o incidentes. No se puede eliminar.' : '',
      ]) ?>
    <?php endif; ?>
  </div>
</header>

<div class="pila">
  <?= Vista::capturar('partials/pasos', ['pasos' => $pasos, 'etiqueta' => 'Avance del caso']) ?>
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash, 'errores' => $errores]) ?>
  <div class="vista-detalle">
    <aside class="tarjeta" aria-label="Datos del caso">
      <dl class="detalle detalle-apilado">
        <dt>Estado</dt><dd><?= Catalogo::insignia('estado_caso', $caso['estado']) ?></dd>
        <dt>Proyecto</dt><dd><?= Html::e($caso['proyecto']) ?></dd>
        <dt>Requerimiento</dt><dd><?= Html::e($caso['requerimiento'] . ' · ' . $caso['requerimiento_descripcion']) ?></dd>
        <dt>Tipo de prueba</dt><dd><?= Html::e(Catalogo::texto('tipo_prueba', $caso['tipo_prueba'])) ?></dd>
        <dt>Plataforma</dt><dd><?= Html::e(Catalogo::texto('plataforma', $caso['plataforma']) . ($caso['entorno'] !== null ? ' · ' . $caso['entorno'] : '')) ?></dd>
        <dt>Técnica</dt><dd><?= $caso['subtecnica'] > 10 ? 'Caja blanca' : 'Caja negra' ?> · <?= Html::e(Catalogo::texto('subtecnica', $caso['subtecnica'])) ?></dd>
        <dt>Fechas</dt><dd><?= Fecha::legible(new DateTime($caso['fecha_inicio'])) ?> al <span<?= $atraso > 0 ? ' class="atrasado"' : '' ?>><?= Fecha::legible(new DateTime($caso['fecha_fin'])) ?></span></dd>
        <?php if ($caso['anotador'] !== null): ?>
          <dt>Resultado anotado por</dt><dd><?= Html::e($caso['anotador']) ?> · <?= $hora($caso['anotado_en']) ?></dd>
        <?php endif; ?>
        <?php if ($caso['commit_probado'] !== null): ?>
          <dt>Commit probado</dt><dd><span class="codigo" title="<?= Html::e($caso['commit_probado']) ?>"><?= Html::e(substr($caso['commit_probado'], 0, 7)) ?></span></dd>
        <?php endif; ?>
        <dt>Creado por</dt><dd><?= Html::e($caso['autor']) ?> · <?= $hora($caso['creado_en']) ?></dd>
        <dt>Solicitado por</dt><dd><?= $caso['solicitante'] === null ? '—' : Html::e($caso['solicitante']) ?></dd>
        <dt>Aprobado por</dt><dd><?= $caso['aprobador'] === null ? '—' : Html::e($caso['aprobador']) ?></dd>
        <?php if ($caso['incidentes']): ?>
          <dt>Incidentes</dt>
          <?php foreach ($caso['incidentes'] as $i): ?>
            <dd><a class="codigo" href="/formularios/incidentes/ver?id=<?= (int) $i['id'] ?>"><?= Html::e($i['codigo']) ?></a> <?= Catalogo::insignia('estado_incidente', $i['estado']) ?><?= $i['es_stopper'] ? ' <span class="insignia insignia-peligro">Stopper</span>' : '' ?> <?= Html::e($i['titulo']) ?></dd>
          <?php endforeach; ?>
        <?php endif; ?>
      </dl>
    </aside>
    <div class="pila">
      <section class="tarjeta" aria-labelledby="dt-prueba">
        <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="dt-prueba">Prueba</h2></header>
        <dl class="detalle">
          <dt>Objetivo</dt><dd><?= Html::e($caso['objetivo']) ?></dd>
          <dt>Precondiciones</dt><dd class="multilinea"><?= $texto($caso['precondiciones']) ?></dd>
          <dt>Datos de entrada</dt><dd class="multilinea"><?= Html::e($caso['entrada']) ?></dd>
          <dt>Pasos</dt><dd class="multilinea"><?= Html::e($caso['pasos']) ?></dd>
          <dt>Resultado esperado</dt><dd class="multilinea"><?= Html::e($caso['resultado_esperado']) ?></dd>
        </dl>
      </section>

      <section class="tarjeta pila" aria-labelledby="dt-resultado">
        <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="dt-resultado">Resultado</h2></header>
        <div class="solo-impresion">
          <dl class="detalle">
            <dt>Resultado obtenido</dt><dd class="multilinea"><?= $texto($caso['resultado_obtenido']) ?></dd>
            <dt>Observaciones</dt><dd class="multilinea"><?= $texto($caso['observaciones']) ?></dd>
            <dt>Impacto</dt><dd class="multilinea"><?= $texto($caso['impacto']) ?></dd>
            <dt>Commit probado</dt><dd class="codigo"><?= $texto($caso['commit_probado']) ?></dd>
          </dl>
        </div>
        <?php if ($caso['evidencias']): ?>
          <div>
            <h3 class="titulo-seccion" style="margin:0 0 8px;font-size:var(--letra-sm)">Evidencia</h3>
            <ul class="archivos">
              <?php foreach ($caso['evidencias'] as $e): ?>
                <li class="archivo">
                  <span class="archivo-icono"><?= Icono::svg($iconos[$e['tipo']] ?? 'file-text') ?></span>
                  <p class="archivo-nombre"><?= Html::e($e['descripcion']) ?></p>
                  <p class="archivo-detalle">
                    <span class="codigo"><?= Html::e($e['nombre_original'] ?? $e['enlace']) ?></span>
                    · <?= Html::e($e['autor']) ?> · <time datetime="<?= (new DateTime($e['subido_en']))->format('Y-m-d\TH:i') ?>" title="<?= $hora($e['subido_en']) ?>"><?= Fecha::relativa(new DateTime($e['subido_en']), new DateTimeImmutable()) ?></time>
                  </p>
                  <div class="acciones">
                    <a class="btn btn-fantasma btn-icono" href="/evidencias/ver?id=<?= (int) $e['id'] ?>" target="_blank" rel="noopener"
                       aria-label="Abrir <?= Html::e($e['descripcion']) ?>" data-tooltip="Abrir" data-tooltip-alinear="fin"><?= Icono::svg($e['tipo'] === 4 ? 'link' : 'download') ?></a>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <form class="pila no-imprimir" method="post" action="/casos/resultado" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
          <input type="hidden" name="id" value="<?= (int) $caso['id'] ?>">
          <?= Vista::capturar('partials/resultado', ['datos' => $datos, 'errores' => $errores, 'previas' => count($caso['evidencias']), 'commits' => $commits]) ?>
          <div class="acciones">
            <button class="btn btn-primario" type="submit">Guardar resultado</button>
          </div>
        </form>
      </section>

      <section class="tarjeta no-imprimir" aria-labelledby="dt-historial">
        <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="dt-historial">Historial</h2></header>
        <?= Vista::capturar('partials/historial', ['historial' => $historial, 'codigo' => $caso['codigo']]) ?>
      </section>
    </div>
  </div>
</div>
