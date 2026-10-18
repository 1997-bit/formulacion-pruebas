<?php
declare(strict_types=1);

use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Requerimientos × tipos de prueba. Una tabla por proyecto
 * Celda: casos, relleno por cantidad. Sin casos: borde punteado. FAULT: raya roja
 *
 * @var list<array<string, mixed>> $matriz  RequerimientoServicio::matriz()
 */

$tipos = Catalogo::valores('tipo_prueba');
$nivel = fn (int $n): int => min(3, $n);
?>
<?php if (!$matriz): ?>
  <div class="vacio">
    <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
    <h2>Todavía no hay requerimientos</h2>
    <p>Registre requerimientos y casos para ver la cobertura.</p>
  </div>
<?php endif; ?>
<?php foreach ($matriz as $p): ?>
  <section class="tarjeta" aria-labelledby="mz-<?= $p['id'] ?>">
    <header class="tarjeta-encabezado">
      <h2 class="tarjeta-titulo" id="mz-<?= $p['id'] ?>"><?= Html::e($p['nombre']) ?></h2>
      <p class="tarjeta-descripcion">Casos por requerimiento y tipo de prueba. Columnas: tipos con casos en el proyecto.</p>
      <div class="acciones"><span class="insignia insignia-borde numero"><?= $p['cubiertas'] ?> / <?= $p['total'] ?></span></div>
    </header>
    <?php if ($p['tipos'] === []): ?>
      <p class="campo-ayuda">Ningún caso todavía.</p>
    <?php else: ?>
      <div class="matriz-contenedor">
        <table class="matriz">
          <caption class="solo-lector">Cobertura de <?= Html::e($p['nombre']) ?>: <?= $p['cubiertas'] ?> de <?= $p['total'] ?> celdas con casos</caption>
          <thead>
            <tr>
              <td></td>
              <?php foreach ($p['tipos'] as $t): ?><th scope="col"><abbr title="<?= Html::e($tipos[$t]['texto']) ?>"><?= $tipos[$t]['sigla'] ?></abbr></th><?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($p['filas'] as $f): ?>
              <tr>
                <th scope="row"><span class="codigo"><?= Html::e($f['codigo']) ?></span> <span class="matriz-descripcion"><?= Html::e($f['descripcion']) ?></span></th>
                <?php foreach ($p['tipos'] as $t): ?>
                  <?php $c = $f['celdas'][$t] ?? null; ?>
                  <td>
                    <?php if ($c === null): ?>
                      <span class="matriz-celda matriz-vacia" title="<?= Html::e($f['codigo'] . ' · ' . $tipos[$t]['texto']) ?>: sin casos"><span class="solo-lector">Sin casos</span></span>
                    <?php else: ?>
                      <a class="matriz-celda matriz-nivel-<?= $nivel($c['casos']) ?><?= $c['fault'] > 0 ? ' matriz-fault' : '' ?>"
                         href="/casos/listar?<?= http_build_query(['proyecto' => $p['id'], 'requerimiento' => $f['id']]) ?>"
                         title="<?= Html::e($f['codigo'] . ' · ' . $tipos[$t]['texto'] . ': ' . Html::cantidad($c['casos'], 'caso', 'casos') . ($c['fault'] > 0 ? ', ' . $c['fault'] . ' FAULT' : '')) ?>"><?= $c['casos'] ?><?= $c['fault'] > 0 ? '<span class="solo-lector">, ' . $c['fault'] . ' FAULT</span>' : '' ?></a>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <ul class="leyenda" aria-hidden="true">
        <li><span class="leyenda-muestra matriz-vacia"></span>Sin casos</li>
        <li><span class="leyenda-muestra matriz-nivel-1"></span>1</li>
        <li><span class="leyenda-muestra matriz-nivel-2"></span>2</li>
        <li><span class="leyenda-muestra matriz-nivel-3"></span>3 o más</li>
        <li><span class="leyenda-muestra matriz-fault-muestra"></span>Con FAULT</li>
      </ul>
    <?php endif; ?>
    <?php if ($p['sin_casos']): ?>
      <footer class="tarjeta-pie matriz-pie">
        <p><?= Icono::svg('triangle-alert') ?> <?= Html::cantidad(count($p['sin_casos']), 'requerimiento', 'requerimientos') ?> sin casos: <span class="codigo"><?= Html::e(implode(', ', $p['sin_casos'])) ?></span></p>
        <a class="btn btn-primario btn-sm" href="/casos/registrar"><?= Icono::svg('plus') ?> Registrar caso</a>
      </footer>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
