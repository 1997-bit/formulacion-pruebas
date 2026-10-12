// Atajos de teclado y su ayuda (?).
(() => {
  const raiz = document.documentElement;

  // Atajos. data-atajo="g c": espacio = secuencia, "+" = juntas. Mod = Ctrl o Cmd.
  // Elemento con data-atajo: click(), o focus() si es campo. Sin elemento: ATAJOS.
  // si: el atajo existe solo si hay dónde actuar. Sin eso, preventDefault quitaría la tecla al navegador (/ en Firefox) sin hacer nada.
  const filtro = () => document.querySelector('.filtros .control');
  const enFormulario = () => document.activeElement?.form;
  // j / k: fila siguiente o anterior de una tabla, tablero o lista del panel; o abre la fila. Como en Jira y Gmail.
  const filas = () => [...document.querySelectorAll('.tabla tbody tr, .tablero-lista > li, .tareas li')].filter((f) => f.querySelector('a'));
  const filaActual = () => filas().find((f) => f.contains(document.activeElement));
  const mover = (paso) => {
    const lista = filas();
    const i = lista.indexOf(filaActual());
    lista[i === -1 ? 0 : Math.min(lista.length - 1, Math.max(0, i + paso))].querySelector('a').focus();
  };
  const ATAJOS = {
    '?': { texto: 'Ayuda de atajos', accion: () => ayudaAtajos() },
    'j': { texto: 'Fila siguiente', si: () => filas().length, accion: () => mover(1) },
    'k': { texto: 'Fila anterior', si: () => filas().length, accion: () => mover(-1) },
    'o': { texto: 'Abrir la fila', si: filaActual, accion: () => filaActual().querySelector('a').click() },
    'Mod+k': { texto: 'Paleta de comandos', si: () => paleta && raiz.dataset.paleta !== 'no', accion: () => abrirPaleta() },
    '/': { texto: 'Ir al filtro', si: filtro, accion: () => filtro().focus() },
    'Mod+Enter': { texto: 'Enviar el formulario', si: enFormulario, accion: () => enFormulario().requestSubmit() },
  };
  // Cómo se ven las teclas: Mod+b → Ctrl+B, o ⌘B en Mac; g → G.
  const MAC = /Mac|iPhone|iPad/.test(navigator.platform);
  // Letra sola al final en mayúscula: b → B, pero Enter queda igual.
  const mayus = (k) => k.replace(/(^|\+|⌘)(\p{Ll})$/u, (_, a, b) => a + b.toUpperCase());
  const verTecla = (k) => mayus(k.replace('Mod+', MAC ? '⌘' : 'Ctrl+'));

  const esCampo = (el) => el.matches('input, textarea, select, [contenteditable]');
  // Sin elementos deshabilitados ni escondidos con hidden o inert.
  const deElementos = () => [...document.querySelectorAll('[data-atajo]')]
    .filter((el) => !el.disabled && !el.closest('[hidden], [inert]')).map((el) => ({
      teclas: el.dataset.atajo,
      texto: el.dataset.atajoTexto || el.textContent.trim(),
      accion: () => (esCampo(el) ? el.focus() : el.click()),
    }));
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

  // Una secuencia (g c) vence a 1 s.
  let pendiente = [];
  let espera;
  const reiniciar = () => { pendiente = []; clearTimeout(espera); };

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
      espera = setTimeout(reiniciar, 1000);
      return;
    }
    reiniciar();
  });
  document.addEventListener('pointerdown', () => { if (pendiente.length) reiniciar(); });

  // Cada elemento con atajo lo muestra: en el tooltip, en aria-keyshortcuts (ARIA no describe secuencias como g c)
  // y en el sidebar con un <kbd> al pasar el mouse. Así se aprenden sin abrir ?.
  // Con data-atajos="no", el CSS esconde el <kbd> y el tooltip, aunque se apaguen en otra pestaña.
  document.querySelectorAll('[data-atajo]').forEach((el) => {
    const teclas = el.dataset.atajo;
    const visto = teclas.split(' ').map(verTecla).join(' ');
    if (!teclas.includes(' ') && (raiz.dataset.atajos !== 'no' || teclas.startsWith('Mod+'))) el.setAttribute('aria-keyshortcuts', mayus(teclas.replace('Mod+', MAC ? 'Meta+' : 'Control+')));
    if (el.closest('.sidebar')) {
      el.append(Object.assign(document.createElement('kbd'), { className: 'sidebar-atajo', textContent: visto }));
      el.lastChild.setAttribute('aria-hidden', 'true');
    } else {
      el.dataset.tooltip = el.dataset.tooltip ? `${el.dataset.tooltip} (${visto})` : `Atajo: ${visto}`;
    }
  });

  // Grupos por la tecla: Ir a (g + letra), General (? y Ctrl) y Esta página (el resto).
  function seccionesAtajos() {
    const grupos = { 'Ir a': [], 'Esta página': [], 'General': [] };
    const vistos = new Set();
    todosAtajos().forEach((a) => {
      if (vistos.has(a.teclas)) return;
      vistos.add(a.teclas);
      const grupo = a.teclas.startsWith('g ') ? 'Ir a' : a.teclas === '?' || a.teclas.startsWith('Mod+') ? 'General' : 'Esta página';
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
    return secciones;
  }

  // ?: lista de atajos de la página, armada al abrir.
  let dialogoAtajos;
  function ayudaAtajos() {
    if (!dialogoAtajos) {
      dialogoAtajos = document.createElement('dialog');
      dialogoAtajos.className = 'dialogo dialogo-atajos';
      dialogoAtajos.setAttribute('aria-labelledby', 'atajos-titulo');
      document.body.append(dialogoAtajos);
    }
    const titulo = document.createElement('h2');
    titulo.id = 'atajos-titulo';
    titulo.textContent = 'Atajos de teclado';
    const secciones = seccionesAtajos();
    // Botón visible para cerrar; method="dialog" cierra sin JS.
    const cerrar = Object.assign(document.createElement('form'), { method: 'dialog', className: 'acciones' });
    cerrar.append(Object.assign(document.createElement('button'), { className: 'btn btn-secundario', textContent: 'Cerrar', autofocus: true }));
    dialogoAtajos.replaceChildren(titulo, ...secciones, cerrar);
    dialogoAtajos.showModal();
  }
  document.querySelector('[data-ver-atajos]')?.addEventListener('click', () => ayudaAtajos());

  // Paleta de comandos (Ctrl/⌘+K). Las opciones fijas vienen del servidor (partials/paleta.php).
  // Al abrir se suman Recientes (localStorage) y Esta página (botones del encabezado y elementos con atajo).
  const paleta = document.getElementById('paleta');
  let abrirPaleta = () => {};
  if (paleta) {
    const entrada = paleta.querySelector('.paleta-entrada');
    const lista = paleta.querySelector('.paleta-lista');
    const vacio = paleta.querySelector('.paleta-vacio');
    const estado = document.getElementById('paleta-estado');
    // Sin tildes ni mayúsculas: "configuracion" encuentra "Configuración".
    const normal = (t) => t.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
    let enPagina = [];
    const ACCIONES = {
      tema: () => document.getElementById('tema')?.click(),
      menu: () => document.getElementById('sidebar-alternar')?.click(),
      atajos: () => ayudaAtajos(),
    };
    let n = 0;
    const opcion = (grupo, texto, destino) => {
      const li = Object.assign(document.createElement('li'), { id: `paleta-d${n++}` });
      li.setAttribute('role', 'option');
      li.dataset.grupo = grupo;
      li.dataset.destino = destino;
      li.dataset.dinamica = '';
      li.append(Object.assign(document.createElement('span'), { textContent: texto }));
      return li;
    };
    const visibles = () => [...lista.children].filter((li) => !li.hidden);
    const activa = () => lista.querySelector('[aria-selected="true"]');
    const activar = (li) => {
      activa()?.setAttribute('aria-selected', 'false');
      if (!li) { entrada.removeAttribute('aria-activedescendant'); return; }
      li.setAttribute('aria-selected', 'true');
      entrada.setAttribute('aria-activedescendant', li.id);
      li.scrollIntoView({ block: 'nearest' });
    };
    const filtrar = () => {
      const q = normal(entrada.value.trim());
      [...lista.children].forEach((li) => { li.hidden = q !== '' && !normal(`${li.textContent} ${li.dataset.grupo}`).includes(q); });
      const v = visibles();
      vacio.hidden = v.length > 0;
      estado.textContent = `${v.length} resultado(s)`;
      activar(v[0]);
    };
    const ejecutar = (li) => {
      if (!li) return;
      paleta.close();
      const d = li.dataset.destino;
      if (d.startsWith('accion:')) ACCIONES[d.slice(7)]?.();
      else if (d.startsWith('pagina:')) enPagina[Number(d.slice(7))]?.click();
      else location.href = d;
    };
    abrirPaleta = () => {
      lista.querySelectorAll('[data-dinamica]').forEach((li) => li.remove());
      let recientes = [];
      try { recientes = JSON.parse(localStorage.getItem('recientes')) || []; } catch (e) {}
      enPagina = [...new Set(document.querySelectorAll('.encabezado .acciones :is(a, button), .app-principal [data-atajo]'))]
        .filter((el) => !el.closest('[hidden], [inert], dialog') && !el.disabled && el.getAttribute('aria-disabled') !== 'true');
      const nuevas = [
        ...recientes.map((r) => opcion('Recientes', `${r.codigo} · ${r.texto}`, r.url)),
        ...enPagina.map((el, i) => opcion('Esta página', el.dataset.atajoTexto || el.getAttribute('aria-label') || el.textContent.trim(), `pagina:${i}`)),
      ];
      lista.prepend(...nuevas);
      entrada.value = '';
      filtrar();
      paleta.showModal();
      entrada.focus();
    };
    entrada.addEventListener('input', filtrar);
    entrada.addEventListener('keydown', (e) => {
      const v = visibles();
      const i = v.indexOf(activa());
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        activar(v[(i + (e.key === 'ArrowDown' ? 1 : -1) + v.length) % v.length]);
      } else if (e.key === 'Enter') {
        e.preventDefault();
        ejecutar(activa());
      } else if (e.key.toLowerCase() === 'k' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        paleta.close();
      }
    });
    lista.addEventListener('click', (e) => ejecutar(e.target.closest('[role="option"]')));
    lista.addEventListener('pointermove', (e) => {
      const li = e.target.closest('[role="option"]');
      if (li && li !== activa()) activar(li);
    });
  }

  // Ajustes → Accesibilidad: la misma lista, en la página. Cambia al apagar los atajos de una tecla.
  const listaAtajos = document.querySelector('[data-lista-atajos]');
  // Los de otras pantallas no están en esta página: van fijos, al final.
  const OTRAS = [
    ['c', 'Listas: crear'], ['/', 'Listas: ir al filtro'], ['j', 'Listas: fila siguiente; caso: el siguiente'],
    ['k', 'Listas: fila anterior; caso: el anterior'], ['o', 'Listas: abrir la fila'], ['e', 'Caso: editar'],
    ['i', 'Caso: registrar incidente'], ['a', 'Incidente: asignarme'], ['Mod+Enter', 'Formularios: enviar'],
  ];
  const otrasPantallas = () => {
    const lista = Object.assign(document.createElement('dl'), { className: 'atajos-lista' });
    OTRAS.filter(([t]) => raiz.dataset.atajos !== 'no' || t.startsWith('Mod+')).forEach(([teclas, texto]) => {
      const dt = document.createElement('dt');
      dt.append(Object.assign(document.createElement('kbd'), { textContent: verTecla(teclas) }));
      lista.append(dt, Object.assign(document.createElement('dd'), { textContent: texto }));
    });
    return [Object.assign(document.createElement('h3'), { className: 'atajos-grupo', textContent: 'En otras pantallas' }), lista];
  };
  if (listaAtajos) {
    const pintar = () => listaAtajos.replaceChildren(...seccionesAtajos().filter((el, i, todos) => todos[i - 1]?.textContent !== 'Esta página' && el.textContent !== 'Esta página'), ...otrasPantallas());
    document.addEventListener('change', () => setTimeout(pintar));
    pintar();
  }
})();
