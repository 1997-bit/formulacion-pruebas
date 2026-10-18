<?php
declare(strict_types=1);

use App\Core\Paginacion;
use App\Core\Vista;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;
use App\Services\LogServicio;

/**
 * RNF-11. Lista, Seguridad y Rendimiento del día. Con uid: la petición entera
 *
 * @var string $vista  lista, seguridad o rendimiento
 * @var array{dia: string, hora: int|null, canal: string, nivel: int, respuesta: int, evento: string, uid: string} $filtros
 * @var list<string> $dias
 * @var list<array<string, mixed>> $lineas  solo en lista
 * @var Paginacion $paginacion  solo en lista
 * @var list<array{error: int, aviso: int, info: int}> $horas  solo en lista
 * @var array<int, string> $usuarios  solo en lista, id => nombre
 * @var array{eventos: array<string, int>, ips: array<string, int>} $seguridad  solo en seguridad
 * @var array<string, mixed> $rendimiento  solo en rendimiento, LogServicio::rendimiento()
 */

$vistas = ['lista' => ['Lista', 'list-checks'], 'seguridad' => ['Seguridad', 'lock'], 'rendimiento' => ['Rendimiento', 'chart-column']];
$enlace = fn (string $v): string => '/admin/logs?' . http_build_query(['vista' => $v, 'dia' => $filtros['dia']]);
// Lista del día con estos filtros
$lista = fn (array $mas): string => '/admin/logs?' . http_build_query(['dia' => $filtros['dia']] + $mas);
$insignia = fn (int $nivel): string => match (true) {
    $nivel >= 400 => 'peligro',
    $nivel >= 300 => 'aviso',
    $nivel >= 200 => 'info',
    default => 'secundaria',
};
$dias = in_array($filtros['dia'], $dias, true) ? $dias : [$filtros['dia'], ...$dias];
$fecha = $filtros['dia'] === date('Y-m-d') ? 'hoy' : 'el ' . (new DateTime($filtros['dia']))->format('d/m/Y');
$coma = fn (float|int $x): string => str_replace('.', ',', (string) $x);
// Bajo 1 s en ms: 0,00 s parecía cero
$seg = fn (float $ms): string => $ms < 1000 ? round($ms) . ' ms' : number_format($ms / 1000, 1, ',', '.') . ' s';
?>
<header class="encabezado">
  <div>
    <h1>Logs</h1>
    <p>Qué pasó en la app <?= $fecha ?>. Solo lectura. Cada visita a esta página queda registrada.</p>
  </div>
</header>
<nav class="pestanas" aria-label="Vistas de logs">
  <?php foreach ($vistas as $clave => [$texto, $icono]): ?>
    <a href="<?= Html::e($enlace($clave)) ?>"<?= $clave === $vista ? ' aria-current="page"' : '' ?>><?= Icono::svg($icono) ?> <?= $texto ?></a>
  <?php endforeach; ?>
