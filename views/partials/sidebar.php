<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var array{nombre: string, usuario: string, rol: int} $usuario
 * @var string $rutaActual
 * @var list<array{id: int, nombre: string}> $proyectos
 * @var array{id: int, nombre: string}|null $activo
 */

$menu = require RAIZ . '/config/menu.php';
$rol = $usuario['rol'];
$permitido = fn (array $x): bool => !isset($x['rol']) || $x['rol'] === $rol;
$atajo = fn (array $x, string $texto): string => isset($x['atajo'])
    ? ' data-atajo="' . Html::e($x['atajo']) . '" data-atajo-texto="Ir a ' . Html::e(mb_strtolower($texto)) . '"'
    : '';
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-cabecera">
    <?php // Selector de proyecto (Jira, Linear): todo lo de Plataforma y Formularios muestra solo el activo. ?>
    <details class="proyecto-menu">
      <summary class="sidebar-boton sidebar-marca" data-etiqueta="<?= Html::e($activo['nombre'] ?? 'Todos los proyectos') ?>"
               data-atajo="p" data-atajo-texto="Cambiar de proyecto">
        <span class="marca-icono" aria-hidden="true"><?= $activo ? Html::e(Html::iniciales($activo['nombre'])) : Icono::svg('flask-conical') ?></span>
        <span class="sidebar-texto sidebar-dos-lineas">
          <strong><?= Html::e($activo['nombre'] ?? 'Todos los proyectos') ?></strong>
          <small><?= $activo ? 'Proyecto' : count($proyectos) . ' proyecto(s)' ?></small>
        </span>
        <?= Icono::svg('chevrons-up-down', 'icono flecha-usuario') ?>
      </summary>
      <form class="proyecto-flotante" method="post" action="/proyecto">
        <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
        <input type="hidden" name="volver" value="<?= Html::e($_SERVER['REQUEST_URI'] ?? '/') ?>">
        <p class="proyecto-flotante-titulo">Proyectos</p>
        <?php foreach ([['id' => 0, 'nombre' => 'Todos los proyectos'], ...$proyectos] as $p): ?>
          <?php $esActivo = $p['id'] === ($activo['id'] ?? 0); ?>
          <button class="sidebar-boton" type="submit" name="proyecto" value="<?= (int) $p['id'] ?>" data-proyecto="<?= (int) $p['id'] ?>"<?= $esActivo ? ' aria-current="true"' : '' ?>>
            <span class="sidebar-texto"><?= Html::e($p['nombre']) ?></span>
            <?php if ($esActivo): ?><?= Icono::svg('check') ?><?php endif; ?>
          </button>
        <?php endforeach; ?>
      </form>
    </details>
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
                        <a class="sidebar-subboton" href="<?= Html::e($hijo['ruta']) ?>"<?= $hijo['ruta'] === $rutaActual ? ' aria-current="page"' : '' ?><?= $atajo($hijo, $el['texto']) ?>>
                          <?= Html::e($hijo['texto']) ?>
                        </a>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </details>
              </li>
            <?php else: ?>
              <li>
                <a class="sidebar-boton" href="<?= Html::e($el['ruta']) ?>" data-etiqueta="<?= Html::e($el['texto']) ?>"<?= $el['ruta'] === $rutaActual ? ' aria-current="page"' : '' ?><?= $atajo($el, $el['texto']) ?>>
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
        <a class="sidebar-boton" href="/ajustes" data-atajo="g s" data-atajo-texto="Ir a ajustes">
          <?= Icono::svg('settings') ?>
          <span>Ajustes</span>
        </a>
        <a class="sidebar-boton" href="/salir">
          <?= Icono::svg('log-out') ?>
          <span>Cerrar sesión</span>
        </a>
      </div>
    </details>
  </div>
</aside>
