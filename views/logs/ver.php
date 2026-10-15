<?php
declare(strict_types=1);

use App\Core\Paginacion;
use App\Core\Vista;
use App\Helpers\Html;
use App\Helpers\Icono;
use App\Services\LogServicio;

/**
 * RNF-11. Lista, Seguridad y Rendimiento del día. Con uid: la petición entera, lista para captura.
 *
 * @var string $vista  lista, seguridad o rendimiento
 * @var array{dia: string, canal: string, nivel: int, evento: string, uid: string} $filtros
 * @var list<string> $dias
 * @var list<array<string, mixed>> $lineas  solo en lista
 * @var Paginacion $paginacion  solo en lista
 * @var array{eventos: array<string, int>, ips: array<string, int>} $seguridad  solo en seguridad
 * @var list<array{ruta: string, peticiones: int, p50: float, p95: float, errores: float}> $rendimiento  solo en rendimiento
 */

$vistas = ['lista' => ['Lista', 'list-checks'], 'seguridad' => ['Seguridad', 'lock'], 'rendimiento' => ['Rendimiento', 'chart-column']];
$enlace = fn (string $v): string => '/admin/logs?' . http_build_query(['vista' => $v, 'dia' => $filtros['dia']]);
$insignia = fn (int $nivel): string => match (true) {
    $nivel >= 400 => 'peligro',
    $nivel >= 300 => 'aviso',
    $nivel >= 200 => 'info',
    default => 'secundaria',
};
$dias = in_array($filtros['dia'], $dias, true) ? $dias : [$filtros['dia'], ...$dias];
?>
<header class="encabezado">
  <div>
    <h1>Logs</h1>
    <p>Solo lectura. Cada visita queda registrada como <span class="codigo">sensitive_read</span>.</p>
  </div>
</header>
<nav class="pestanas" aria-label="Vistas de logs">
  <?php foreach ($vistas as $clave => [$texto, $icono]): ?>
    <a href="<?= Html::e($enlace($clave)) ?>"<?= $clave === $vista ? ' aria-current="page"' : '' ?>><?= Icono::svg($icono) ?> <?= $texto ?></a>
  <?php endforeach; ?>
