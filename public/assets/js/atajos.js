// Atajos de teclado y su ayuda (?).
(() => {
  const raiz = document.documentElement;

  // Atajos. data-atajo="g c": espacio = secuencia, "+" = juntas, "," = alternativas. Mod = Ctrl o Cmd.
  // Elemento con data-atajo: click(), o focus() si es campo. Sin elemento: ATAJOS.
  // si: el atajo existe solo si hay dónde actuar. Sin eso, preventDefault quitaría la tecla al navegador (/ en Firefox) sin hacer nada.
  const filtro = () => document.querySelector('.filtros .control');
  const hayFilas = () => document.querySelector('.tabla tbody a');
  const enFormulario = () => document.activeElement?.form;
  const ATAJOS = {
    '?': { texto: 'Ayuda de atajos', grupo: 'General', accion: () => ayudaAtajos() },
    '/': { texto: 'Ir al filtro', grupo: 'Esta página', si: filtro, accion: () => filtro().focus() },
    'j': { texto: 'Fila siguiente (Enter la abre)', grupo: 'Esta página', si: hayFilas, accion: () => marcarFila(1) },
    'k': { texto: 'Fila anterior', grupo: 'Esta página', si: hayFilas, accion: () => marcarFila(-1) },
    'Mod+Enter': { texto: 'Enviar el formulario', grupo: 'Esta página', si: enFormulario, accion: () => enFormulario().requestSubmit() },
  };
  // Cómo se ven las teclas: Mod+b → Ctrl+B, o ⌘B en Mac; g → G.
  const MAC = /Mac|iPhone|iPad/.test(navigator.platform);
  const verTecla = (k) => k.replace('Mod+', MAC ? '⌘' : 'Ctrl+').replace(/(^|\+|⌘)(\p{Ll})$/u, (_, a, b) => a + b.toUpperCase());
  // Título de la pista según la primera tecla.
  const GRUPOS = { 'g': 'Ir a' };

  // J/K: foco en el enlace de la fila (el lector de pantalla la anuncia; Enter la abre). data-marcada solo pinta.
  function marcarFila(paso) {
    const filas = [...(document.querySelector('.tabla tbody')?.rows ?? [])].filter((f) => f.querySelector('a'));
    if (!filas.length) return;
    // Parte de la fila con foco (llegó con Tab o clic); si no hay, de la marcada.
    const conFoco = filas.findIndex((f) => f.contains(document.activeElement));
    const actual = conFoco >= 0 ? conFoco : filas.findIndex((f) => f.hasAttribute('data-marcada'));
    filas.forEach((f) => f.removeAttribute('data-marcada'));
    const siguiente = filas[Math.min(Math.max(actual + paso, 0), filas.length - 1)];
    siguiente.setAttribute('data-marcada', '');
    siguiente.querySelector('a').focus();
  }
  // Al volver con Atrás, el foco regresa a la última fila (como Gmail). sessionStorage: solo esta pestaña.
  const claveFila = () => `fila:${location.pathname}${location.search}`;
  document.addEventListener('focusin', (e) => {
    const enlace = e.target.closest('.tabla tbody a');
    if (enlace) try { sessionStorage.setItem(claveFila(), enlace.getAttribute('href')); } catch (err) {}
  });
  if (performance.getEntriesByType('navigation')[0]?.type === 'back_forward') {
    let href = null;
    try { href = sessionStorage.getItem(claveFila()); } catch (err) {}
    const enlace = href && document.querySelector(`.tabla tbody a[href="${CSS.escape(href)}"]`);
    if (enlace) {
      enlace.closest('tr').setAttribute('data-marcada', '');
      enlace.focus();
    }
  }
  const esCampo = (el) => el.matches('input, textarea, select, [contenteditable]');
  // Sin elementos deshabilitados ni escondidos con hidden o inert.
  const deElementos = () => [...document.querySelectorAll('[data-atajo]')]
    .filter((el) => !el.disabled && !el.closest('[hidden], [inert]')).flatMap((el) =>
    el.dataset.atajo.split(',').map((teclas) => ({
      teclas: teclas.trim(),
      texto: el.dataset.atajoTexto || el.dataset.etiqueta || el.getAttribute('aria-label') || el.textContent.trim(),
      accion: () => (esCampo(el) ? el.focus() : el.click()),
    })));
  const deObjeto = (objeto) => Object.entries(objeto).filter(([, a]) => !a.si || a.si()).map(([teclas, a]) => ({ teclas, ...a }));
  // <html data-atajos="no"> (Ajustes): sin atajos de una tecla, solo los de Ctrl (WCAG 2.1.4: dictado por voz).
  const todosAtajos = () => {
    const lista = [...deElementos(), ...deObjeto(ATAJOS)];
    return raiz.dataset.atajos === 'no' ? lista.filter((a) => a.teclas.startsWith('Mod+')) : lista;
  };

  // Letras en minúscula: con Bloq Mayús, g c sigue siendo g c.
  const tecla = (e) => {
    if (['Shift', 'Control', 'Meta', 'Alt', 'CapsLock'].includes(e.key) || e.altKey) return null;
    const mod = e.ctrlKey || e.metaKey;
    let k = e.key === ' ' ? 'Space' : e.key;
    if (/^\p{L}$/u.test(k)) k = k.toLowerCase();
    return (mod ? 'Mod+' : '') + k;
  };

  // La pista sale a los 300 ms (escribir rápido no la hace parpadear) y espera hasta otra tecla, Esc o un clic.
  // Sin pista (Ajustes), la secuencia vence a 1 s.
  let pendiente = [];
  let espera;
  let esperaPista;
  const reiniciar = () => { pendiente = []; clearTimeout(espera); clearTimeout(esperaPista); pista.replaceChildren(); };
  const conPista = () => raiz.dataset.pista !== 'no';

  // Pista: qué teclas siguen después de una tecla de grupo.
  // Vive siempre en la página: una región role="status" anuncia solo los cambios. Vacía no se ve.
  const pista = Object.assign(document.createElement('div'), { className: 'atajos-pista' });
  pista.setAttribute('role', 'status');
  document.body.append(pista);
  function mostrarPista(secuencia, atajos) {
    const siguientes = new Map();
    atajos.forEach(({ teclas, texto }) => {
      const resto = teclas.startsWith(secuencia + ' ') && teclas.slice(secuencia.length + 1).split(' ');
      if (resto && !siguientes.has(resto[0])) siguientes.set(resto[0], texto);
    });
    const titulo = document.createElement('strong');
    titulo.textContent = GRUPOS[secuencia] ?? secuencia;
    const lista = document.createElement('dl');
    siguientes.forEach((texto, k) => {
      const dt = document.createElement('dt');
      dt.append(Object.assign(document.createElement('kbd'), { textContent: verTecla(k) }));
      lista.append(dt, Object.assign(document.createElement('dd'), { textContent: texto }));
    });
    pista.replaceChildren(titulo, lista);
  }

  document.addEventListener('keydown', (e) => {
    // IME o tecla muerta (´ + a): la tecla es parte de un carácter, no un atajo.
    if (e.isComposing || e.key === 'Dead') return;
    const t = tecla(e);
    if (!t || e.defaultPrevented || document.querySelector('dialog[open]')) return;
    if (t === 'Escape' && pendiente.length) { reiniciar(); return; }
    // Tecla sostenida a mitad de secuencia: la repetición no es otra tecla.
    if (e.repeat && pendiente.length) { e.preventDefault(); return; }
    // En un campo solo cuentan las combinaciones con Ctrl. Space y Enter conservan su uso en botones y enlaces.
    if (esCampo(e.target) && !t.startsWith('Mod+')) return;
    if (!pendiente.length && ['Space', 'Enter'].includes(t) && e.target.closest('a, button, summary')) return;
    pendiente.push(t);
    const secuencia = pendiente.join(' ');
    const atajos = todosAtajos();
    const exacto = atajos.find((a) => a.teclas === secuencia);
    if (exacto) {
      e.preventDefault();
      reiniciar();
      exacto.accion();
      return;
    }
    if (atajos.some((a) => a.teclas.startsWith(secuencia + ' '))) {
      e.preventDefault();
      if (conPista()) esperaPista = setTimeout(() => mostrarPista(secuencia, atajos), 300);
      else espera = setTimeout(reiniciar, 1000);
      return;
    }
    reiniciar();
  });
  document.addEventListener('pointerdown', () => { if (pendiente.length) reiniciar(); });

  // Cada elemento con atajo lo muestra: en el tooltip, en aria-keyshortcuts (ARIA no describe secuencias como g c)
  // y en el sidebar con un <kbd> al pasar el mouse. Así se aprenden sin abrir ?.
  document.querySelectorAll('[data-atajo]').forEach((el) => {
    const teclas = el.dataset.atajo.split(',')[0].trim();
    if (raiz.dataset.atajos === 'no' && !teclas.startsWith('Mod+')) return;
    const visto = teclas.split(' ').map(verTecla).join(' ');
    if (!teclas.includes(' ')) el.setAttribute('aria-keyshortcuts', teclas.replace('Mod+', MAC ? 'Meta+' : 'Control+').replace(/\p{Ll}$/u, (l) => l.toUpperCase()));
    if (el.closest('.sidebar')) {
      el.append(Object.assign(document.createElement('kbd'), { className: 'sidebar-atajo', textContent: visto }));
      el.lastChild.setAttribute('aria-hidden', 'true');
      if (el.dataset.etiqueta) el.dataset.etiqueta += ` (${visto})`;
    } else {
      el.dataset.tooltip = el.dataset.tooltip ? `${el.dataset.tooltip} (${visto})` : `Atajo: ${visto}`;
    }
  });

  // ?: lista de atajos de la página, armada al abrir.
  let dialogoAtajos;
  function ayudaAtajos() {
    if (!dialogoAtajos) {
      dialogoAtajos = document.createElement('dialog');
      dialogoAtajos.className = 'dialogo dialogo-atajos';
      dialogoAtajos.setAttribute('aria-labelledby', 'atajos-titulo');
      document.body.append(dialogoAtajos);
      dialogoAtajos.addEventListener('click', (e) => { if (e.target === dialogoAtajos) dialogoAtajos.close(); });
    }
    const titulo = document.createElement('h2');
    titulo.id = 'atajos-titulo';
    titulo.textContent = 'Atajos de teclado';
    // Grupos: Ir a (g + letra), Esta página (botones de la vista y acciones de lista) y General.
    const grupos = { 'Ir a': [], 'Esta página': [], 'General': [] };
    const vistos = new Set();
    todosAtajos().forEach((a) => {
      if (vistos.has(a.teclas)) return;
      vistos.add(a.teclas);
      const grupo = a.grupo ?? (a.teclas.startsWith('g ') ? 'Ir a' : a.teclas.startsWith('Mod+') ? 'General' : 'Esta página');
      grupos[grupo].push(a);
    });
    const secciones = Object.entries(grupos).filter(([, atajos]) => atajos.length).flatMap(([nombre, atajos]) => {
      const subtitulo = Object.assign(document.createElement('h3'), { className: 'atajos-grupo', textContent: nombre });
      const lista = Object.assign(document.createElement('dl'), { className: 'atajos-lista' });
      atajos.forEach(({ teclas, texto }) => {
        const dt = document.createElement('dt');
        teclas.split(' ').forEach((k) => dt.append(Object.assign(document.createElement('kbd'), { textContent: verTecla(k) })));
        lista.append(dt, Object.assign(document.createElement('dd'), { textContent: texto }));
      });
      return [subtitulo, lista];
    });
    // Botón visible para cerrar; method="dialog" cierra sin JS.
    const cerrar = Object.assign(document.createElement('form'), { method: 'dialog', className: 'acciones' });
    cerrar.append(Object.assign(document.createElement('button'), { className: 'btn btn-secundario', textContent: 'Cerrar', autofocus: true }));
    dialogoAtajos.replaceChildren(titulo, ...secciones, cerrar);
    dialogoAtajos.showModal();
  }
  document.querySelector('[data-ver-atajos]')?.addEventListener('click', () => ayudaAtajos());
})();
