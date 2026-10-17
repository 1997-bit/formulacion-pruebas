// Mostrar u ocultar la contraseña, patrón GOV.UK Password input. Ojo dentro del campo. Sin JS el campo queda igual.
// Íconos: lucide-static eye y eye-off, ISC.
const svg = (trazos) => `<svg class="icono" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${trazos}</svg>`;
const ojo = svg('<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>');
const ojoTachado = svg('<path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/>');

document.querySelectorAll('input[type="password"]').forEach((campo) => {
  const boton = document.createElement('button');
  boton.type = 'button';
  boton.className = 'btn btn-fantasma btn-icono control-boton';
  boton.setAttribute('aria-controls', campo.id);
  const estado = document.createElement('span');
  estado.className = 'solo-lector';
  estado.setAttribute('aria-live', 'polite');
  const pintar = (visible) => {
    const texto = visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
    boton.innerHTML = visible ? ojoTachado : ojo;
    boton.setAttribute('aria-label', texto);
    boton.dataset.tooltip = texto;
  };
  boton.addEventListener('click', () => {
    const visible = campo.type === 'password';
    campo.type = visible ? 'text' : 'password';
    pintar(visible);
    estado.textContent = visible ? 'La contraseña se ve.' : 'La contraseña está oculta.';
  });
  // Al enviar vuelve a oculta: el navegador no la guarda como texto.
  campo.form?.addEventListener('submit', () => { campo.type = 'password'; pintar(false); });
  pintar(false);
  const fila = document.createElement('div');
  fila.className = 'control-con-boton';
  campo.replaceWith(fila);
  fila.append(campo, boton, estado);
});
