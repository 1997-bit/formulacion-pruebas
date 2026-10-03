<?php
declare(strict_types=1);

// Vista de referencia del sistema de diseño. Solo para desarrollo: no es parte de la app.
define('RAIZ', dirname(__DIR__));
require RAIZ . '/core/bootstrap.php';

use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sistema de diseño</title>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/base.css">
<link rel="stylesheet" href="/assets/css/componentes.css">
<script>
  // Tema antes de pintar, para que no parpadee: el elegido por la persona o, si no eligió, el del sistema.
  (() => {
    let t = null;
    try { t = localStorage.getItem('tema'); } catch (e) {}
    document.documentElement.dataset.tema = t || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  })();
</script>
<script src="/assets/js/app.js" defer></script>
<style>
  /* Estilos solo de esta página de muestra */
  body { background: var(--tenue); }
  main { max-width: 1120px; margin: 0 auto; padding: 16px 16px 64px; }

  .barra {
    display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    padding: 12px 16px; background: var(--fondo); border-bottom: 1px solid var(--borde);
  }
  .barra img { height: 28px; }
  /* El logo es negro: en tema oscuro se invierte para que se vea */
  :root[data-tema="dark"] .barra img { filter: invert(1); }
  @media (prefers-color-scheme: dark) { :root:not([data-tema]) .barra img { filter: invert(1); } }

  .seccion { margin-top: 40px; }
  .seccion > h2 { font-size: var(--letra-base); font-weight: 600; letter-spacing: 0; margin: 0 0 4px; }
  .seccion > p { font-size: var(--letra-sm); color: var(--tenue-texto); margin: 0 0 16px; max-width: 48ch; }

  .rejilla { display: grid; gap: 12px; grid-template-columns: repeat(auto-fill, minmax(min(100%, 420px), 1fr)); }
  .rejilla-4 { grid-template-columns: repeat(auto-fill, minmax(min(100%, 240px), 1fr)); }

  .marco { background: var(--fondo); border: 1px solid var(--borde); border-radius: var(--radio-2xl); overflow: hidden; }
  .escenario { min-height: 120px; display: grid; place-items: center; padding: 20px 12px; }
  .escenario-libre { padding: 20px; }
  .etiqueta {
    border-top: 1px solid var(--borde); padding: 12px; margin: 0;
    font-family: var(--fuente-mono); font-size: var(--letra-xs);
  }

  .par { display: flex; align-items: center; justify-content: space-between; gap: 8px; min-height: 80px; padding: 12px 16px; }
  .par span { font-size: var(--letra-2xl); font-weight: 600; }
  .par code { font-family: var(--fuente-mono); font-size: var(--letra-xs); text-align: right; }

  .esquina { min-height: 80px; position: relative; overflow: hidden; }
  .esquina div {
    position: absolute; left: 50%; top: 24px; width: 60%; height: 100px;
    background: var(--tenue); border-top: 1px solid var(--texto); border-left: 1px solid var(--texto);
  }

  .menu-demo {
    width: 100%; list-style: none; margin: 0; padding: 4px;
    background: var(--flotante); color: var(--flotante-texto);
    border: 1px solid var(--borde); border-radius: var(--radio-lg); box-shadow: var(--sombra-md);
  }
  .menu-demo li { padding: 8px 10px; border-radius: var(--radio-sm); cursor: default; }
  .menu-demo li:hover, .menu-demo li.activo { background: var(--acento); color: var(--acento-texto); }

  .flotante-demo {
    width: 100%; background: var(--flotante); color: var(--flotante-texto);
    border: 1px solid var(--borde); border-radius: var(--radio-lg); box-shadow: var(--sombra-md); padding: 12px;
  }
  .flotante-demo strong { display: block; font-weight: 500; margin-bottom: 4px; }
  .flotante-demo p { margin: 0; color: var(--tenue-texto); font-size: var(--letra-sm); }
  .flotante-demo .insignia { border-radius: var(--radio-total); }
  .flotante-demo .fila { justify-content: flex-start; margin-top: 8px; }

  .globo-demo {
    background: var(--flotante); color: var(--flotante-texto); font-size: var(--letra-sm);
    border: 1px solid var(--borde); border-radius: var(--radio-md); box-shadow: var(--sombra-md); padding: 6px 12px;
  }

  /* Diálogo dibujado estático (el real se abre en la sección "Diálogo") */
  .dialogo-demo { position: relative; width: 100%; max-width: 24rem; }
  .dialogo-demo h2 { font-size: var(--letra-lg); font-weight: 600; letter-spacing: 0; margin: 0 0 6px; }
  .dialogo-demo p { margin: 0 0 16px; font-size: var(--letra-sm); color: var(--tenue-texto); }

  .escena-velo { position: relative; min-height: 220px; display: grid; place-items: center; padding: 16px; overflow: hidden; }
  .escena-fondo { position: absolute; inset: 0; margin: 12px; width: calc(100% - 24px); font-size: var(--letra-sm); }
  .escena-fondo td { padding: 8px 4px; }
  .escena-capa { position: absolute; inset: 0; }

  .tipografia { padding: 0 20px 8px; }

  /* Índice fijo para saltar entre secciones (útil sobre todo en el celular) */
  .indice {
    position: sticky; top: 0; z-index: 20;
    display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none;
    padding: 8px 16px; background: var(--fondo); border-bottom: 1px solid var(--borde);
  }
  .indice a {
    flex-shrink: 0; padding: 6px 12px; border-radius: var(--radio-total);
    font-size: var(--letra-sm); color: var(--secundario-texto); background: var(--secundario); text-decoration: none;
  }
  .indice a:hover { background: var(--acento); color: var(--acento-texto); }
  .seccion { scroll-margin-top: 64px; }

  /* QR para abrir la página en el celular */
  .qr-caja { width: min(100%, 220px); padding: 12px; background: #FFFFFF; border-radius: var(--radio-xl); }
  .qr { display: block; width: 100%; height: auto; }


  /* Al imprimir esta página sale solo la vista formal de la sección "Impresión" */
  @media print {
    .barra, .indice, main > :not(#detalle), #detalle > :is(h2, p) { display: none !important; }
    body { background: #FFFFFF; }
    main { padding: 0; }
    #detalle { margin: 0; }
    #detalle .marco { border: 0; padding: 0; }
  }

  @media (max-width: 40rem) {
    main { padding: 8px 12px 48px; }
    .seccion { margin-top: 32px; }
    .escenario-libre { padding: 14px; }
    .barra { padding: 8px 12px; }
    .indice { padding: 8px 12px; }
  }
</style>
</head>
<body>
<header class="barra">
  <img src="/Frame.svg" alt="Logo">
  <button class="btn btn-fantasma btn-icono" id="tema" type="button" aria-pressed="false" aria-label="Modo oscuro"
          data-tooltip="Cambiar a oscuro" data-tooltip-lado="abajo" data-tooltip-alinear="fin">
    <?= Icono::svg('moon', 'icono tema-luna') ?><?= Icono::svg('sun', 'icono tema-sol') ?>
  </button>
</header>
<nav class="indice" aria-label="Secciones">
  <a href="#colores">Colores</a>
  <a href="#formulario">Formulario</a>
  <a href="#matrices">Tablas editables</a>
  <a href="#lista">Lista</a>
  <a href="#historial">Historial</a>
  <a href="#panel">Panel</a>
  <a href="#acceso">Acceso</a>
  <a href="#miembros">Casillas</a>
  <a href="#detalle">Detalle</a>
  <a href="#tooltips">Tooltips</a>
  <a href="#dialogo">Diálogo</a>
  <a href="#alertas">Alertas</a>
  <a href="#vacio">Vacío</a>
  <a href="#radios">Radios</a>
  <a href="#sombras">Sombras</a>
  <a href="#insignias">Insignias</a>
  <a href="#tipografia">Tipografía</a>
  <a href="#celular">Celular</a>
</nav>

<main>
  <header class="encabezado" style="margin-top:24px">
    <div>
      <h1>Sistema de diseño</h1>
      <p>Referencia de colores y componentes. Solo para desarrollo: no es parte de la app ni se entrega.</p>
    </div>
  </header>

  <!-- ================= Colores ================= -->
  <section class="seccion" id="colores">
    <h2>Colores</h2>
    <p>Cada fondo con el texto que va encima. Abajo, el nombre equivalente en shadcn.</p>
    <div class="rejilla rejilla-4">
      <div class="marco"><div class="par" style="background:var(--fondo);color:var(--texto)"><span>Aa</span><code>--fondo<br>--texto</code></div><p class="etiqueta">background · foreground</p></div>
      <div class="marco"><div class="par" style="background:var(--tarjeta);color:var(--tarjeta-texto)"><span>Aa</span><code>--tarjeta<br>--tarjeta-texto</code></div><p class="etiqueta">card</p></div>
      <div class="marco"><div class="par" style="background:var(--flotante);color:var(--flotante-texto)"><span>Aa</span><code>--flotante<br>--flotante-texto</code></div><p class="etiqueta">popover</p></div>
      <div class="marco"><div class="par" style="background:var(--primario);color:var(--primario-texto)"><span>Aa</span><code>--primario<br>--primario-texto</code></div><p class="etiqueta">primary · azul de la marca</p></div>
      <div class="marco"><div class="par" style="background:var(--secundario);color:var(--secundario-texto)"><span>Aa</span><code>--secundario<br>--secundario-texto</code></div><p class="etiqueta">secondary</p></div>
      <div class="marco"><div class="par" style="background:var(--tenue);color:var(--tenue-texto)"><span>Aa</span><code>--tenue<br>--tenue-texto</code></div><p class="etiqueta">muted</p></div>
      <div class="marco"><div class="par" style="background:var(--acento);color:var(--acento-texto)"><span>Aa</span><code>--acento<br>--acento-texto</code></div><p class="etiqueta">accent · tinte azul</p></div>
      <div class="marco"><div class="par" style="background:var(--destacado);color:var(--destacado-texto)"><span>Aa</span><code>--destacado<br>--destacado-texto</code></div><p class="etiqueta">naranja de apoyo</p></div>
      <div class="marco"><div class="par" style="background:var(--peligro);color:var(--peligro-texto)"><span>Aa</span><code>--peligro<br>--peligro-texto</code></div><p class="etiqueta">destructive · FAULT</p></div>
      <div class="marco"><div class="par" style="background:var(--exito);color:var(--exito-texto)"><span>Aa</span><code>--exito<br>--exito-texto</code></div><p class="etiqueta">OK</p></div>
      <div class="marco"><div class="par" style="background:var(--aviso);color:var(--aviso-texto)"><span>Aa</span><code>--aviso<br>--aviso-texto</code></div><p class="etiqueta">Pendiente</p></div>
      <div class="marco"><div class="par" style="background:var(--info);color:var(--info-texto)"><span>Aa</span><code>--info<br>--info-texto</code></div><p class="etiqueta">En progreso (incidente)</p></div>
      <div class="marco"><div class="par" style="background:var(--fondo);color:var(--enlace)"><span>Aa</span><code>--enlace<br>--anillo</code></div><p class="etiqueta">link · ring</p></div>
      <div class="marco"><div class="par" style="background:var(--fondo);color:var(--error)"><span>Aa</span><code>--error</code></div><p class="etiqueta">texto de error</p></div>
      <div class="marco"><div class="par" style="background:var(--fondo);color:var(--correcto)"><span>Aa</span><code>--correcto</code></div><p class="etiqueta">texto e ícono de éxito</p></div>
    </div>
  </section>

  <!-- ================= Formulario ================= -->
  <section class="seccion" id="formulario">
    <h2>Formulario</h2>
    <p>Formularios 1, 6 y 10. Etiqueta, obligatorio, ayuda y error en texto enlazado con aria-describedby (RNF-07). La persona anota a mano el resultado: la plataforma solo da seguimiento, no ejecuta pruebas. El tipo de evidencia muestra solo sus campos; sin JS se ven todos.</p>
    <div class="rejilla">
      <div class="marco" style="grid-column:1/-1">
        <div class="escenario-libre">
          <div class="alerta alerta-exito" role="status"><?= Icono::svg('circle-check') ?>
            <p class="alerta-titulo">Caso guardado.</p>
            <div class="alerta-descripcion"><span class="codigo">SIS-003</span> se guardó con todos sus datos. <a href="#">Ver caso</a>.</div>
          </div>
        </div>
        <p class="etiqueta">Después de guardar · arriba de la página a la que se llega</p>
      </div>

      <div class="marco escenario-libre" style="grid-column:1/-1">
        <form class="pila" action="#" onsubmit="return false">
          <p class="titulo-seccion">Registrar caso de prueba <small class="campo-ayuda">Formulario 1</small></p>
          <div class="campos">
            <div class="campo">
              <label for="f-proyecto">Proyecto <span class="requerido" aria-hidden="true">*</span></label>
              <div class="select">
                <select class="control" id="f-proyecto" name="proyecto" required>
                  <option selected>Portal web</option>
                  <option>App de inventario</option>
                </select>
              </div>
            </div>
            <div class="campo">
              <label for="f-req">Requerimiento <span class="requerido" aria-hidden="true">*</span></label>
              <div class="select">
                <select class="control" id="f-req" name="requerimiento" required>
                  <option value="">Elegir…</option>
                  <option selected>RF-01 · Iniciar sesión</option>
                  <option>RF-02 · Crear cuenta</option>
                  <option>RF-24 · Anotar resultado</option>
                </select>
              </div>
            </div>
            <div class="campo">
              <label for="f-tipo">Tipo de prueba <span class="requerido" aria-hidden="true">*</span></label>
              <div class="select">
                <select class="control" id="f-tipo" name="tipo" required>
                  <option value="">Elegir…</option>
                  <?= Catalogo::opciones('tipo_prueba', 3) ?>
                </select>
              </div>
            </div>
            <div class="campo">
              <label for="f-codigo">Código</label>
              <input class="control codigo" id="f-codigo" value="SIS-003" disabled aria-describedby="f-codigo-ayuda">
              <p class="campo-ayuda" id="f-codigo-ayuda">Se genera al guardar con la sigla del tipo: SIS-001.</p>
            </div>
            <div class="campo">
              <label for="f-modulo">Módulo o funcionalidad <span class="requerido" aria-hidden="true">*</span></label>
              <input class="control" id="f-modulo" name="modulo" required value="Inicio de sesión">
            </div>
            <div class="campo">
              <label for="f-plataforma">Plataforma <span class="requerido" aria-hidden="true">*</span></label>
              <div class="select">
                <select class="control" id="f-plataforma" name="plataforma" required>
                  <option value="">Elegir…</option>
                  <?= Catalogo::opciones('plataforma', 1) ?>
                </select>
              </div>
            </div>
            <div class="campo">
              <label for="f-entorno">Entorno</label>
              <input class="control" id="f-entorno" name="entorno" maxlength="255" value="Windows 11, Chrome 129, v1.2" aria-describedby="f-entorno-ayuda">
              <p class="campo-ayuda" id="f-entorno-ayuda">Detalle de la plataforma: sistema operativo, navegador y versión probada.</p>
            </div>
          </div>

          <fieldset class="grupo">
            <legend>Técnica utilizada <span class="requerido" aria-hidden="true">*</span></legend>
            <div class="grupo grupo-fila">
              <label class="opcion"><input type="radio" name="tecnica" value="1" required checked> Caja negra</label>
              <label class="opcion"><input type="radio" name="tecnica" value="2"> Caja blanca</label>
            </div>
          </fieldset>
          <div class="campos">
            <div class="campo">
              <label for="f-subtecnica">Sub-técnica <span class="requerido" aria-hidden="true">*</span></label>
              <div class="select">
                <select class="control" id="f-subtecnica" name="subtecnica" required aria-describedby="f-subtecnica-ayuda">
                  <option value="">Elegir…</option>
                  <?php foreach (['Caja negra' => [1, 10], 'Caja blanca' => [11, 20]] as $grupo => [$desde, $hasta]): ?>
                    <optgroup label="<?= $grupo ?>">
                      <?php foreach (Catalogo::valores('subtecnica') as $clave => $sub): ?>
                        <?php if ($clave >= $desde && $clave <= $hasta): ?>
                          <option value="<?= $clave ?>"<?= $clave === 1 ? ' selected' : '' ?>><?= Html::e($sub['texto']) ?></option>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </optgroup>
                  <?php endforeach; ?>
                </select>
              </div>
              <p class="campo-ayuda" id="f-subtecnica-ayuda">Debe ser de la técnica elegida: 10 de caja negra y 10 de caja blanca.</p>
            </div>
            <div class="campo">
              <label for="f-inicio">Fecha de inicio <span class="requerido" aria-hidden="true">*</span></label>
              <input class="control" id="f-inicio" name="fecha_inicio" type="date" required value="2026-09-30">
            </div>
            <div class="campo">
              <label for="f-fin">Fecha final <span class="requerido" aria-hidden="true">*</span></label>
              <input class="control" id="f-fin" name="fecha_fin" type="date" required data-desde="f-inicio" value="2026-10-03" aria-describedby="f-fin-ayuda">
              <p class="campo-ayuda" id="f-fin-ayuda">Igual o después de la fecha de inicio.</p>
            </div>
          </div>

          <div class="campo">
            <label for="f-desc">Objetivo <span class="requerido" aria-hidden="true">*</span></label>
            <textarea class="control" id="f-desc" name="objetivo" required aria-invalid="true" aria-describedby="f-desc-error"></textarea>
            <p class="campo-error" id="f-desc-error"><?= Icono::svg('circle-x') ?>Escriba qué se pretende verificar.</p>
          </div>
          <div class="campos">
            <div class="campo">
              <label for="f-pre">Precondiciones</label>
              <textarea class="control" id="f-pre" name="precondiciones" aria-describedby="f-pre-ayuda">Existe el usuario demo.</textarea>
              <p class="campo-ayuda" id="f-pre-ayuda">Estado inicial requerido.</p>
            </div>
            <div class="campo">
              <label for="f-entrada">Datos de entrada <span class="requerido" aria-hidden="true">*</span></label>
              <textarea class="control" id="f-entrada" name="entrada" required aria-describedby="f-entrada-ayuda">Usuario: demo
Contraseña: (vacía)</textarea>
              <p class="campo-ayuda" id="f-entrada-ayuda">Valores con los que se prueba.</p>
            </div>
            <div class="campo">
              <label for="f-pasos">Pasos de ejecución <span class="requerido" aria-hidden="true">*</span></label>
              <textarea class="control" id="f-pasos" name="pasos" required aria-describedby="f-pasos-ayuda">1. Abrir la página de inicio de sesión.
2. Escribir el usuario y dejar la contraseña vacía.
3. Pulsar "Iniciar sesión".</textarea>
              <p class="campo-ayuda" id="f-pasos-ayuda">Un paso por línea.</p>
            </div>
            <div class="campo">
              <label for="f-esperado">Resultado esperado <span class="requerido" aria-hidden="true">*</span></label>
              <textarea class="control" id="f-esperado" name="resultado_esperado" required>El sistema no inicia sesión y muestra "Usuario o contraseña incorrectos".</textarea>
            </div>
          </div>


          <hr class="separador">
          <p class="titulo-seccion" style="margin:0">Resultado</p>
          <fieldset class="resultado">
            <legend>Estado</legend>
            <label class="resultado-opcion resultado-pendiente">
              <input type="radio" name="estado" value="0" checked>
              <?= Icono::svg('circle-alert') ?> Pendiente
            </label>
            <label class="resultado-opcion resultado-ok">
              <input type="radio" name="estado" value="1">
              <?= Icono::svg('circle-check') ?> OK
            </label>
            <label class="resultado-opcion resultado-fault">
              <input type="radio" name="estado" value="2">
              <?= Icono::svg('circle-x') ?> FAULT
            </label>
          </fieldset>
          <div class="campos">
            <div class="campo">
              <label for="f-obtenido">Resultado obtenido</label>
              <textarea class="control" id="f-obtenido" name="resultado_obtenido" placeholder="Qué hizo el sistema"></textarea>
            </div>
            <div class="campo">
              <label for="f-obs">Observaciones</label>
              <textarea class="control" id="f-obs" name="observaciones" placeholder="Notas adicionales"></textarea>
            </div>
          </div>

          <fieldset class="grupo">
            <legend>Tipo de evidencia <span class="requerido" aria-hidden="true">*</span></legend>
            <div class="grupo grupo-fila">
              <?php foreach (Catalogo::valores('tipo_evidencia') as $clave => $tipo): ?>
                <label class="opcion"><input type="radio" name="evidencia_tipo" value="<?= $clave ?>" required<?= $clave === 1 ? ' checked' : '' ?>> <?= Html::e($tipo['texto']) ?></label>
              <?php endforeach; ?>
            </div>
          </fieldset>
          <div class="campos">
            <div class="campo" data-cuando="evidencia_tipo=1">
              <label for="f-captura">Captura <span class="requerido" aria-hidden="true">*</span></label>
              <input class="control" id="f-captura" name="evidencia" type="file" accept=".png,.jpg,.jpeg"
                     data-vista-previa="f-captura-previa" aria-describedby="f-captura-ayuda">
              <p class="campo-ayuda" id="f-captura-ayuda">PNG o JPG, máximo 5 MB.</p>
              <img id="f-captura-previa" class="vista-previa" alt="" hidden>
            </div>
            <div class="campo" data-cuando="evidencia_tipo=2">
              <label for="f-archivo">Archivo <span class="requerido" aria-hidden="true">*</span></label>
              <input class="control" id="f-archivo" name="evidencia" type="file" accept=".pdf,.txt,.log" aria-describedby="f-archivo-ayuda">
              <p class="campo-ayuda" id="f-archivo-ayuda">Log o documento: PDF, TXT o LOG, máximo 5 MB.</p>
            </div>
            <div class="campo" data-cuando="evidencia_tipo=3">
              <label for="f-enlace">Enlace <span class="requerido" aria-hidden="true">*</span></label>
              <input class="control" id="f-enlace" name="evidencia_enlace" type="url" placeholder="https://…" aria-describedby="f-enlace-ayuda">
              <p class="campo-ayuda" id="f-enlace-ayuda">Video, carpeta de Drive o ejecución en CI.</p>
            </div>
            <div class="campo">
              <label for="f-evidencia-desc">Qué muestra la evidencia <span class="requerido" aria-hidden="true">*</span></label>
              <input class="control" id="f-evidencia-desc" name="evidencia_descripcion" required maxlength="255" aria-describedby="f-evidencia-desc-ayuda"
                     placeholder="Pantalla de login con el mensaje de error">
              <p class="campo-ayuda" id="f-evidencia-desc-ayuda">Texto alternativo de la captura (RNF-07).</p>
            </div>
          </div>

          <p class="campo-ayuda"><span class="requerido" aria-hidden="true">*</span> Campo obligatorio</p>
          <div class="acciones">
            <button class="btn btn-secundario" type="button">Cancelar</button>
            <button class="btn btn-primario" type="submit">Guardar caso</button>
          </div>
        </form>
      </div>

      <div class="marco escenario-libre">
        <form class="pila" action="#" onsubmit="return false">
          <p class="titulo-seccion">Registrar incidente <small class="campo-ayuda">Formulario 10 · de <span class="codigo">SIS-002</span></small></p>
          <div class="campos">
            <div class="campo">
              <label for="f-inc-codigo">ID del incidente</label>
              <input class="control codigo" id="f-inc-codigo" value="BUG-002" disabled aria-describedby="f-inc-codigo-ayuda">
              <p class="campo-ayuda" id="f-inc-codigo-ayuda">Se genera al guardar: BUG y un consecutivo del proyecto.</p>
            </div>
            <div class="campo">
              <label for="f-inc-modulo">Módulo <span class="requerido" aria-hidden="true">*</span></label>
              <input class="control" id="f-inc-modulo" name="modulo" required maxlength="100" value="Inicio de sesión" aria-describedby="f-inc-modulo-ayuda">
              <p class="campo-ayuda" id="f-inc-modulo-ayuda">Componente afectado. Sale del caso; se puede cambiar.</p>
            </div>
          </div>
          <div class="campo">
            <label for="f-inc-titulo">Título <span class="requerido" aria-hidden="true">*</span></label>
            <input class="control" id="f-inc-titulo" name="titulo" required maxlength="150" value="Inicia sesión con contraseña vacía">
          </div>
          <fieldset class="grupo">
            <legend>Severidad <span class="requerido" aria-hidden="true">*</span></legend>
            <div class="grupo grupo-fila">
              <label class="opcion"><input type="radio" name="severidad" value="1"> Baja</label>
              <label class="opcion"><input type="radio" name="severidad" value="2"> Media</label>
              <label class="opcion"><input type="radio" name="severidad" value="3" checked> Alta</label>
              <label class="opcion"><input type="radio" name="severidad" value="4"> Crítica</label>
            </div>
          </fieldset>
          <fieldset class="grupo" aria-describedby="f-prioridad-error">
            <legend>Prioridad <span class="requerido" aria-hidden="true">*</span></legend>
            <div class="grupo grupo-fila">
              <label class="opcion"><input type="radio" name="prioridad" value="1" required aria-invalid="true"> Baja</label>
              <label class="opcion"><input type="radio" name="prioridad" value="2" aria-invalid="true"> Media</label>
              <label class="opcion"><input type="radio" name="prioridad" value="3" aria-invalid="true"> Alta</label>
            </div>
            <p class="campo-error" id="f-prioridad-error"><?= Icono::svg('circle-x') ?>Elija la prioridad.</p>
          </fieldset>
          <div class="campo">
            <label for="f-inc-desc">Descripción <span class="requerido" aria-hidden="true">*</span></label>
            <textarea class="control" id="f-inc-desc" name="descripcion" required aria-describedby="f-inc-desc-ayuda">El login acepta una contraseña vacía y entra a la cuenta.</textarea>
            <p class="campo-ayuda" id="f-inc-desc-ayuda">Detalle del problema.</p>
          </div>
          <div class="campo">
            <label for="f-inc-pasos">Pasos para reproducir <span class="requerido" aria-hidden="true">*</span></label>
            <textarea class="control" id="f-inc-pasos" name="pasos" required>1. Abrir el login.
2. Escribir "demo" y dejar la contraseña vacía.
3. Pulsar "Iniciar sesión".</textarea>
          </div>
          <div class="campos">
            <div class="campo">
              <label for="f-inc-esperado">Resultado esperado <span class="requerido" aria-hidden="true">*</span></label>
              <textarea class="control" id="f-inc-esperado" name="resultado_esperado" required>No inicia sesión.</textarea>
            </div>
            <div class="campo">
              <label for="f-inc-obtenido">Resultado obtenido <span class="requerido" aria-hidden="true">*</span></label>
              <textarea class="control" id="f-inc-obtenido" name="resultado_obtenido" required>Inicia sesión.</textarea>
            </div>
          </div>

          <fieldset class="grupo">
            <legend>Tipo de evidencia</legend>
            <div class="grupo grupo-fila">
              <?php foreach (Catalogo::valores('tipo_evidencia') as $clave => $tipo): ?>
                <label class="opcion"><input type="radio" name="evidencia_tipo" value="<?= $clave ?>"<?= $clave === 1 ? ' checked' : '' ?>> <?= Html::e($tipo['texto']) ?></label>
              <?php endforeach; ?>
            </div>
          </fieldset>
          <div class="campo" data-cuando="evidencia_tipo=1">
            <label for="f-inc-captura">Captura</label>
            <input class="control" id="f-inc-captura" name="evidencia" type="file" accept=".png,.jpg,.jpeg" data-vista-previa="f-inc-captura-previa">
            <img id="f-inc-captura-previa" class="vista-previa" alt="" hidden>
          </div>
          <div class="campo" data-cuando="evidencia_tipo=2">
            <label for="f-inc-archivo">Archivo</label>
            <input class="control" id="f-inc-archivo" name="evidencia" type="file" accept=".pdf,.txt,.log">
          </div>
          <div class="campo" data-cuando="evidencia_tipo=3">
            <label for="f-inc-enlace">Enlace</label>
            <input class="control" id="f-inc-enlace" name="evidencia_enlace" type="url" placeholder="https://…">
          </div>
          <p class="campo-ayuda" style="margin-top:-8px">Opcional: el caso ya tiene la suya. Mismo bloque que el formulario 1.</p>

          <div class="campos">
            <div class="campo">
              <label for="f-inc-estado">Estado</label>
              <div class="select">
                <select class="control" id="f-inc-estado" name="estado">
                  <?= Catalogo::opciones('estado_incidente', 0) ?>
                </select>
              </div>
            </div>
            <div class="campo">
              <label for="f-inc-asignado">Asignado a</label>
              <div class="select">
                <select class="control" id="f-inc-asignado" name="asignado">
                  <option value="">Sin asignar</option><option selected>María Pérez</option><option>José Rodríguez</option>
                </select>
              </div>
            </div>
          </div>
          <fieldset class="grupo">
            <legend>Bloqueo</legend>
            <label class="opcion"><input type="checkbox" name="es_stopper" value="1" checked> Es stopper: impide cerrar el plan</label>
          </fieldset>
          <div class="acciones">
            <button class="btn btn-primario" type="submit">Registrar incidente</button>
          </div>
        </form>
      </div>

      <div class="marco escenario-libre">
        <form class="pila" action="#" onsubmit="return false">
          <p class="titulo-seccion">Plan de pruebas <small class="campo-ayuda">Formulario 6 · uno por proyecto</small></p>
          <div class="campos">
            <div class="campo">
              <label for="f-plan-proyecto">Proyecto <span class="requerido" aria-hidden="true">*</span></label>
              <div class="select">
                <select class="control" id="f-plan-proyecto" name="proyecto_id" required>
                  <option selected>Portal web</option><option>App de inventario</option>
                </select>
              </div>
            </div>
            <div class="campo">
              <label for="f-plan-version">Versión <span class="requerido" aria-hidden="true">*</span></label>
              <input class="control" id="f-plan-version" name="version" required maxlength="20" value="1.0">
            </div>
            <div class="campo">
              <label for="f-plan-responsable">Responsable <span class="requerido" aria-hidden="true">*</span></label>
              <div class="select">
                <select class="control" id="f-plan-responsable" name="responsable_id" required>
                  <option selected>María Pérez</option><option>José Rodríguez</option>
                </select>
              </div>
            </div>
            <div class="campo">
              <label for="f-plan-fecha">Fecha <span class="requerido" aria-hidden="true">*</span></label>
              <input class="control" id="f-plan-fecha" name="fecha" type="date" required value="2026-10-03">
            </div>
          </div>
          <div class="campo">
            <label for="f-plan-alcance">Alcance <span class="requerido" aria-hidden="true">*</span></label>
            <textarea class="control" id="f-plan-alcance" name="alcance" required aria-describedby="f-plan-alcance-ayuda">Se prueban acceso, casos y formularios. No se prueba la carga con muchos usuarios.</textarea>
            <p class="campo-ayuda" id="f-plan-alcance-ayuda">Qué se va a probar y qué no.</p>
          </div>
          <div class="campo">
            <label for="f-plan-objetivos">Objetivos <span class="requerido" aria-hidden="true">*</span></label>
            <textarea class="control" id="f-plan-objetivos" name="objetivos" required aria-describedby="f-plan-objetivos-ayuda"></textarea>
            <p class="campo-ayuda" id="f-plan-objetivos-ayuda">Metas de las pruebas.</p>
          </div>
          <fieldset class="grupo">
            <legend>Estrategia <span class="requerido" aria-hidden="true">*</span></legend>
            <div class="grupo grupo-fila">
              <?php foreach (Catalogo::valores('estrategia') as $clave => $e): ?>
                <label class="opcion"><input type="radio" name="estrategia" value="<?= $clave ?>" required<?= $clave === 3 ? ' checked' : '' ?>> <?= Html::e($e['texto']) ?></label>
              <?php endforeach; ?>
            </div>
          </fieldset>
          <div class="campos">
            <div class="campo">
              <label for="f-plan-recursos">Recursos</label>
              <textarea class="control" id="f-plan-recursos" name="recursos" aria-describedby="f-plan-recursos-ayuda"></textarea>
              <p class="campo-ayuda" id="f-plan-recursos-ayuda">Herramientas, personal y tiempo.</p>
            </div>
            <div class="campo">
              <label for="f-plan-cronograma">Cronograma</label>
              <textarea class="control" id="f-plan-cronograma" name="cronograma" aria-describedby="f-plan-cronograma-ayuda"></textarea>
              <p class="campo-ayuda" id="f-plan-cronograma-ayuda">Fases y fechas.</p>
            </div>
            <div class="campo">
              <label for="f-plan-criterios">Criterios de aceptación <span class="requerido" aria-hidden="true">*</span></label>
              <textarea class="control" id="f-plan-criterios" name="criterios_aceptacion" required aria-describedby="f-plan-criterios-ayuda"></textarea>
              <p class="campo-ayuda" id="f-plan-criterios-ayuda">Cuándo se consideran exitosas las pruebas.</p>
            </div>
            <div class="campo">
              <label for="f-plan-riesgos">Riesgos</label>
              <textarea class="control" id="f-plan-riesgos" name="riesgos" aria-describedby="f-plan-riesgos-ayuda"></textarea>
              <p class="campo-ayuda" id="f-plan-riesgos-ayuda">Cada riesgo con su mitigación.</p>
            </div>
          </div>
          <div class="campo">
            <label for="f-plan-estado">Estado</label>
            <div class="select">
              <select class="control" id="f-plan-estado" name="estado">
                <?= Catalogo::opciones('estado_plan', 0) ?>
              </select>
            </div>
          </div>
          <div class="acciones">
            <button class="btn btn-primario" type="submit">Guardar plan</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <!-- ================= Tablas editables ================= -->
  <?php
  // Una fila de tabla editable. Vacía sirve de plantilla para "Agregar fila".
  // $columnas: nombre => [etiqueta, control]; control es textarea, un type de input o catalogo:nombre.
  $fila = function (array $columnas, array $v = []): string {
      $html = '<tr>';
      foreach ($columnas as $nombre => [$etiqueta, $control]) {
          $atributos = 'class="control" name="' . $nombre . '[]" aria-label="' . Html::e($etiqueta) . '"';
          $valor = $v[$nombre] ?? '';
          $html .= '<td data-columna="' . Html::e($etiqueta) . '"' . ($control === 'number' ? ' class="celda-corta"' : '') . '>' . match (true) {
              $control === 'textarea' => '<textarea ' . $atributos . '>' . Html::e((string) $valor) . '</textarea>',
              str_starts_with($control, 'catalogo:') => '<div class="select"><select ' . $atributos . '><option value="">Elegir…</option>'
                  . Catalogo::opciones(substr($control, 9), $valor === '' ? null : (int) $valor) . '</select></div>',
              default => '<input ' . $atributos . ' type="' . $control . '"' . ($control === 'number' ? ' min="1" inputmode="numeric"' : '') . ' value="' . Html::e((string) $valor) . '">',
          } . '</td>';
      }
      return $html
          . '<td class="celda-acciones"><button class="btn btn-fantasma btn-icono" type="button" data-quitar-fila aria-label="Quitar fila" data-tooltip="Quitar fila" data-tooltip-alinear="fin">'
          . Icono::svg('trash-2') . '</button></td>'
          . '</tr>';
  };
  $colClases = [
      'campo' => ['Campo', 'text'], 'valida' => ['Clase válida', 'textarea'], 'invalidas' => ['Clases inválidas', 'textarea'],
      'valores' => ['Valores representativos', 'text'], 'esperado' => ['Resultado esperado', 'textarea'],
  ];
  $colLimite = [
      'campo' => ['Campo', 'text'], 'rango' => ['Rango válido', 'text'], 'minimo' => ['Valor mínimo', 'text'],
      'maximo' => ['Valor máximo', 'text'], 'limites' => ['Valores límite a probar', 'text'], 'esperado' => ['Resultado esperado', 'textarea'],
  ];
  $colPortafolio = [
      'semana' => ['Semana', 'number'], 'evidencia' => ['Evidencia', 'text'], 'tipo' => ['Tipo', 'catalogo:tipo_portafolio'],
      'fecha' => ['Fecha', 'date'], 'observaciones' => ['Observaciones', 'textarea'],
  ];
  ?>
  <section class="seccion" id="matrices">
    <h2>Tablas editables</h2>
    <p>Formularios 2, 3, 4, 5, 7, 8 y 9: la misma tabla con controles en las celdas, cada uno con aria-label. Las filas con datos (2, 3, 8, 9) se vuelven tarjetas en el celular; las tablas por columnas (4, 5, 7) se desplazan de lado.</p>
    <div class="pila">

      <div class="marco escenario-libre">
        <form class="pila" action="#" onsubmit="return false">
          <p class="titulo-seccion">Matriz de clases de equivalencia <small class="campo-ayuda">Formulario 2</small></p>
          <div class="tabla-contenedor">
            <table class="tabla tabla-editable tabla-tarjetas">
              <caption class="solo-lector">Clases de equivalencia por campo</caption>
              <thead>
                <tr><th scope="col">Campo</th><th scope="col">Clase válida</th><th scope="col">Clases inválidas</th><th scope="col">Valores representativos</th><th scope="col">Resultado esperado</th><th scope="col" class="celda-acciones"><span class="solo-lector">Acciones</span></th></tr>
              </thead>
              <tbody id="clases">
                <?= $fila($colClases, ['campo' => 'Edad', 'valida' => '18 a 65', 'invalidas' => 'Menor de 18; mayor de 65; no numérico', 'valores' => '30, 10, 70, abc', 'esperado' => 'Acepta 30; rechaza 10, 70 y abc']) ?>
                <?= $fila($colClases, ['campo' => 'Usuario', 'valida' => '4 a 20 letras o números', 'invalidas' => 'Vacío; más de 20; con espacios', 'valores' => 'demo, (vacío), a b', 'esperado' => 'Acepta demo; rechaza los demás']) ?>
              </tbody>
            </table>
          </div>
          <template id="clases-fila"><?= $fila($colClases) ?></template>
          <div class="acciones acciones-separadas">
            <button class="btn btn-secundario" type="button" data-agregar-fila="clases"><?= Icono::svg('plus') ?> Agregar fila</button>
            <button class="btn btn-primario" type="submit">Guardar matriz</button>
          </div>
        </form>
      </div>

      <div class="marco escenario-libre">
        <form class="pila" action="#" onsubmit="return false">
          <p class="titulo-seccion">Análisis de valor límite <small class="campo-ayuda">Formulario 3</small></p>
          <div class="tabla-contenedor">
            <table class="tabla tabla-editable tabla-tarjetas">
              <caption class="solo-lector">Valores frontera por campo</caption>
              <thead>
                <tr><?php foreach ($colLimite as [$etiqueta]): ?><th scope="col"><?= Html::e($etiqueta) ?></th><?php endforeach; ?><th scope="col" class="celda-acciones"><span class="solo-lector">Acciones</span></th></tr>
              </thead>
              <tbody id="limites">
                <?= $fila($colLimite, ['campo' => 'Edad', 'rango' => '18 a 65', 'minimo' => '18', 'maximo' => '65', 'limites' => '17, 18, 19, 64, 65, 66', 'esperado' => 'Acepta de 18 a 65; rechaza 17 y 66']) ?>
                <?= $fila($colLimite, ['campo' => 'Contraseña', 'rango' => '8 a 64 caracteres', 'minimo' => '8', 'maximo' => '64', 'limites' => '7, 8, 64, 65', 'esperado' => 'Acepta 8 y 64; rechaza 7 y 65']) ?>
              </tbody>
            </table>
          </div>
          <template id="limites-fila"><?= $fila($colLimite) ?></template>
          <div class="acciones acciones-separadas">
            <button class="btn btn-secundario" type="button" data-agregar-fila="limites"><?= Icono::svg('plus') ?> Agregar fila</button>
            <button class="btn btn-primario" type="submit">Guardar análisis</button>
          </div>
        </form>
      </div>

      <div class="marco escenario-libre">
        <form class="pila" action="#" onsubmit="return false">
          <p class="titulo-seccion">Tabla de decisión <small class="campo-ayuda">Formulario 4</small></p>
          <p class="campo-ayuda" style="margin-top:-8px">Las reglas salen de las condiciones: 2 condiciones = 4 reglas. El servidor arma las columnas; no se agregan a mano.</p>
          <div class="tabla-contenedor">
            <table class="tabla tabla-editable">
              <caption class="solo-lector">Condiciones y acciones por regla</caption>
              <thead>
                <tr><th scope="col">Condición / Acción</th><?php for ($r = 1; $r <= 4; $r++): ?><th scope="col" class="celda-centro">Regla <?= $r ?></th><?php endfor; ?></tr>
              </thead>
              <tbody>
                <tr class="fila-grupo"><th scope="colgroup" colspan="5">Condiciones</th></tr>
                <?php foreach ([['Usuario existe', 'VVFF'], ['Contraseña correcta', 'VFVF']] as $i => [$condicion, $valores]): ?>
                  <tr>
                    <th scope="row"><input class="control" name="condicion[]" aria-label="Condición <?= $i + 1 ?>" value="<?= Html::e($condicion) ?>"></th>
                    <?php for ($r = 0; $r < 4; $r++): ?>
                      <td class="celda-centro celda-corta">
                        <div class="select"><select class="control" name="condicion_regla[<?= $i ?>][]" aria-label="Condición <?= $i + 1 ?>, regla <?= $r + 1 ?>">
                          <option<?= $valores[$r] === 'V' ? ' selected' : '' ?>>V</option>
                          <option<?= $valores[$r] === 'F' ? ' selected' : '' ?>>F</option>
                          <option value="-">—</option>
                        </select></div>
                      </td>
                    <?php endfor; ?>
                  </tr>
                <?php endforeach; ?>
                <tr class="fila-grupo"><th scope="colgroup" colspan="5">Acciones</th></tr>
                <?php foreach ([['Inicia sesión', 'X---'], ['Muestra "Usuario o contraseña incorrectos"', '-XXX']] as $i => [$accion, $marcas]): ?>
                  <tr>
                    <th scope="row"><input class="control" name="accion[]" aria-label="Acción <?= $i + 1 ?>" value="<?= Html::e($accion) ?>"></th>
                    <?php for ($r = 0; $r < 4; $r++): ?>
                      <td class="celda-centro"><input type="checkbox" name="accion_regla[<?= $i ?>][<?= $r ?>]" aria-label="Acción <?= $i + 1 ?>, regla <?= $r + 1 ?>"<?= $marcas[$r] === 'X' ? ' checked' : '' ?>></td>
                    <?php endfor; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="acciones"><button class="btn btn-primario" type="submit">Guardar tabla</button></div>
        </form>
      </div>

      <div class="marco escenario-libre">
        <form class="pila" action="#" onsubmit="return false">
          <p class="titulo-seccion">Cobertura de caja blanca <small class="campo-ayuda">Formulario 5</small></p>
          <div class="tabla-contenedor">
            <table class="tabla tabla-editable">
              <caption class="solo-lector">Cobertura alcanzada por métrica</caption>
              <thead>
                <tr><th scope="col">Métrica</th><th scope="col">Total</th><th scope="col">Cubiertos</th><th scope="col">Cobertura</th><th scope="col">Herramienta</th></tr>
              </thead>
              <tbody>
                <?php foreach ([['Sentencia', 120, 114], ['Decisión', 40, 33], ['Condición', 56, 34], ['Caminos', 18, 7], ['Bucles', 6, 6]] as [$metrica, $total, $cubiertos]): ?>
                  <tr>
                    <th scope="row"><?= Html::e($metrica) ?></th>
                    <td class="celda-corta"><input class="control" type="number" min="0" inputmode="numeric" name="total[]" data-parte="total" aria-label="Total de <?= Html::e(mb_strtolower($metrica)) ?>" value="<?= $total ?>"></td>
                    <td class="celda-corta"><input class="control" type="number" min="0" inputmode="numeric" name="cubiertos[]" data-parte="cubiertos" aria-label="Cubiertos de <?= Html::e(mb_strtolower($metrica)) ?>" value="<?= $cubiertos ?>"></td>
                    <td style="vertical-align:middle">
                      <div class="progreso-celda">
                        <meter class="progreso" min="0" max="100" low="70" high="90" optimum="100" value="<?= round($cubiertos / $total * 100) ?>" aria-label="Cobertura de <?= Html::e(mb_strtolower($metrica)) ?>"></meter>
                        <output data-porcentaje><?= round($cubiertos / $total * 100) ?> %</output>
                      </div>
                    </td>
                    <td><input class="control" name="herramienta[]" aria-label="Herramienta" value="TestCover"></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p class="campo-ayuda">Verde desde 90 %, amarillo de 70 a 89 %, rojo bajo 70 %: los cortes de la rúbrica.</p>
        </form>
      </div>

      <div class="marco escenario-libre">
        <form class="pila" action="#" onsubmit="return false">
          <p class="titulo-seccion">Rúbrica de evaluación <small class="campo-ayuda">Formulario 7 · solo admin · Portal web</small></p>
          <?php
          // Descriptores del documento: Excelente (5), Bueno (4), Regular (3), Deficiente (1-2).
          $rubrica = [
              ['Diseño de casos', 5, ['Todos los casos bien documentados y justificados', 'La mayoría bien documentados', 'Algunos casos documentados', 'Casos incompletos o ausentes']],
              ['Aplicación de técnicas', 4, ['Aplica correctamente caja negra y blanca', 'Aplica la mayoría correctamente', 'Aplica parcialmente', 'No aplica correctamente']],
              ['Cobertura', 4, ['Alcanza más de 90 %', 'Alcanza 70 a 90 %', 'Alcanza 50 a 70 %', 'Menos de 50 %']],
              ['Uso de herramientas', 3, ['Domina Selenium, PHPUnit, JMeter, TestCover', 'Usa la mayoría', 'Usa algunas', 'No usa herramientas']],
              ['Documentación', 5, ['Completa, clara y organizada', 'Completa pero poco clara', 'Incompleta', 'Ausente']],
              ['Presentación', 4, ['Excelente comunicación', 'Buena comunicación', 'Comunicación regular', 'Deficiente']],
          ];
          ?>
          <div class="tabla-contenedor">
            <table class="tabla tabla-editable">
              <caption class="solo-lector">Puntos por criterio</caption>
              <thead><tr><th scope="col">Criterio</th><th scope="col">Puntos</th></tr></thead>
              <tbody>
                <?php foreach ($rubrica as $i => [$criterio, $puntos, $niveles]): ?>
                  <tr>
                    <th scope="row"><?= Html::e($criterio) ?></th>
                    <td>
                      <div class="select"><select class="control" name="puntos[]" data-grupo="rubrica" aria-label="Puntos de <?= Html::e(mb_strtolower($criterio)) ?>">
                        <option value="">Elegir…</option>
                        <?php foreach ([5 => 0, 4 => 1, 3 => 2, 2 => 3, 1 => 3] as $valor => $n): ?>
                          <option value="<?= $valor ?>"<?= $valor === $puntos ? ' selected' : '' ?>><?= $valor ?> · <?= Html::e($niveles[$n]) ?></option>
                        <?php endforeach; ?>
                      </select></div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr><th scope="row">Total</th><td><output data-suma="rubrica">25</output> / 30</td></tr>
              </tfoot>
            </table>
          </div>
          <p class="campo-ayuda">Cada opción dice el descriptor del nivel, así no hace falta otra tabla. Excelente 5, Bueno 4, Regular 3, Deficiente 1 o 2.</p>
          <div class="acciones"><button class="btn btn-primario" type="submit">Guardar rúbrica</button></div>
        </form>
      </div>

      <div class="marco escenario-libre">
        <form class="pila" action="#" onsubmit="return false">
          <p class="titulo-seccion">Autoevaluación y coevaluación <small class="campo-ayuda">Formulario 8</small></p>
          <div class="campo" style="max-width:24rem">
            <label for="f-co-evaluado">Compañero que evalúa <span class="requerido" aria-hidden="true">*</span></label>
            <div class="select">
              <select class="control" id="f-co-evaluado" name="evaluado_id" required aria-describedby="f-co-evaluado-ayuda">
                <option selected>José Rodríguez</option>
              </select>
            </div>
            <p class="campo-ayuda" id="f-co-evaluado-ayuda">Solo salen los miembros del proyecto. La autoevaluación es de usted.</p>
          </div>
          <div class="tabla-contenedor">
            <table class="tabla tabla-editable tabla-tarjetas">
              <caption class="solo-lector">Puntos de 1 a 5 por aspecto</caption>
              <thead><tr><th scope="col">Aspecto</th><th scope="col">Autoevaluación (1-5)</th><th scope="col">Coevaluación (1-5)</th><th scope="col">Comentarios</th></tr></thead>
              <tbody>
                <?php foreach (Catalogo::valores('aspecto_evaluacion') as $clave => $a): ?>
                  <?php $aspecto = mb_strtolower($a['texto']); $auto = [1 => 4, 5, 5, 3, 4, 3][$clave]; $co = [1 => 5, 4, 4, 4, 5, 4][$clave]; ?>
                  <tr>
                    <th scope="row"><?= Html::e($a['texto']) ?></th>
                    <td class="celda-corta" data-columna="Autoevaluación"><input class="control" type="number" min="1" max="5" inputmode="numeric" name="auto[<?= $clave ?>]" data-grupo="auto" aria-label="Autoevaluación de <?= Html::e($aspecto) ?>" value="<?= $auto ?>"></td>
                    <td class="celda-corta" data-columna="Coevaluación"><input class="control" type="number" min="1" max="5" inputmode="numeric" name="co[<?= $clave ?>]" data-grupo="co" aria-label="Coevaluación de <?= Html::e($aspecto) ?>" value="<?= $co ?>"></td>
                    <td data-columna="Comentarios"><textarea class="control" name="comentario[<?= $clave ?>]" aria-label="Comentarios de <?= Html::e($aspecto) ?>"></textarea></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr><th scope="row">Promedio</th><td data-columna="Autoevaluación"><output data-promedio="auto">4.0</output></td><td data-columna="Coevaluación"><output data-promedio="co">4.3</output></td><td></td></tr>
              </tfoot>
            </table>
          </div>
          <p class="campo-ayuda">Totales y promedios se ven al escribir; el servidor los vuelve a calcular al guardar.</p>
          <div class="acciones"><button class="btn btn-primario" type="submit">Guardar evaluación</button></div>
        </form>
      </div>

      <div class="marco escenario-libre">
        <form class="pila" action="#" onsubmit="return false">
          <p class="titulo-seccion">Portafolio de evidencias <small class="campo-ayuda">Formulario 9 · lo que entrega cada semana de la unidad</small></p>
          <div class="tabla-contenedor">
            <table class="tabla tabla-editable tabla-tarjetas">
              <caption class="solo-lector">Evidencias del aprendizaje por semana</caption>
              <thead>
                <tr><?php foreach ($colPortafolio as [$etiqueta]): ?><th scope="col"><?= Html::e($etiqueta) ?></th><?php endforeach; ?><th scope="col" class="celda-acciones"><span class="solo-lector">Acciones</span></th></tr>
              </thead>
              <tbody id="portafolio">
                <?= $fila($colPortafolio, ['semana' => 1, 'evidencia' => 'Glosario de términos', 'tipo' => 1, 'fecha' => '2026-09-12']) ?>
                <?= $fila($colPortafolio, ['semana' => 2, 'evidencia' => 'Taller de caja negra', 'tipo' => 2, 'fecha' => '2026-09-19']) ?>
                <?= $fila($colPortafolio, ['semana' => 3, 'evidencia' => 'Laboratorio de caja blanca', 'tipo' => 3, 'fecha' => '2026-09-26', 'observaciones' => 'Cobertura con PHPUnit.']) ?>
                <?= $fila($colPortafolio, ['semana' => 4, 'evidencia' => 'Plan de pruebas del proyecto', 'tipo' => 4, 'fecha' => '2026-10-03']) ?>
              </tbody>
            </table>
          </div>
          <template id="portafolio-fila"><?= $fila($colPortafolio) ?></template>
          <div class="acciones acciones-separadas">
            <button class="btn btn-secundario" type="button" data-agregar-fila="portafolio"><?= Icono::svg('plus') ?> Agregar fila</button>
            <button class="btn btn-primario" type="submit">Guardar portafolio</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <!-- ================= Tabla ================= -->
  <section class="seccion" id="lista">
    <h2>Lista: encabezado, filtros, tabla y paginación</h2>
    <p>Así se arma cada listado. Filtros por GET (RF-22), 20 filas por página (RNF-09), botones de ícono con aria-label. En el celular cada fila se vuelve una tarjeta.</p>
    <div class="marco escenario-libre">
    <header class="encabezado">
      <div>
        <h1 style="font-size:var(--letra-xl)">Casos de prueba</h1>
        <p>Todos los casos de sus proyectos, ordenados por código.</p>
      </div>
      <div class="acciones"><a class="btn btn-primario" href="#"><?= Icono::svg('plus') ?> Registrar caso</a></div>
    </header>
    <form class="filtros" method="get" action="#" role="search" aria-label="Filtrar casos">
      <div class="campo campo-busqueda">
        <label for="q">Buscar</label>
        <input class="control" id="q" name="q" type="search" placeholder="Código o descripción">
      </div>
      <div class="campo">
        <label for="fl-proyecto">Proyecto</label>
        <div class="select"><select class="control" id="fl-proyecto" name="proyecto"><option value="">Todos</option><option>Portal web</option><option>App de inventario</option></select></div>
      </div>
      <div class="campo">
        <label for="fl-req">Requerimiento</label>
        <div class="select"><select class="control" id="fl-req" name="requerimiento"><option value="">Todos</option><option>RF-01</option><option>RF-24</option></select></div>
      </div>
      <div class="campo">
        <label for="fl-estado">Estado</label>
        <div class="select"><select class="control" id="fl-estado" name="estado"><option value="">Todos</option><?= Catalogo::opciones('estado_caso') ?></select></div>
      </div>
      <div class="acciones">
        <button class="btn btn-secundario" type="submit">Filtrar</button>
        <a class="btn btn-fantasma" href="#">Limpiar</a>
      </div>
    </form>
    <div class="tabla-contenedor">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Casos de prueba del proyecto</caption>
        <thead>
          <tr><th scope="col">Código</th><th scope="col">Caso</th><th scope="col">Proyecto</th><th scope="col">Tipo</th><th scope="col">Estado</th><th scope="col"><span class="solo-lector">Acciones</span></th></tr>
        </thead>
        <tbody>
          <?php
          $filas = [
              ['SIS-001', 'Login con credenciales válidas', 3, 1, 'Portal web'],
              ['SIS-002', 'Login con contraseña vacía', 3, 2, 'Portal web'],
              ['UNI-126274-01', 'Edad en el valor límite 18', 1, 0, 'Portal web'],
              ['INT-126280-01', 'Subir evidencia PDF de 5 MB', 2, 0, 'App de inventario'],
          ];
          foreach ($filas as [$codigo, $caso, $tipo, $estado, $proyecto]): ?>
            <tr>
              <td data-columna="Código"><a class="codigo" href="#"><?= Html::e($codigo) ?></a></td>
              <td data-columna="Caso" class="celda-larga"><?= Html::e($caso) ?></td>
              <td data-columna="Proyecto"><?= Html::e($proyecto) ?></td>
              <td data-columna="Tipo"><?= Html::e(Catalogo::texto('tipo_prueba', $tipo)) ?></td>
              <td data-columna="Estado"><?= Catalogo::insignia('estado_caso', $estado) ?></td>
              <td class="celda-acciones">
                <div class="acciones">
                  <a class="btn btn-fantasma btn-icono" href="#" aria-label="Editar <?= Html::e($codigo) ?>" data-tooltip="Editar"><?= Icono::svg('pencil') ?></a>
                  <button class="btn btn-fantasma btn-icono" type="button" data-abrir-dialogo="dlg-eliminar" aria-label="Eliminar <?= Html::e($codigo) ?>" data-tooltip="Eliminar" data-tooltip-alinear="fin"><?= Icono::svg('trash-2') ?></button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <nav class="paginacion" aria-label="Páginas">
      <span>Mostrando 1–20 de 57</span>
      <ul>
        <li><span aria-disabled="true"><?= Icono::svg('chevron-left') ?><span class="solo-lector">Anterior</span></span></li>
        <li><a href="#" aria-current="page">1</a></li>
        <li><a href="#">2</a></li>
        <li><a href="#">3</a></li>
        <li><a href="#"><?= Icono::svg('chevron-right') ?><span class="solo-lector">Siguiente</span></a></li>
      </ul>
    </nav>
    </div>
  </section>

  <!-- ================= Historial ================= -->
  <section class="seccion" id="historial">
    <h2>Historial de cambios</h2>
    <p>Lo que guarda logs_cambios (RF-20). El valor anterior va tachado y cada valor en su columna, no solo con color.</p>
    <div class="tabla-contenedor">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Cambios de SIS-002</caption>
        <thead><tr><th scope="col">Fecha</th><th scope="col">Usuario</th><th scope="col">Campo</th><th scope="col">Antes</th><th scope="col">Después</th></tr></thead>
        <tbody>
          <?php
          $cambios = [
              ['30/09/2026 14:32', 'mperez', 'Estado', 'Pendiente', 'FAULT'],
              ['30/09/2026 10:05', 'admin', 'Resultado esperado', 'No inicia sesión.', 'No inicia sesión y muestra "Usuario o contraseña incorrectos".'],
              ['29/09/2026 16:48', 'mperez', 'Tipo de prueba', 'Unitaria', 'Sistema'],
          ];
          foreach ($cambios as [$fecha, $usuario, $campo, $antes, $despues]): ?>
            <tr>
              <td data-columna="Fecha"><?= Html::e($fecha) ?></td>
              <td data-columna="Usuario"><?= Html::e($usuario) ?></td>
              <td data-columna="Campo"><?= Html::e($campo) ?></td>
              <td data-columna="Antes" class="celda-larga"><del class="cambio"><?= Html::e($antes) ?></del></td>
              <td data-columna="Después" class="celda-larga"><ins class="cambio"><?= Html::e($despues) ?></ins></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- ================= Panel ================= -->
  <section class="seccion" id="panel">
    <h2>Panel y reportes</h2>
    <p>Cifras, barras de avance y tarjetas con encabezado, contenido y pie. Para el dashboard y el reporte de cierre (RF-23).</p>
    <div class="pila">
      <div class="cifras">
        <?php
        $cifrasDemo = [
            ['Casos', 'clipboard-list', '57', '12 proyectos'],
            ['OK', 'circle-check', '32', '56 % del total'],
            ['FAULT', 'circle-x', '9', '6 con incidente'],
            ['Pendientes', 'circle-alert', '16', 'Sin resultado todavía'],
        ];
        foreach ($cifrasDemo as [$etiqueta, $icono, $valor, $nota]): ?>
          <div class="tarjeta cifra">
            <p class="cifra-etiqueta"><?= Html::e($etiqueta) ?> <?= Icono::svg($icono) ?></p>
            <p class="cifra-valor"><?= Html::e($valor) ?></p>
            <p class="cifra-nota"><?= Html::e($nota) ?></p>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="rejilla">
        <section class="tarjeta" aria-labelledby="t-avance">
          <header class="tarjeta-encabezado">
            <h3 class="tarjeta-titulo" id="t-avance">Avance del plan de pruebas v1</h3>
            <p class="tarjeta-descripcion">Portal web · 57 casos</p>
            <div class="acciones"><a class="btn btn-secundario btn-sm" href="#">Ver reporte</a></div>
          </header>
          <div class="tarjeta-contenido pila">
            <?php
            $avance = [['OK', 32, 'progreso-exito'], ['FAULT', 9, 'progreso-peligro'], ['Pendiente', 16, 'progreso-aviso']];
            foreach ($avance as $i => [$estado, $cantidad, $clase]): ?>
              <div>
                <div class="progreso-fila"><label for="av-<?= $i ?>"><?= Html::e($estado) ?></label><span><?= $cantidad ?> · <?= round($cantidad / 57 * 100) ?> %</span></div>
                <progress class="progreso <?= $clase ?>" id="av-<?= $i ?>" max="57" value="<?= $cantidad ?>"><?= $cantidad ?> de 57</progress>
              </div>
            <?php endforeach; ?>
          </div>
          <footer class="tarjeta-pie" style="justify-content:space-between">
            <small class="campo-ayuda">Actualizado el 30/09/2026 14:32</small>
            <button class="btn btn-secundario btn-sm" type="button" data-imprimir><?= Icono::svg('printer') ?> Imprimir</button>
          </footer>
        </section>

        <form class="tarjeta" action="#" onsubmit="return false" aria-labelledby="t-plan">
          <header class="tarjeta-encabezado">
            <h3 class="tarjeta-titulo" id="t-plan">Plan de pruebas del proyecto</h3>
            <p class="tarjeta-descripcion">Formulario 6 · uno por proyecto</p>
          </header>
          <div class="tarjeta-contenido pila">
            <div class="campos">
              <div class="campo">
                <label for="pl-version">Versión <span class="requerido" aria-hidden="true">*</span></label>
                <input class="control" id="pl-version" name="version" required value="1.0">
              </div>
              <div class="campo">
                <label for="pl-fecha">Fecha <span class="requerido" aria-hidden="true">*</span></label>
                <input class="control" id="pl-fecha" name="fecha" type="date" required value="2026-09-30">
              </div>
            </div>
            <div class="campo">
              <label for="pl-alcance">Alcance <span class="requerido" aria-hidden="true">*</span></label>
              <textarea class="control" id="pl-alcance" name="alcance" required aria-describedby="pl-alcance-ayuda"></textarea>
              <p class="campo-ayuda" id="pl-alcance-ayuda">Qué se va a probar y qué no.</p>
            </div>
          </div>
          <footer class="tarjeta-pie">
            <button class="btn btn-secundario" type="button">Cancelar</button>
            <button class="btn btn-primario" type="submit">Guardar plan</button>
          </footer>
        </form>
      </div>
    </div>
  </section>

  <!-- ================= Acceso ================= -->
  <section class="seccion" id="acceso">
    <h2>Pantalla de acceso</h2>
    <p>Login y registro: tarjeta centrada sobre fondo tenue. Error genérico que no dice si el usuario existe (RNF-02).</p>
    <div class="marco">
      <div class="acceso" style="min-height:auto">
        <img class="acceso-logo" src="/Frame.svg" alt="Safeguard">
        <form class="tarjeta pila" action="#" onsubmit="return false" aria-labelledby="ac-titulo">
          <header class="tarjeta-encabezado" style="margin:0">
            <h3 class="tarjeta-titulo" id="ac-titulo">Iniciar sesión</h3>
            <p class="tarjeta-descripcion">Entre con su usuario y contraseña.</p>
          </header>
          <div class="alerta alerta-error" role="alert"><?= Icono::svg('circle-alert') ?>
            <p class="alerta-titulo">Usuario o contraseña incorrectos.</p>
          </div>
          <div class="campo">
            <label for="ac-usuario">Usuario</label>
            <input class="control" id="ac-usuario" name="usuario" autocomplete="username" required value="demo">
          </div>
          <div class="campo">
            <label for="ac-clave">Contraseña</label>
            <input class="control" id="ac-clave" name="clave" type="password" autocomplete="current-password" required>
          </div>
          <button class="btn btn-primario btn-bloque" type="submit">Iniciar sesión</button>
        </form>
        <p class="acceso-pie">¿No tiene cuenta? <a href="#">Crear cuenta</a></p>
      </div>
    </div>
  </section>

  <!-- ================= Casillas ================= -->
  <section class="seccion" id="miembros">
    <h2>Lista de casillas</h2>
    <p>Para elegir varios de una lista larga, como los miembros de un proyecto. Casillas nativas en una caja con scroll; sin combobox.</p>
    <div class="marco escenario-libre">
      <form class="pila" action="#" onsubmit="return false" style="max-width:28rem">
        <fieldset class="grupo">
          <legend>Miembros de Portal web</legend>
          <div class="lista-opciones">
            <?php
            $personas = [
                ['María Pérez', 'mperez', true], ['José Rodríguez', 'jrodriguez', true], ['Ana Núñez', 'anunez', false],
                ['Luis Gómez', 'lgomez', false], ['Carmen Díaz', 'cdiaz', true], ['Pedro Santana', 'psantana', false],
                ['Rosa Martínez', 'rmartinez', false], ['Juan Castillo', 'jcastillo', false],
            ];
            foreach ($personas as [$nombre, $usuario, $marcado]): ?>
              <label class="opcion"><input type="checkbox" name="miembros[]" value="<?= Html::e($usuario) ?>"<?= $marcado ? ' checked' : '' ?>> <?= Html::e($nombre) ?> <small><?= Html::e($usuario) ?></small></label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <div class="acciones"><button class="btn btn-primario" type="submit">Guardar miembros</button></div>
      </form>
    </div>
  </section>

  <!-- ================= Detalle ================= -->
  <section class="seccion" id="detalle">
    <h2>Detalle del caso</h2>
    <p>La página que más se abre, estilo Jira: contenido a la izquierda y datos cortos a la derecha. Es también la vista formal del formulario 1: al imprimir sale en una columna, en claro y sin botones. Un botón sin permiso no se muestra (RF-21).</p>
    <div class="marco escenario-libre">
      <header class="encabezado">
        <div>
          <p class="antetitulo fila"><span class="codigo">SIS-002</span> <span class="insignia insignia-peligro">FAULT</span></p>
          <h1 style="font-size:var(--letra-xl)">Login con contraseña vacía</h1>
        </div>
        <div class="acciones">
          <a class="btn btn-secundario" href="#"><?= Icono::svg('pencil') ?> Editar</a>
          <a class="btn btn-secundario" href="#"><?= Icono::svg('bug') ?> Registrar incidente</a>
          <button class="btn btn-secundario" type="button" data-imprimir><?= Icono::svg('printer') ?> Imprimir</button>
        </div>
      </header>

      <div class="vista-detalle">
        <div class="pila">
          <section class="tarjeta" aria-labelledby="dt-prueba">
            <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="dt-prueba">Prueba</h2></header>
            <dl class="detalle">
              <dt>Objetivo</dt><dd>Verificar que no se inicia sesión con contraseña vacía.</dd>
              <dt>Precondiciones</dt><dd>Existe el usuario demo.</dd>
              <dt>Datos de entrada</dt><dd class="multilinea">Usuario: demo
Contraseña: (vacía)</dd>
              <dt>Pasos</dt><dd class="multilinea">1. Abrir la página de inicio de sesión.
2. Escribir el usuario y dejar la contraseña vacía.
3. Pulsar "Iniciar sesión".</dd>
              <dt>Resultado esperado</dt><dd>No inicia sesión y muestra "Usuario o contraseña incorrectos".</dd>
            </dl>
          </section>

          <section class="tarjeta" aria-labelledby="dt-resultado">
            <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="dt-resultado">Resultado</h2></header>
            <dl class="detalle">
              <dt>Resultado obtenido</dt><dd>Inicia sesión.</dd>
              <dt>Observaciones</dt><dd>Pasa también con espacios en la contraseña.</dd>
            </dl>
            <h3 class="titulo-seccion" style="margin:20px 0 8px;font-size:var(--letra-sm)">Evidencia</h3>
            <ul class="archivos">
              <?php
              $evidencias = [
                  ['image', 'SIS-002_1.png', 'PNG · 240 KB · 30/09/2026 14:20', 'Pantalla de login con el mensaje de error'],
                  ['file-text', 'SIS-002_2.log', 'LOG · 3 KB · 30/09/2026 14:21', 'Registro del servidor durante el intento'],
              ];
              foreach ($evidencias as [$icono, $archivo, $detalle, $descripcion]): ?>
                <li class="archivo">
                  <span class="archivo-icono"><?= Icono::svg($icono) ?></span>
                  <p class="archivo-nombre"><?= Html::e($descripcion) ?></p>
                  <p class="archivo-detalle"><span class="codigo"><?= Html::e($archivo) ?></span> · <?= Html::e($detalle) ?></p>
                  <div class="acciones">
                    <a class="btn btn-fantasma btn-icono" href="#" aria-label="Descargar <?= Html::e($archivo) ?>" data-tooltip="Descargar"><?= Icono::svg('download') ?></a>
                    <button class="btn btn-fantasma btn-icono" type="button" aria-label="Eliminar <?= Html::e($archivo) ?>" data-tooltip="Eliminar" data-tooltip-alinear="fin"><?= Icono::svg('trash-2') ?></button>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        </div>

        <aside class="tarjeta" aria-label="Datos del caso">
          <dl class="detalle detalle-apilado">
            <dt>Estado</dt><dd><span class="insignia insignia-peligro">FAULT</span></dd>
            <dt>Proyecto</dt><dd>Portal web</dd>
            <dt>Requerimiento</dt><dd>RF-01 · Iniciar sesión</dd>
            <dt>Tipo de prueba</dt><dd>Sistema</dd>
            <dt>Módulo</dt><dd>Inicio de sesión</dd>
            <dt>Plataforma</dt><dd>Web · Windows 11, Chrome 129</dd>
            <dt>Técnica</dt><dd>Caja negra · Partición de equivalencia</dd>
            <dt>Fechas</dt><dd>30/09/2026 al 03/10/2026</dd>
            <dt>Resultado anotado por</dt><dd>mperez · 30/09/2026 14:32</dd>
            <dt>Creado por</dt><dd>admin · 29/09/2026 16:40</dd>
            <dt>Incidentes</dt><dd><a class="codigo" href="#">BUG-001</a> <span class="insignia insignia-aviso">Abierto</span></dd>
          </dl>
        </aside>
      </div>
      <p class="solo-impresion campo-ayuda" style="margin-top:24px">Impreso el 30/09/2026 desde Casos de Prueba.</p>
    </div>
  </section>

  <!-- ================= Tooltips ================= -->
  <section class="seccion" id="tooltips">
    <h2>Tooltips</h2>
    <p>Con el mouse o con Tab. Se puede pasar el mouse encima y se cierra con Escape. Solo repiten lo que ya dice el aria-label.</p>
    <div class="rejilla">
      <div class="marco">
        <div class="escenario">
          <div class="fila">
            <button class="btn btn-secundario btn-icono" type="button" aria-label="Ver caso" data-tooltip="Ver caso"><?= Icono::svg('file-text') ?></button>
            <button class="btn btn-secundario btn-icono" type="button" aria-label="Editar caso" data-tooltip="Editar"><?= Icono::svg('pencil') ?></button>
            <button class="btn btn-secundario btn-icono" type="button" aria-label="Subir evidencia" data-tooltip="Subir evidencia"><?= Icono::svg('upload') ?></button>
            <button class="btn btn-secundario btn-icono" type="button" aria-label="Eliminar caso" data-tooltip="Eliminar" data-tooltip-lado="abajo"><?= Icono::svg('trash-2') ?></button>
          </div>
        </div>
        <p class="etiqueta">Botones de ícono · arriba y abajo</p>
      </div>
      <div class="marco">
        <div class="escenario">
          <p style="margin:0;font-size:var(--letra-sm)">
            Tipo <abbr tabindex="0" data-tooltip="Sistema: prueba el software completo">SIS</abbr>,
            creado el <span tabindex="0" data-tooltip="30/09/2026 a las 14:20" style="text-decoration:underline dotted;text-underline-offset:3px">30/09/2026</span>
          </p>
        </div>
        <p class="etiqueta">Texto con explicación · abbr y span con tabindex</p>
      </div>
    </div>
  </section>

  <!-- ================= Diálogo ================= -->
  <section class="seccion" id="dialogo">
    <h2>Diálogo de confirmación</h2>
    <p>&lt;dialog&gt; nativo: atrapa el foco y se cierra con Escape sin código extra (RNF-04). También se abre desde el ícono de eliminar de la tabla.</p>
    <div class="marco escenario">
      <button class="btn btn-peligro" type="button" data-abrir-dialogo="dlg-eliminar"><?= Icono::svg('trash-2') ?> Eliminar caso</button>
    </div>
    <dialog class="dialogo" id="dlg-eliminar" aria-labelledby="dlg-eliminar-titulo">
      <h2 id="dlg-eliminar-titulo">¿Eliminar este caso de prueba?</h2>
      <p>Su historial y sus evidencias se borran con él. Esta acción no se puede deshacer.</p>
      <form method="dialog" class="acciones">
        <button class="btn btn-secundario" value="cancelar" autofocus>Cancelar</button>
        <button class="btn btn-peligro" value="eliminar">Eliminar</button>
      </form>
    </dialog>
  </section>

  <!-- ================= Alertas ================= -->
  <section class="seccion" id="alertas">
    <h2>Alertas</h2>
    <p>Resultado de una acción. Normal, de éxito (datos guardados) o de error; ícono, título, descripción y acción son opcionales.</p>
    <div class="rejilla">
      <div class="marco escenario-libre pila">
        <div class="alerta alerta-exito" role="status"><?= Icono::svg('circle-check') ?>
          <p class="alerta-titulo">Resultado guardado.</p>
          <div class="alerta-descripcion">Se guardaron el resultado y 2 evidencias de <span class="codigo">SIS-002</span>.</div>
        </div>
        <div class="alerta alerta-exito" role="status"><?= Icono::svg('circle-check') ?>
          <p class="alerta-titulo">Cambios guardados.</p>
        </div>
      </div>
      <div class="marco escenario-libre pila">
        <div class="alerta" role="status"><p class="alerta-titulo">Caso guardado.</p></div>
        <div class="alerta" role="status">
          <p class="alerta-titulo">Caso guardado.</p>
          <div class="alerta-descripcion"><span class="codigo">SIS-003</span> se agregó al proyecto.</div>
        </div>
        <div class="alerta" role="status"><div class="alerta-descripcion">Esta solo tiene descripción. Sin título ni ícono.</div></div>
      </div>
      <div class="marco escenario-libre pila">
        <div class="alerta" role="status"><?= Icono::svg('circle-check') ?>
          <p class="alerta-titulo">Caso guardado.</p>
          <div class="alerta-descripcion"><span class="codigo">SIS-003</span> se agregó al proyecto. <a href="#">Ver caso</a>.</div>
        </div>
        <div class="alerta" role="status"><?= Icono::svg('circle-alert') ?>
          <div class="alerta-descripcion">Quedan 12 casos pendientes en este plan. <a href="#">Ver pendientes</a>.</div>
        </div>
        <div class="alerta" role="status"><?= Icono::svg('circle-alert') ?>
          <p class="alerta-titulo">3 casos con FAULT no tienen evidencia y el plan no se puede cerrar hasta que cada uno tenga al menos una</p>
        </div>
      </div>
      <div class="marco escenario-libre pila">
        <div class="alerta alerta-error" role="alert"><?= Icono::svg('circle-alert') ?>
          <p class="alerta-titulo">No se pudo guardar la evidencia.</p>
          <div class="alerta-descripcion">Intente de nuevo. Si sigue fallando, reporte el código <code>E-1042</code>.</div>
        </div>
        <div class="alerta alerta-error" role="alert"><?= Icono::svg('circle-alert') ?>
          <p class="alerta-titulo">El caso no se guardó.</p>
          <div class="alerta-descripcion">
            <p>Revise estos campos y vuelva a intentarlo:</p>
            <ul>
              <li>Descripción es obligatoria</li>
              <li>Seleccione el requerimiento que valida el caso</li>
            </ul>
          </div>
        </div>
      </div>
      <div class="marco escenario-libre pila">
        <div class="alerta" role="status"><?= Icono::svg('circle-alert') ?>
          <p class="alerta-titulo">Caso marcado como FAULT.</p>
          <div class="alerta-descripcion"><span class="codigo">SIS-002</span> no dio el resultado esperado.</div>
          <div class="alerta-accion"><a class="btn btn-secundario btn-sm" href="#">Registrar incidente</a></div>
        </div>
        <div class="alerta" role="status"><?= Icono::svg('circle-alert') ?>
          <p class="alerta-titulo">Plan de pruebas v1 cerrado.</p>
          <div class="alerta-descripcion">Ya no se pueden cambiar los resultados de este plan.</div>
          <div class="alerta-accion"><span class="insignia insignia-secundaria">Cerrado</span></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ================= Estado vacío ================= -->
  <section class="seccion" id="vacio">
    <h2>Estado vacío</h2>
    <p>Lo que ve una lista sin datos: qué pasa y cuál es el siguiente paso. Las páginas de error usan lo mismo, con el título como h1.</p>
    <div class="marco escenario-libre">
      <div class="vacio">
        <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
        <h3>Todavía no hay casos de prueba</h3>
        <p>Registre el primer caso de este proyecto para empezar a darle seguimiento.</p>
        <a class="btn btn-primario" href="#"><?= Icono::svg('plus') ?> Registrar caso</a>
      </div>
    </div>
    <div class="rejilla" style="margin-top:12px">
      <div class="marco">
        <div class="escenario-libre">
          <div class="vacio">
            <span class="vacio-icono"><?= Icono::svg('lock') ?></span>
            <h3>No tiene permiso para ver esta página</h3>
            <p>Pida acceso al administrador del proyecto.</p>
            <a class="btn btn-secundario" href="#">Volver al panel</a>
          </div>
        </div>
        <p class="etiqueta">403 · RF-21, RNF-02</p>
      </div>
      <div class="marco">
        <div class="escenario-libre">
          <div class="vacio">
            <span class="vacio-icono"><?= Icono::svg('file-question-mark') ?></span>
            <h3>No encontramos esta página</h3>
            <p>Puede que el caso se haya eliminado o que la dirección esté mal.</p>
            <a class="btn btn-secundario" href="#">Volver al panel</a>
          </div>
        </div>
        <p class="etiqueta">404</p>
      </div>
    </div>
  </section>

  <!-- ================= Radios ================= -->
  <section class="seccion" id="radios">
    <h2>Escala de radios</h2>
    <p>Todos los pasos salen de --radio (10px), de la esquina más cerrada a la más redonda.</p>
    <div class="rejilla rejilla-4">
      <?php foreach (['sm' => 6, 'md' => 8, 'lg' => 10, 'xl' => 14, '2xl' => 18, '3xl' => 22, '4xl' => 26] as $paso => $px): ?>
        <div class="marco"><div class="esquina"><div style="border-top-left-radius:var(--radio-<?= Html::e($paso) ?>)"></div></div><p class="etiqueta">--radio-<?= Html::e($paso) ?> · <?= Html::e((string) $px) ?>px</p></div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ================= Sombras ================= -->
  <section class="seccion" id="sombras">
    <h2>Sombras en contexto</h2>
    <p>Cada superficie con el paso de sombra que le toca.</p>
    <div class="rejilla">
      <div class="marco">
        <div class="escenario"><button class="btn btn-secundario" type="button"><?= Icono::svg('printer') ?> Imprimir</button></div>
        <p class="etiqueta">Botón de barra · --sombra-xs</p>
      </div>
      <div class="marco">
        <div class="escenario">
          <div class="tarjeta" style="width:100%;box-shadow:var(--sombra-sm)">
            <div class="tarjeta-encabezado">
              <h3 class="tarjeta-titulo">Plan de pruebas v1</h3>
              <p class="tarjeta-descripcion">Actualizado hace 2 horas</p>
            </div>
          </div>
        </div>
        <p class="etiqueta">Tarjeta · --sombra-sm</p>
      </div>
      <div class="marco">
        <div class="escenario">
          <ul class="menu-demo">
            <li>Ver evidencias</li>
            <li class="activo">Marcar como aprobado</li>
            <li>Registrar incidente</li>
          </ul>
        </div>
        <p class="etiqueta">Menú · --sombra-md</p>
      </div>
      <div class="marco">
        <div class="escenario">
          <div class="flotante-demo">
            <strong>Invitar a un tester</strong>
            <p>Solo tendrá acceso a este proyecto.</p>
            <div class="fila">
              <span class="insignia insignia-secundaria">puede editar</span>
              <span class="insignia insignia-borde">solo ver</span>
            </div>
          </div>
        </div>
        <p class="etiqueta">Flotante · --sombra-md</p>
      </div>
      <div class="marco">
        <div class="escenario"><span class="globo-demo">Ver el historial del caso</span></div>
        <p class="etiqueta">Globo de ayuda · --sombra-md</p>
      </div>
      <div class="marco">
        <div class="escena-velo">
          <table class="escena-fondo" aria-hidden="true">
            <?php foreach ($filas as [$codigo, $caso, , $estado]): ?>
              <tr><td class="codigo"><?= Html::e($codigo) ?></td><td><?= Html::e($caso) ?></td><td><?= Catalogo::insignia('estado_caso', $estado) ?></td></tr>
            <?php endforeach; ?>
          </table>
          <div class="velo escena-capa"></div>
          <div class="dialogo dialogo-demo">
            <h2>¿Eliminar este caso de prueba?</h2>
            <p>Su historial y sus evidencias se borran con él.</p>
            <div class="acciones">
              <button class="btn btn-secundario" type="button">Cancelar</button>
              <button class="btn btn-peligro" type="button">Eliminar</button>
            </div>
          </div>
        </div>
        <p class="etiqueta">Diálogo sobre el velo · --sombra-xl + --velo</p>
      </div>
    </div>
  </section>

  <!-- ================= Radios en contexto ================= -->
  <section class="seccion" id="insignias">
    <h2>Botones, entrada e insignias</h2>
    <p>Los componentes que redondea la escala, cada uno con el paso que usa.</p>
    <div class="rejilla">
      <div class="marco">
        <div class="escenario"><div class="fila"><button class="btn btn-primario" type="button">Guardar caso</button><button class="btn btn-secundario" type="button">Cancelar</button></div></div>
        <p class="etiqueta">Botón · --radio-md</p>
      </div>
      <div class="marco">
        <div class="escenario">
          <label style="width:100%"><span class="solo-lector">Buscar casos de prueba</span><input class="control" type="search" placeholder="Buscar casos de prueba"></label>
        </div>
        <p class="etiqueta">Entrada · --radio-md</p>
      </div>
      <div class="marco">
        <div class="escenario">
          <div class="fila">
            <span class="insignia insignia-destacado">Nuevo</span>
            <span class="insignia insignia-exito">OK</span>
            <span class="insignia insignia-aviso">Pendiente</span>
            <span class="insignia insignia-peligro">FAULT</span>
            <span class="insignia insignia-info">En progreso</span>
            <span class="insignia insignia-borde">Archivado</span>
          </div>
        </div>
        <p class="etiqueta">Insignia · --radio-md</p>
      </div>
    </div>

    <p style="margin:24px 0 12px;font-size:var(--letra-sm);color:var(--tenue-texto)">Qué variante lleva cada valor. Sale de config/catalogos.php: para cambiar un texto o un color, o agregar un valor, se edita ese archivo y cambia en toda la app. Con el mouse encima se ve el número que se guarda en la base.</p>
    <div class="tabla-contenedor">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Variante de insignia por dato</caption>
        <thead><tr><th scope="col">Dato</th><th scope="col">Valores</th></tr></thead>
        <tbody>
          <?php foreach (Catalogo::todos() as $catalogo => $datos): ?>
            <tr>
              <th scope="row"><?= Html::e($datos['nombre']) ?> <small class="codigo campo-ayuda"><?= Html::e($catalogo) ?></small></th>
              <td data-columna="Valores" style="white-space:normal">
                <span class="fila">
                  <?php foreach ($datos['valores'] as $clave => $valor): ?>
                    <span class="insignia insignia-<?= Html::e($valor['variante']) ?>" data-tooltip="<?= Html::e($clave . ' · ' . $valor['variante']) ?>" tabindex="0"><?= Html::e($valor['texto']) ?></span>
                  <?php endforeach; ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- ================= Tipografía ================= -->
  <section class="seccion" id="tipografia">
    <h2>Tipografía</h2>
    <p>Inter. Texto normal fijo en rem; títulos fluidos.</p>
    <div class="marco tipografia">
      <h1>h1 · Plan de pruebas 2026</h1>
      <h2>h2 · Registro de caso de prueba</h2>
      <h3>h3 · Técnica de caja negra</h3>
      <h4>h4 · Análisis de valor límite</h4>
      <p>Texto base: el sistema registra cada caso, su resultado y la evidencia. Números 0123456789, <em>cursiva</em> y <strong>negrita</strong>. <a href="#">Un enlace</a>.</p>
      <p><small>Texto pequeño: creado por el administrador el 30/09/2026.</small></p>
    </div>
  </section>
  <!-- ================= Abrir en el celular ================= -->
  <?php
  // IPs IPv4 de esta compu en la red (sin 127.x). 2 = AF_INET: la constante
  // no existe si PHP no tiene la extensión sockets.
  $ipsRed = [];
  foreach (net_get_interfaces() ?: [] as $interfaz) {
      foreach ($interfaz['unicast'] ?? [] as $direccion) {
          if (($direccion['family'] ?? 0) === 2 && !str_starts_with($direccion['address'], '127.')) {
              $ipsRed[] = $direccion['address'];
          }
      }
  }
  $ipsRed = array_values(array_unique($ipsRed));

  // Con "php -S 127.0.0.1:8000" el celular no puede entrar: hay que usar 0.0.0.0.
  $soloEstaCompu = in_array($_SERVER['SERVER_NAME'] ?? '', ['127.0.0.1', 'localhost', '::1'], true);
  $puerto = $_SERVER['SERVER_PORT'] ?? '8000';
  $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/paleta.php';
  $urlRed = $ipsRed !== [] ? "http://{$ipsRed[0]}:{$puerto}{$ruta}" : null;

  // El QR lo dibuja qrencode (herramienta del sistema). Si no está instalado, se muestra solo la dirección.
  $qr = null;
  if ($urlRed !== null && !$soloEstaCompu && function_exists('shell_exec')) {
      $svg = shell_exec('command -v qrencode >/dev/null && qrencode -t SVG -m 2 -o - ' . escapeshellarg($urlRed) . ' 2>/dev/null');
      if (is_string($svg) && ($inicio = strpos($svg, '<svg')) !== false) {
          $qr = preg_replace(
              '/ width="[^"]*" height="[^"]*"/',
              ' class="qr" role="img" aria-label="Código QR para abrir esta página en el celular"',
              substr($svg, $inicio),
              1
          );
      }
  }
  ?>
  <section class="seccion" id="celular">
    <h2>Celular</h2>
    <div class="marco escenario">
      <?php if ($qr !== null): ?>
        <div class="qr-caja"><?= $qr /* SVG generado por qrencode con una dirección armada aquí */ ?></div>
      <?php elseif ($soloEstaCompu): ?>
        <code>php -S 0.0.0.0:<?= Html::e($puerto) ?> -t public</code>
      <?php elseif ($urlRed !== null): ?>
        <a class="codigo" href="<?= Html::e($urlRed) ?>"><?= Html::e($urlRed) ?></a>
      <?php else: ?>
        <p style="margin:0">Sin red</p>
      <?php endif; ?>
    </div>
  </section>
</main>
</body>
</html>
