<?php
declare(strict_types=1);

use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Services\CasoServicio;

/**
 * Solo para ver. OK y FAULT piden evidencia, RF-24: se anotan en el caso
 *
 * @var list<array<string, mixed>> $casos  CasoModelo::sinPaginar()
 */

$columnas = [];
foreach (Catalogo::valores('estado_caso') as $estado => $_) {
    $columnas[$estado] = [];
}
foreach ($casos as $c) {
    $columnas[$c['estado']][] = $c;
}
$hoy = date('Y-m-d');
?>
<?php if (count($casos) >= CasoServicio::TOPE_VISTA): ?>
  <p class="campo-ayuda">Se muestran los primeros <?= CasoServicio::TOPE_VISTA ?> casos. Filtre por proyecto o requerimiento para ver el resto.</p>
<?php endif; ?>
<div class="tablero">
  <?php foreach ($columnas as $estado => $lista): ?>
    <section class="tablero-columna" aria-labelledby="col-<?= $estado ?>">
      <h2 class="tablero-titulo" id="col-<?= $estado ?>"><?= Catalogo::insignia('estado_caso', $estado) ?> <span><?= count($lista) ?></span></h2>
      <?php if (!$lista): ?>
        <p class="tablero-vacio">Sin casos.</p>
      <?php else: ?>
        <ul class="tablero-lista">
          <?php foreach ($lista as $c): ?>
            <?php $atrasado = $c['estado'] === 0 && $c['fecha_fin'] < $hoy; ?>
            <li><a class="tarjeta tarjeta-caso" href="/casos/resultado?id=<?= (int) $c['id'] ?>"><span class="tarjeta-caso-cabeza"><span class="codigo"><?= Html::e($c['codigo']) ?></span><?= $c['incidentes'] > 0 ? '<span class="insignia insignia-peligro">' . Html::cantidad((int) $c['incidentes'], 'incidente', 'incidentes') . '</span>' : '' ?></span><span class="tarjeta-caso-texto"><?= Html::e($c['objetivo']) ?></span><span class="tarjeta-caso-pie"><span><?= Html::e($c['proyecto']) ?> · <?= Html::e($c['requerimiento']) ?></span><span<?= $atrasado ? ' class="atrasado"' : '' ?>><?= Fecha::legible(new DateTime($c['fecha_fin'])) ?><?= $atrasado ? ' · atrasado' : '' ?></span></span></a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>