</nav>
<div class="pila">
  <form class="filtros" method="get" action="/admin/logs" role="search" aria-label="Filtrar logs">
    <input type="hidden" name="vista" value="<?= Html::e($vista) ?>">
    <div class="campo">
      <label for="fl-dia">Día</label>
      <div class="select"><select class="control" id="fl-dia" name="dia">
        <?php foreach ($dias as $d): ?>
          <option<?= $d === $filtros['dia'] ? ' selected' : '' ?>><?= Html::e($d) ?></option>
        <?php endforeach; ?>
      </select></div>
    </div>
    <?php if ($vista === 'lista'): ?>
      <div class="campo">
        <label for="fl-canal">Canal</label>
        <div class="select"><select class="control" id="fl-canal" name="canal"><option value="">Todos</option>
          <?php foreach (LogServicio::CANALES as $c): ?>
            <option<?= $c === $filtros['canal'] ? ' selected' : '' ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select></div>
      </div>
      <div class="campo">
        <label for="fl-nivel">Nivel mínimo</label>
        <div class="select"><select class="control" id="fl-nivel" name="nivel"><option value="">Todos</option>
          <?php foreach (LogServicio::NIVELES as $n => $texto): ?>
            <option value="<?= $n ?>"<?= $n === $filtros['nivel'] ? ' selected' : '' ?>><?= $texto ?></option>
          <?php endforeach; ?>
        </select></div>
      </div>
      <div class="campo">
        <label for="fl-evento">Mensaje</label>
        <input class="control" id="fl-evento" name="evento" value="<?= Html::e($filtros['evento']) ?>" placeholder="authz_fail">
      </div>
      <div class="campo">
        <label for="fl-uid">Código del error</label>
        <input class="control codigo" id="fl-uid" name="uid" value="<?= Html::e($filtros['uid']) ?>" placeholder="be45d3e3" maxlength="8">
      </div>
    <?php endif; ?>
    <div class="acciones">
      <button class="btn btn-secundario" type="submit">Filtrar</button>
      <a class="btn btn-fantasma" href="/admin/logs?vista=<?= Html::e($vista) ?>">Limpiar</a>
    </div>
  </form>

  <?php if ($vista === 'lista'): ?>
    <?php if ($filtros['uid'] !== ''): ?>
      <p>Petición <span class="codigo"><?= Html::e($filtros['uid']) ?></span>, de todos los días. Cada línea, abierta.</p>
    <?php endif; ?>
    <?php if (!$lineas): ?>
      <div class="vacio">
        <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
        <h2>Ninguna línea coincide</h2>
        <p>Cambie el día o los filtros.</p>
      </div>
    <?php else: ?>
      <div class="tabla-contenedor">
        <table class="tabla">
          <caption class="solo-lector">Líneas del log</caption>
          <thead>
            <tr><th scope="col">Hora</th><th scope="col">Nivel</th><th scope="col">Canal</th><th scope="col">Mensaje</th><th scope="col">Usuario</th><th scope="col">IP</th><th scope="col">Código</th></tr>
          </thead>
          <tbody>
            <?php foreach ($lineas as $l): ?>
              <tr>
                <td><span class="codigo"><?= Html::e(substr((string) $l['datetime'], 11, 8)) ?></span></td>
                <td><span class="insignia insignia-<?= $insignia((int) $l['level']) ?>"><?= Html::e((string) $l['level_name']) ?></span></td>
                <td><?= Html::e((string) $l['channel']) ?></td>
                <td>
                  <details class="plegable"<?= $filtros['uid'] !== '' ? ' open' : '' ?>>
                    <summary><span class="codigo"><?= Html::e((string) $l['message']) ?></span></summary>
                    <pre class="log-detalle"><?= Html::e((string) json_encode(['context' => $l['context'], 'extra' => $l['extra']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
                  </details>
                </td>
                <td><?= Html::e((string) ($l['extra']['user_id'] ?? '—')) ?></td>
                <td><span class="codigo"><?= Html::e((string) ($l['extra']['ip'] ?? '—')) ?></span></td>
                <td><a class="codigo" href="/admin/logs?uid=<?= Html::e((string) ($l['extra']['uid'] ?? '')) ?>"><?= Html::e((string) ($l['extra']['uid'] ?? '')) ?></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?= Vista::capturar('partials/paginacion', ['paginacion' => $paginacion]) ?>
    <?php endif; ?>

  <?php elseif ($vista === 'seguridad'): ?>
    <div class="tabla-contenedor">
      <table class="tabla">
        <caption>Eventos de seguridad del día</caption>
        <thead><tr><th scope="col">Evento</th><th scope="col" class="num">Veces</th></tr></thead>
        <tbody>
          <?php foreach ($seguridad['eventos'] as $evento => $n): ?>
            <tr><td><a class="codigo" href="/admin/logs?<?= Html::e(http_build_query(['dia' => $filtros['dia'], 'canal' => 'seguridad', 'evento' => $evento])) ?>"><?= Html::e($evento) ?></a></td><td class="num"><?= $n ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$seguridad['eventos']): ?><tr><td colspan="2">Sin eventos.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="tabla-contenedor">
      <table class="tabla">
        <caption>Login fallido por IP, top 10</caption>
        <thead><tr><th scope="col">IP</th><th scope="col" class="num">Fallos</th></tr></thead>
        <tbody>
          <?php foreach ($seguridad['ips'] as $ip => $n): ?>
            <tr><td><span class="codigo"><?= Html::e((string) $ip) ?></span></td><td class="num"><?= $n ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$seguridad['ips']): ?><tr><td colspan="2">Sin fallos.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

  <?php else: ?>
    <div class="tabla-contenedor">
      <table class="tabla">
        <caption>Golden signals por ruta. Tiempos en ms.</caption>
        <thead><tr><th scope="col">Ruta</th><th scope="col" class="num">Peticiones</th><th scope="col" class="num">p50</th><th scope="col" class="num">p95</th><th scope="col" class="num">5xx %</th></tr></thead>
        <tbody>
          <?php foreach ($rendimiento as $r): ?>
            <tr>
              <td><span class="codigo"><?= Html::e($r['ruta']) ?></span></td>
              <td class="num"><?= $r['peticiones'] ?></td>
              <td class="num"><?= $r['p50'] ?></td>
              <td class="num"><?= $r['p95'] ?></td>
              <td class="num"><?= $r['errores'] > 0 ? '<span class="insignia insignia-peligro">' . $r['errores'] . '</span>' : '0' ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rendimiento): ?><tr><td colspan="5">Sin peticiones.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