</nav>
<div class="pila">
  <search aria-label="Filtrar logs">
    <form class="filtros" method="get" action="/admin/logs">
      <input type="hidden" name="vista" value="<?= Html::e($vista) ?>">
      <div class="campo">
        <label for="fl-dia">Día</label>
        <div class="select"><select class="control" id="fl-dia" name="dia">
          <?php foreach ($dias as $d): ?>
            <option value="<?= Html::e($d) ?>"<?= $d === $filtros['dia'] ? ' selected' : '' ?>><?= Html::e($d === date('Y-m-d') ? "Hoy, {$d}" : $d) ?></option>
          <?php endforeach; ?>
        </select></div>
      </div>
      <?php if ($vista === 'lista'): ?>
        <div class="campo">
          <label for="fl-hora">Hora</label>
          <div class="select"><select class="control" id="fl-hora" name="hora"><option value="">Todas</option>
            <?php for ($h = 0; $h < 24; $h++): ?>
              <option value="<?= $h ?>"<?= $h === $filtros['hora'] ? ' selected' : '' ?>><?= Fecha::hora(new DateTimeImmutable("{$h}:00")) ?></option>
            <?php endfor; ?>
          </select></div>
        </div>
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
          <label for="fl-respuesta">Respuesta</label>
          <div class="select"><select class="control" id="fl-respuesta" name="respuesta"><option value="">Todas</option>
            <?php foreach ([4 => 'Rechazadas, 4xx', 5 => 'Fallas de la app, 5xx'] as $n => $texto): ?>
              <option value="<?= $n ?>"<?= $n === $filtros['respuesta'] ? ' selected' : '' ?>><?= $texto ?></option>
            <?php endforeach; ?>
          </select></div>
        </div>
        <div class="campo">
          <label for="fl-evento">Mensaje</label>
          <input class="control" id="fl-evento" name="evento" enterkeyhint="search" value="<?= Html::e($filtros['evento']) ?>" placeholder="authz_fail">
        </div>
        <div class="campo">
          <label for="fl-uid">Código del error</label>
          <input class="control codigo" id="fl-uid" name="uid" enterkeyhint="search" value="<?= Html::e($filtros['uid']) ?>" placeholder="be45d3e3" maxlength="8">
        </div>
      <?php endif; ?>
      <div class="acciones">
        <button class="btn btn-secundario" type="submit">Filtrar</button>
        <a class="btn btn-fantasma" href="/admin/logs?vista=<?= Html::e($vista) ?>">Limpiar</a>
      </div>
    </form>
  </search>

  <?php if ($vista === 'lista'): ?>
    <?php if ($filtros['uid'] !== ''): ?>
      <p>Petición <span class="codigo"><?= Html::e($filtros['uid']) ?></span>, de todos los días. Cada línea, abierta.</p>
    <?php endif; ?>
    <?php if ($filtros['uid'] === '' && $lineas): ?>
      <?= Vista::capturar('logs/horas', [
          'titulo' => 'Líneas por hora',
          'horas' => $horas,
          'series' => ['error' => ['Error o crítico', 'barra-peligro'], 'aviso' => ['Aviso', 'barra-aviso'], 'info' => ['Info', 'barra-secundaria']],
          'unidad' => ['línea', 'líneas'],
          'enlace' => fn (int $h): string => '/admin/logs?' . http_build_query(['hora' => $h] + array_diff_key($_GET, ['pagina' => 0, 'vista' => 0])),
      ]) ?>
    <?php endif; ?>
    <?php if (!$lineas): ?>
      <div class="vacio">
        <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
        <h2>Ninguna línea coincide</h2>
        <p>Cambie el día o los filtros.</p>
      </div>
    <?php else: ?>
      <div class="tabla-contenedor encabezado-fijo">
        <table class="tabla">
          <caption class="solo-lector">Líneas del log</caption>
          <thead>
            <tr><th scope="col">Hora</th><th scope="col">Nivel</th><th scope="col">Canal</th><th scope="col">Mensaje</th><th scope="col">Usuario</th><th scope="col">IP</th><th scope="col">Código</th></tr>
          </thead>
          <tbody>
            <?php foreach ($lineas as $l): ?>
              <tr>
                <td><span class="codigo"><?= Fecha::hora(new DateTimeImmutable((string) $l['datetime']), true) ?></span></td>
                <td><span class="insignia insignia-<?= $insignia((int) $l['level']) ?>"><?= Html::e(LogServicio::NIVELES[$l['level']] ?? (string) $l['level_name']) ?></span></td>
                <td><?= Html::e((string) $l['channel']) ?></td>
                <td>
                  <details class="plegable"<?= $filtros['uid'] !== '' ? ' open' : '' ?>>
                    <summary><span class="codigo"><?= Html::e((string) $l['message']) ?></span><?php $texto = LogServicio::EVENTOS[strtok((string) $l['message'], ':')] ?? null; ?><?php if ($l['channel'] === 'seguridad' && $texto !== null): ?> <span class="log-texto"><?= Html::e($texto) ?></span><?php endif; ?></summary>
                    <pre class="log-detalle"><?= Html::e((string) json_encode(['context' => $l['context'], 'extra' => $l['extra']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
                  </details>
                </td>
                <td><?= Html::e($usuarios[$l['extra']['user_id'] ?? 0] ?? (string) ($l['extra']['user_id'] ?? '—')) ?></td>
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
    <?php
    $e = $seguridad['eventos'];
    $veces = fn (string $evento): int => $e[$evento] ?? 0;
    $maxEvento = max([1, ...array_values($e)]);
    ?>
    <div class="cifras log-cifras">
      <?php foreach ([
          ['Logins', 'authn_login_success'],
          ['Contraseña incorrecta', 'authn_login_fail'],
          ['Sin permiso', 'authz_fail'],
          ['Formulario falso', 'malicious_csrf'],
          ['Logins frenados', 'authn_login_lock'],
      ] as [$etiqueta, $evento]): ?>
        <a class="tarjeta cifra cifra-enlace" href="<?= Html::e($lista(['canal' => 'seguridad', 'evento' => $evento])) ?>">
          <p class="cifra-etiqueta"><?= $etiqueta ?></p>
          <p class="cifra-valor"><?= $veces($evento) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="tabla-contenedor">
      <table class="tabla">
        <caption class="solo-lector">Todo lo que pasó <?= $fecha ?>, de más a menos</caption>
        <thead><tr><th scope="col">Qué pasó</th><th scope="col">Código</th><th scope="col" class="num">Veces</th></tr></thead>
        <tbody>
          <?php foreach ($e as $evento => $n): ?>
            <tr>
              <td><?= Html::e(LogServicio::EVENTOS[$evento] ?? $evento) ?></td>
              <td><a class="codigo" href="<?= Html::e($lista(['canal' => 'seguridad', 'evento' => $evento])) ?>"><?= Html::e($evento) ?></a></td>
              <td class="num"><span class="log-barra"><span class="barra-apilada"><span class="barra-secundaria" style="width:<?= round($n / $maxEvento * 100, 1) ?>%"></span></span><?= $n ?></span></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$e): ?><tr><td colspan="3">Sin eventos.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="tabla-contenedor">
      <table class="tabla">
        <caption class="solo-lector">Equipos con más contraseñas incorrectas, top 10. IP: dirección del equipo en la red.</caption>
        <thead><tr><th scope="col">Equipo, IP</th><th scope="col" class="num">Intentos fallidos</th></tr></thead>
        <tbody>
          <?php foreach ($seguridad['ips'] as $ip => $n): ?>
            <tr><td><span class="codigo"><?= Html::e((string) $ip) ?></span></td><td class="num"><?= $n ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$seguridad['ips']): ?><tr><td colspan="2">Sin fallos.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

  <?php else: ?>
    <?php
    $r = $rendimiento;
    $maxP95 = max(1.0, $r['rutas'][0]['p95'] ?? 0.0);
    ?>
    <?php if ($r['peticiones'] === 0): ?>
      <div class="vacio">
        <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
        <h2>Nadie usó la app este día</h2>
        <p>Elija otro día.</p>
      </div>
    <?php else: ?>
      <div class="cifras log-cifras">
        <?php foreach ([
            ['Visitas', (string) $r['peticiones'], ['canal' => 'http']],
            ['Tiempo normal', $seg($r['p50']), null],
            ['1 de cada 20 tarda', $seg($r['p95']), null],
            ['Fallas de la app', $coma($r['errores']) . ' %', ['canal' => 'http', 'respuesta' => 5]],
            ['Rechazadas', $coma($r['rechazos']) . ' %', ['canal' => 'http', 'respuesta' => 4]],
        ] as [$etiqueta, $valor, $filtro]): ?>
          <?php if ($filtro === null): ?>
            <div class="tarjeta cifra"><p class="cifra-etiqueta"><?= $etiqueta ?></p><p class="cifra-valor"><?= $valor ?></p></div>
          <?php else: ?>
            <a class="tarjeta cifra cifra-enlace" href="<?= Html::e($lista($filtro)) ?>"><p class="cifra-etiqueta"><?= $etiqueta ?></p><p class="cifra-valor"><?= $valor ?></p></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <?= Vista::capturar('logs/horas', [
          'titulo' => 'Visitas por hora',
          'horas' => $r['horas'],
          'series' => ['errores' => ['Falla de la app', 'barra-peligro'], 'rechazos' => ['Rechazada', 'barra-aviso'], 'ok' => ['Bien', 'barra-secundaria']],
          'unidad' => ['visita', 'visitas'],
          'enlace' => fn (int $h): string => $lista(['canal' => 'http', 'hora' => $h]),
      ]) ?>

      <div class="tabla-contenedor">
        <table class="tabla">
          <caption class="solo-lector">Páginas, de la más lenta a la más rápida</caption>
          <thead><tr><th scope="col">Página</th><th scope="col" class="num">Visitas</th><th scope="col" class="num">Tiempo normal</th><th scope="col" class="num">1 de cada 20 tarda</th><th scope="col" class="num">Fallas</th></tr></thead>
          <tbody>
            <?php foreach ($r['rutas'] as $ruta): ?>
              <?php $t = LogServicio::tono($ruta['p95'], LogServicio::LENTO); ?>
              <tr>
                <td><a class="codigo" href="<?= Html::e($lista(['canal' => 'http', 'evento' => $ruta['ruta']])) ?>"><?= Html::e($ruta['ruta']) ?></a></td>
                <td class="num"><?= $ruta['peticiones'] ?></td>
                <td class="num"><?= $seg($ruta['p50']) ?></td>
                <td class="num"><span class="log-barra"><span class="barra-apilada"><span class="barra-<?= $t ?>" style="width:<?= round($ruta['p95'] / $maxP95 * 100, 1) ?>%"></span></span><span class="insignia insignia-<?= $t ?>"><?= $seg($ruta['p95']) ?></span></span></td>
                <td class="num"><?= $ruta['errores'] > 0 ? '<span class="insignia insignia-peligro">' . $coma($ruta['errores']) . ' %</span>' : '0 %' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
