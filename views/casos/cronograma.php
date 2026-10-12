<?php
declare(strict_types=1);

use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;
use App\Services\CasoServicio;

/**
 * Gantt de un mes: una fila por caso, de fecha_inicio a fecha_fin, agrupadas por requerimiento.
 * Grid CSS: la columna 1 es el código; las demás, un día cada una. Cada celda lleva su fila: la línea de hoy se cruza con las barras. Sin JS.
 *
 * @var list<array<string, mixed>> $casos  CasoModelo::sinPaginar() con el rango del mes
 * @var DateTimeImmutable $mes  primer día
 * @var array<string, string> $filtros
 */

$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$nombreMes = fn (DateTimeImmutable $m): string => $meses[(int) $m->format('n') - 1] . ' ' . $m->format('Y');
$dias = (int) $mes->format('t');
$inicio = $mes->format('Y-m-d');
$fin = $mes->format('Y-m-t');
$hoy = date('Y-m-d');
$diaHoy = str_starts_with($hoy, $mes->format('Y-m')) ? (int) date('j') : null;
$enlaceMes = fn (DateTimeImmutable $m): string => '/casos/listar?' . http_build_query(['vista' => 'cronograma', 'mes' => $m->format('Y-m')] + array_filter($filtros, fn (string $x): bool => $x !== ''));
// Día del mes en que empieza y termina la barra, recortada al mes.
$columna = fn (string $fecha): int => (int) (new DateTimeImmutable(max($inicio, min($fin, $fecha))))->format('j');

$grupos = [];
foreach ($casos as $c) {
    $grupos[$c['proyecto'] . ' · ' . $c['requerimiento']][] = $c;
}
$filas = count($casos) + count($grupos) + 1;
$fila = 1;
?>
<div class="cronograma-barra">
  <a class="btn btn-secundario btn-sm" href="<?= Html::e($enlaceMes($mes->modify('-1 month'))) ?>"><?= Icono::svg('chevron-left') ?> <?= Html::e(ucfirst($nombreMes($mes->modify('-1 month')))) ?></a>
  <h2 class="tarjeta-titulo"><?= Html::e(ucfirst($nombreMes($mes))) ?></h2>
  <a class="btn btn-secundario btn-sm" href="<?= Html::e($enlaceMes($mes->modify('+1 month'))) ?>"><?= Html::e(ucfirst($nombreMes($mes->modify('+1 month')))) ?> <?= Icono::svg('chevron-right') ?></a>
  <?php if ($diaHoy === null): ?><a class="btn btn-fantasma btn-sm" href="<?= Html::e($enlaceMes(new DateTimeImmutable('first day of this month'))) ?>">Hoy</a><?php endif; ?>
</div>
<?php if (!$casos): ?>
  <div class="vacio">
    <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
    <h2>Ningún caso en <?= Html::e($nombreMes($mes)) ?></h2>
    <p>Pase a otro mes o cambie los filtros.</p>
  </div>
<?php else: ?>
  <?php if (count($casos) >= CasoServicio::TOPE_VISTA): ?>
    <p class="campo-ayuda">Se muestran los primeros <?= CasoServicio::TOPE_VISTA ?> casos. Filtre por proyecto o requerimiento para ver el resto.</p>
  <?php endif; ?>
  <div class="cronograma-contenedor">
    <div class="cronograma" style="--dias:<?= $dias ?>;grid-template-rows:repeat(<?= $filas ?>, auto)">
      <span class="cronograma-esquina">Caso</span>
      <?php for ($d = 1; $d <= $dias; $d++): ?>
        <?php $semana = (int) $mes->modify('+' . ($d - 1) . ' days')->format('N'); ?>
        <span class="cronograma-dia<?= $semana >= 6 ? ' finde' : '' ?><?= $d === $diaHoy ? ' hoy' : '' ?>" style="grid-row:1;grid-column:<?= $d + 1 ?>" aria-hidden="true"><?= $d ?></span>
      <?php endfor; ?>
      <?php if ($diaHoy !== null): ?><span class="cronograma-hoy" style="grid-column:<?= $diaHoy + 1 ?>;grid-row:1 / -1" aria-hidden="true"></span><?php endif; ?>
      <?php foreach ($grupos as $grupo => $lista): ?>
        <h3 class="cronograma-grupo" style="grid-row:<?= ++$fila ?>"><?= Html::e($grupo) ?></h3>
        <?php foreach ($lista as $c): ?>
          <?php
          $desde = $columna($c['fecha_inicio']);
          $hasta = $columna($c['fecha_fin']);
          $estado = Catalogo::texto('estado_caso', $c['estado']);
          $variante = Catalogo::valores('estado_caso')[$c['estado']]['variante'];
          $fila++;
          $fechas = Fecha::legible(new DateTime($c['fecha_inicio'])) . ' al ' . Fecha::legible(new DateTime($c['fecha_fin']));
          ?>
          <a class="cronograma-caso codigo" style="grid-row:<?= $fila ?>" href="/casos/resultado?id=<?= (int) $c['id'] ?>" title="<?= Html::e($c['objetivo']) ?>"><?= Html::e($c['codigo']) ?><span class="solo-lector">: <?= Html::e($estado) ?>, <?= $fechas ?></span></a>
          <span class="cronograma-tramo barra-<?= $variante ?><?= $c['fecha_inicio'] < $inicio ? ' sigue-antes' : '' ?><?= $c['fecha_fin'] > $fin ? ' sigue-despues' : '' ?>"
                style="grid-row:<?= $fila ?>;grid-column:<?= $desde + 1 ?> / <?= $hasta + 2 ?>" title="<?= Html::e($c['codigo'] . ' · ' . $estado . ' · ' . $fechas) ?>" aria-hidden="true"><?= Html::e($estado) ?></span>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
