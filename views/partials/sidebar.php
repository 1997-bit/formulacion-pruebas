<?php
declare(strict_types=1);

use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var array{nombre: string, usuario: string, rol: int} $usuario
 * @var string $rutaActual
 */

$menu = require RAIZ . '/config/menu.php';
$rol = $usuario['rol'];
$permitido = fn (array $x): bool => !isset($x['rol']) || $x['rol'] === $rol;
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-cabecera">
    <a class="sidebar-boton sidebar-marca" href="/dashboard.php" data-etiqueta="Casos de Prueba">
      <span class="marca-icono"><?= Icono::svg('flask-conical') ?></span>
      <span class="sidebar-texto sidebar-dos-lineas">
        <strong>Casos de Prueba</strong>
        <small>Sistema de pruebas</small>
      </span>
    </a>
  </div>

  <nav class="sidebar-contenido" aria-label="Navegación principal">
    <?php foreach ($menu as $grupo): ?>
      <?php if (!$permitido($grupo)) {
          continue;
      } ?>
      <div class="sidebar-grupo">
        <h2 class="sidebar-grupo-titulo"><?= Html::e($grupo['grupo']) ?></h2>
        <ul class="sidebar-menu">
          <?php foreach ($grupo['elementos'] as $el): ?>
            <?php if (!$permitido($el)) {
                continue;
            } ?>
            <?php if (isset($el['hijos'])): ?>
              <?php
              $hijos = array_filter($el['hijos'], $permitido);
              if ($hijos === []) {
                  continue;
              }
              $abierto = in_array($rutaActual, array_column($hijos, 'ruta'), true);
              ?>
              <li>
                <details class="sidebar-desplegable"<?= $abierto ? ' open' : '' ?>>
                  <summary class="sidebar-boton" data-etiqueta="<?= Html::e($el['texto']) ?>">
                    <?= Icono::svg($el['icono']) ?>
                    <span class="sidebar-texto"><?= Html::e($el['texto']) ?></span>
                    <?= Icono::svg('chevron-right', 'icono flecha') ?>
                  </summary>
                  <ul class="sidebar-submenu">
                    <?php foreach ($hijos as $hijo): ?>
                      <li>
                        <a class="sidebar-subboton" href="<?= Html::e($hijo['ruta']) ?>"<?= $hijo['ruta'] === $rutaActual ? ' aria-current="page"' : '' ?>>
                          <?= Html::e($hijo['texto']) ?>
                        </a>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </details>
              </li>
            <?php else: ?>
              <li>
                <a class="sidebar-boton" href="<?= Html::e($el['ruta']) ?>" data-etiqueta="<?= Html::e($el['texto']) ?>"<?= $el['ruta'] === $rutaActual ? ' aria-current="page"' : '' ?>>
                  <?= Icono::svg($el['icono']) ?>
                  <span class="sidebar-texto"><?= Html::e($el['texto']) ?></span>
                </a>
              </li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-pie">
    <details class="usuario-menu">
      <summary class="sidebar-boton sidebar-usuario" data-etiqueta="<?= Html::e($usuario['nombre']) ?>">
        <span class="avatar-cuadrado" aria-hidden="true"><?= Html::e(Html::iniciales($usuario['nombre'])) ?></span>
        <span class="sidebar-texto sidebar-dos-lineas">
          <strong><?= Html::e($usuario['nombre']) ?></strong>
          <small><?= Html::e($usuario['usuario']) ?> · <?= Html::e(Catalogo::texto('rol', $usuario['rol'])) ?></small>
        </span>
        <?= Icono::svg('chevrons-up-down', 'icono flecha-usuario') ?>
      </summary>
      <div class="usuario-flotante">
        <a class="sidebar-boton" href="/logout.php">
          <?= Icono::svg('log-out') ?>
          <span>Cerrar sesión</span>
        </a>
      </div>
    </details>
  </div>
</aside>
