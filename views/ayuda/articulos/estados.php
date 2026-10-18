<svg class="diagrama" viewBox="0 0 580 170" width="580" height="170" role="img" aria-labelledby="diagrama-titulo" fill="none" stroke="currentColor" stroke-width="1.5" font-family="inherit" font-size="14">
  <title id="diagrama-titulo">Diagrama que muestra un caso Pendiente que pasa a OK o a FAULT, y un FAULT que abre un incidente.</title>
  <defs><marker id="flecha" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto"><path d="M0 0 10 5 0 10z" fill="currentColor" stroke="none"/></marker></defs>
  <rect x="10" y="65" width="120" height="40" rx="8"/>
  <rect x="230" y="10" width="120" height="40" rx="8"/>
  <rect x="230" y="120" width="120" height="40" rx="8"/>
  <rect x="440" y="120" width="130" height="40" rx="8"/>
  <path d="M130 85 228 32" marker-end="url(#flecha)"/>
  <path d="M130 85 228 138" marker-end="url(#flecha)"/>
  <path d="M350 140h88" marker-end="url(#flecha)"/>
  <path d="M505 120V30H352" stroke-dasharray="5 4" marker-end="url(#flecha)"/>
  <g fill="currentColor" stroke="none" text-anchor="middle">
    <text x="70" y="90">Pendiente</text>
    <text x="290" y="35">OK</text>
    <text x="290" y="145">FAULT</text>
    <text x="505" y="145">Incidente</text>
    <text x="440" y="22" font-size="12">Incidente cerrado: repetir</text>
  </g>
</svg>

<h2>Caso</h2>
<div class="tabla-contenedor">
  <table class="tabla">
    <thead><tr><th scope="col">Estado</th><th scope="col">Significa</th></tr></thead>
    <tbody>
      <tr><td>Pendiente</td><td>Sin resultado todavía.</td></tr>
      <tr><td>OK</td><td>Pasó la prueba. No se permite con incidentes sin cerrar.</td></tr>
      <tr><td>FAULT</td><td>Falló la prueba. Registre un incidente.</td></tr>
    </tbody>
  </table>
</div>

<h2>Incidente</h2>
<div class="tabla-contenedor">
  <table class="tabla">
    <thead><tr><th scope="col">Estado</th><th scope="col">Significa</th></tr></thead>
    <tbody>
      <tr><td>Abierto</td><td>Registrado, sin trabajo todavía.</td></tr>
      <tr><td>En progreso</td><td>Alguien lo corrige.</td></tr>
      <tr><td>Cerrado</td><td>Corregido. Escriba el commit corregido.</td></tr>
    </tbody>
  </table>
</div>

<h2>Plan de pruebas</h2>
<div class="tabla-contenedor">
  <table class="tabla">
    <thead><tr><th scope="col">Estado</th><th scope="col">Significa</th></tr></thead>
    <tbody>
      <tr><td>Borrador</td><td>En preparación.</td></tr>
      <tr><td>Aprobado</td><td>Listo para ejecutar.</td></tr>
      <tr><td>Cerrado</td><td>Terminado. No se permite con stoppers sin cerrar.</td></tr>
    </tbody>
  </table>
</div>
