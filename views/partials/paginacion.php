<?php
declare(strict_types=1);

use App\Core\Paginacion;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Conserva los filtros de la URL.
 *
 * @var Paginacion $paginacion
 */

$p = $paginacion;
$camino = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
$enlace = fn (int $n): string => Html::e($camino . '?' . http_build_query(['pagina' => $n] + $_GET));
$desde = $p->offset() + 1;
$hasta = min($p->offset() + Paginacion::POR_PAGINA, $p->total);
?>
<?php if ($p->paginas() > 1): ?>
  <nav class="paginacion" aria-label="Páginas">
    <span>Mostrando <?= $desde ?>–<?= $hasta ?> de <?= $p->total ?></span>
    <ul>
      <li>
        <?php if ($p->pagina > 1): ?>
          <a href="<?= $enlace($p->pagina - 1) ?>"><?= Icono::svg('chevron-left') ?><span class="solo-lector">Anterior</span></a>
        <?php else: ?>
          <span aria-disabled="true"><?= Icono::svg('chevron-left') ?><span class="solo-lector">Anterior</span></span>
        <?php endif; ?>
      </li>
      <?php for ($n = 1; $n <= $p->paginas(); $n++): ?>
        <li><a href="<?= $enlace($n) ?>"<?= $n === $p->pagina ? ' aria-current="page"' : '' ?>><?= $n ?></a></li>
      <?php endfor; ?>
      <li>
        <?php if ($p->pagina < $p->paginas()): ?>
          <a href="<?= $enlace($p->pagina + 1) ?>"><?= Icono::svg('chevron-right') ?><span class="solo-lector">Siguiente</span></a>
        <?php else: ?>
          <span aria-disabled="true"><?= Icono::svg('chevron-right') ?><span class="solo-lector">Siguiente</span></span>
        <?php endif; ?>
      </li>
    </ul>
  </nav>
<?php endif; ?>
