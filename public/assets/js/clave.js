// Mostrar u ocultar la contraseña, patrón GOV.UK Password input. Sin JS el campo queda igual.
document.querySelectorAll('input[type="password"]').forEach((campo) => {
  const boton = document.createElement('button');
  boton.type = 'button';
  boton.className = 'btn btn-secundario';
  boton.textContent = 'Mostrar';
  boton.setAttribute('aria-controls', campo.id);
  boton.setAttribute('aria-label', 'Mostrar contraseña');
  const estado = document.createElement('span');
  estado.className = 'solo-lector';
  estado.setAttribute('aria-live', 'polite');
  boton.addEventListener('click', () => {
    const ver = campo.type === 'password';
    campo.type = ver ? 'text' : 'password';
    boton.textContent = ver ? 'Ocultar' : 'Mostrar';
    boton.setAttribute('aria-label', ver ? 'Ocultar contraseña' : 'Mostrar contraseña');
    estado.textContent = ver ? 'La contraseña se ve.' : 'La contraseña está oculta.';
  });
  // Al enviar vuelve a oculta: el navegador no la guarda como texto.
  campo.form?.addEventListener('submit', () => { campo.type = 'password'; });
  const fila = document.createElement('div');
  fila.className = 'control-con-boton';
  campo.replaceWith(fila);
  fila.append(campo, boton, estado);
});
