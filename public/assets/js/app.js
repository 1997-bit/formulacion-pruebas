// Interfaz: sidebar, atajos, menús, diálogos, tooltips, tablas editables, totales y tema.
(() => {
  const raiz = document.documentElement;
  const sidebar = document.getElementById('sidebar');
  const alternar = document.getElementById('sidebar-alternar');
  const fondo = document.getElementById('sidebar-fondo');
  const escritorio = window.matchMedia('(min-width: 48rem)');

  if (sidebar && alternar) {
    const colapsado = () => raiz.dataset.sidebar === 'colapsado';
    const movilAbierto = () => raiz.dataset.sidebarMovil === 'abierto';

    const sincronizarAria = () => {
      alternar.setAttribute('aria-expanded', String(escritorio.matches ? !colapsado() : movilAbierto()));
    };

    const fijarColapsado = (valor) => {
      raiz.dataset.sidebar = valor ? 'colapsado' : 'expandido';
      // Cookie: PHP pinta el estado en la próxima página.
      document.cookie = `sidebar=${raiz.dataset.sidebar}; path=/; max-age=31536000; SameSite=Lax`;
      sincronizarAria();
    };

    const abrirMovil = () => {
      raiz.dataset.sidebarMovil = 'abierto';
      fondo.hidden = false;
      sincronizarAria();
      sidebar.querySelector('a, summary')?.focus();
    };

    const cerrarMovil = (devolverFoco = true) => {
      delete raiz.dataset.sidebarMovil;
      fondo.hidden = true;
      sincronizarAria();
      if (devolverFoco) alternar.focus();
    };

    const conmutar = () => {
      if (escritorio.matches) fijarColapsado(!colapsado());
      else if (movilAbierto()) cerrarMovil();
      else abrirMovil();
    };

    alternar.addEventListener('click', conmutar);
    fondo.addEventListener('click', () => cerrarMovil());

    // Ctrl+B: data-atajo en el botón.
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && movilAbierto()) cerrarMovil();
    });

    // Colapsado: abrir un grupo expande el sidebar.
    sidebar.querySelectorAll('.sidebar-desplegable > summary').forEach((summary) => {
      summary.addEventListener('click', (e) => {
        if (escritorio.matches && colapsado()) {
          e.preventDefault();
          fijarColapsado(false);
          summary.parentElement.open = true;
        }
      });
    });

    escritorio.addEventListener('change', () => {
      if (escritorio.matches && movilAbierto()) cerrarMovil(false);
      sincronizarAria();
    });
    sincronizarAria();
  }

  // Atajos. data-atajo="g c": espacio = secuencia, "+" = juntas, "," = alternativas. Mod = Ctrl o Cmd.
  // Elemento con data-atajo: click(), o focus() si es campo. Sin elemento: ATAJOS.
  // Modo en <html data-atajos> (Ajustes): estandar, avanzado (más data-atajo-avanzado, estilo LazyVim) o ninguno (solo con Ctrl).
  // si: el atajo existe solo si hay dónde actuar. Sin eso, preventDefault quitaría la tecla al navegador (/ en Firefox) sin hacer nada.
  const filtro = () => document.querySelector('.filtros .control');
  const hayFilas = () => document.querySelector('.tabla tbody a');
  const enFormulario = () => document.activeElement?.form;
  const ATAJOS = {
    '?': { texto: 'Ayuda de atajos', accion: () => ayudaAtajos() },
    '/': { texto: 'Ir al filtro', si: filtro, accion: () => filtro().focus() },
    'j': { texto: 'Fila siguiente (Enter la abre)', si: hayFilas, accion: () => marcarFila(1) },
    'k': { texto: 'Fila anterior', si: hayFilas, accion: () => marcarFila(-1) },
    'Mod+Enter': { texto: 'Enviar el formulario', si: enFormulario, accion: () => enFormulario().requestSubmit() },
  };
  const AVANZADOS = {
    'g g': { texto: 'Primera fila', si: hayFilas, accion: () => marcarFila(-Infinity) },
    'G': { texto: 'Última fila', si: hayFilas, accion: () => marcarFila(Infinity) },
    'Space /': { texto: 'Ir al filtro', si: filtro, accion: () => filtro().focus() },
    'Space s k': { texto: 'Buscar atajos', accion: () => ayudaAtajos() },
  };
  // Nombre de cada grupo en la pista (estilo which-key de LazyVim).
  const GRUPOS = { 'g': 'Ir a', 'Space': 'Líder', 'Space u': 'Interfaz', 'Space q': 'Sesión', 'Space s': 'Buscar' };

  // J/K: foco en el enlace de la fila (el lector de pantalla la anuncia; Enter la abre). data-marcada solo pinta.
  function marcarFila(paso) {
    const filas = [...(document.querySelector('.tabla tbody')?.rows ?? [])].filter((f) => f.querySelector('a'));
    if (!filas.length) return;
    const actual = filas.findIndex((f) => f.hasAttribute('data-marcada'));
    const siguiente = filas[Math.min(Math.max(actual + paso, 0), filas.length - 1)];
    filas[actual]?.removeAttribute('data-marcada');
    siguiente.setAttribute('data-marcada', '');
    siguiente.querySelector('a').focus();
  }
  const esCampo = (el) => el.matches('input, textarea, select, [contenteditable]');
  // Sin elementos deshabilitados ni escondidos con hidden o inert.
  const deElementos = (atributo) => [...document.querySelectorAll(`[data-${atributo}]`)]
    .filter((el) => !el.disabled && !el.closest('[hidden], [inert]')).flatMap((el) =>
    el.getAttribute(`data-${atributo}`).split(',').map((teclas) => ({
      teclas: teclas.trim(),
      texto: el.dataset.atajoTexto || el.dataset.etiqueta || el.getAttribute('aria-label') || el.textContent.trim(),
      accion: () => (esCampo(el) ? el.focus() : el.click()),
    })));
  const deObjeto = (objeto) => Object.entries(objeto).filter(([, a]) => !a.si || a.si()).map(([teclas, a]) => ({ teclas, ...a }));
  const todosAtajos = () => {
    const modo = raiz.dataset.atajos;
    const lista = [...deElementos('atajo'), ...deObjeto(ATAJOS)];
    if (modo === 'avanzado') lista.push(...deElementos('atajo-avanzado'), ...deObjeto(AVANZADOS));
    // WCAG 2.1.4: sin atajos de una tecla.
    return modo === 'ninguno' ? lista.filter((a) => a.teclas.startsWith('Mod+')) : lista;
  };

  // Letras con su mayúscula: G no es g.
  const tecla = (e) => {
    if (['Shift', 'Control', 'Meta', 'Alt'].includes(e.key) || e.altKey) return null;
    const k = e.key === ' ' ? 'Space' : e.key.length === 1 && (e.ctrlKey || e.metaKey) ? e.key.toLowerCase() : e.key;
    return (e.ctrlKey || e.metaKey ? 'Mod+' : '') + k;
  };

  let pendiente = [];
  let espera;
  const reiniciar = () => { pendiente = []; clearTimeout(espera); pista.replaceChildren(); };

  // Pista: qué teclas siguen después de una tecla de grupo.
  // Vive siempre en la página: una región role="status" anuncia solo los cambios. Vacía no se ve.
  const pista = Object.assign(document.createElement('div'), { className: 'atajos-pista' });
  pista.setAttribute('role', 'status');
  document.body.append(pista);
  function mostrarPista(secuencia, atajos) {
    const siguientes = new Map();
    atajos.forEach(({ teclas, texto }) => {
      const resto = teclas.startsWith(secuencia + ' ') && teclas.slice(secuencia.length + 1).split(' ');
      if (!resto || siguientes.has(resto[0])) return;
      siguientes.set(resto[0], resto.length > 1 ? `+${GRUPOS[`${secuencia} ${resto[0]}`] ?? 'grupo'}` : texto);
    });
    const titulo = document.createElement('strong');
    titulo.textContent = GRUPOS[secuencia] ?? secuencia;
    const lista = document.createElement('dl');
    siguientes.forEach((texto, k) => {
      const dt = document.createElement('dt');
      dt.append(Object.assign(document.createElement('kbd'), { textContent: k }));
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
      clearTimeout(espera);
      // Con pista a la vista, espera más: el usuario la está leyendo.
      espera = setTimeout(reiniciar, 3000);
      mostrarPista(secuencia, atajos);
      return;
    }
    reiniciar();
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
    const lista = document.createElement('dl');
    lista.className = 'atajos-lista';
    const vistos = new Set();
    todosAtajos().forEach(({ teclas, texto }) => {
      if (vistos.has(teclas)) return;
      vistos.add(teclas);
      const dt = document.createElement('dt');
      teclas.split(' ').forEach((k) => {
        const kbd = document.createElement('kbd');
        kbd.textContent = k.replace('Mod+', 'Ctrl+');
        dt.append(kbd);
      });
      const dd = document.createElement('dd');
      dd.textContent = texto;
      lista.append(dt, dd);
    });
    // Botón visible para cerrar; method="dialog" cierra sin JS.
    const cerrar = Object.assign(document.createElement('form'), { method: 'dialog', className: 'acciones' });
    cerrar.append(Object.assign(document.createElement('button'), { className: 'btn btn-secundario', textContent: 'Cerrar', autofocus: true }));
    dialogoAtajos.replaceChildren(titulo, lista, cerrar);
    dialogoAtajos.showModal();
  }

  // Menú del usuario: cierra con clic fuera o Escape.
  const menuUsuario = document.querySelector('.usuario-menu');
  if (menuUsuario) {
    document.addEventListener('click', (e) => {
      if (menuUsuario.open && !menuUsuario.contains(e.target)) menuUsuario.open = false;
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && menuUsuario.open) {
        menuUsuario.open = false;
        menuUsuario.querySelector('summary').focus();
      }
    });
  }

  // <button data-abrir-dialogo="id"> abre <dialog id="id">.
  document.querySelectorAll('[data-abrir-dialogo]').forEach((boton) => {
    const dialogo = document.getElementById(boton.dataset.abrirDialogo);
    if (!dialogo) return;
    boton.addEventListener('click', () => dialogo.showModal());
    dialogo.addEventListener('click', (e) => {
      // Solo clic en el velo, no en el relleno.
      const r = dialogo.getBoundingClientRect();
      const fuera = e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
      if (e.target === dialogo && fuera) dialogo.close();
    });
  });

  // Escape esconde los tooltips hasta mover el mouse o el foco.
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') raiz.setAttribute('data-tooltips-ocultos', '');
  });
  const mostrarTooltips = () => raiz.removeAttribute('data-tooltips-ocultos');
  document.addEventListener('pointermove', mostrarTooltips);
  document.addEventListener('focusin', mostrarTooltips);

  // data-agregar-fila="tbody" copia <template id="tbody-fila">; data-quitar-fila borra. Siempre queda una.
  document.addEventListener('click', (e) => {
    const agregar = e.target.closest('[data-agregar-fila]');
    if (agregar) {
      const cuerpo = document.getElementById(agregar.dataset.agregarFila);
      const plantilla = document.getElementById(`${agregar.dataset.agregarFila}-fila`);
      if (!cuerpo || !plantilla) return;
      cuerpo.append(plantilla.content.cloneNode(true));
      cuerpo.lastElementChild.querySelector('input, select, textarea')?.focus();
      return;
    }
    const quitar = e.target.closest('[data-quitar-fila]');
    if (quitar) {
      const cuerpo = quitar.closest('tbody');
      quitar.closest('tr').remove();
      const botonAgregar = document.querySelector(`[data-agregar-fila="${cuerpo.id}"]`);
      if (cuerpo.rows.length === 0) botonAgregar?.click();
      else botonAgregar?.focus();
      calcular();
    }
  });

  // Totales en vivo, solo para ver: el servidor recalcula. data-suma, data-promedio y data-porcentaje.
  function calcular() {
    document.querySelectorAll('output[data-suma], output[data-promedio]').forEach((salida) => {
      const grupo = salida.dataset.suma ?? salida.dataset.promedio;
      const valores = [...document.querySelectorAll(`[data-grupo="${grupo}"]`)]
        .filter((c) => (c.type === 'radio' ? c.checked : c.value !== ''))
        .map((c) => Number(c.value));
      const total = valores.reduce((a, b) => a + b, 0);
      if (salida.dataset.suma !== undefined) salida.textContent = String(total);
      else salida.textContent = valores.length ? (total / valores.length).toFixed(1) : '—';
    });
    document.querySelectorAll('output[data-porcentaje]').forEach((salida) => {
      const fila = salida.closest('tr');
      const total = fila.querySelector('[data-parte="total"]')?.value ?? '';
      const cubiertos = fila.querySelector('[data-parte="cubiertos"]')?.value ?? '';
      // Mismo redondeo que CoberturaServicio::porcentaje(): multiplicar antes de dividir.
      const valido = /^\d+$/.test(total) && /^\d+$/.test(cubiertos) && Number(total) > 0 && Number(cubiertos) <= Number(total);
      const porcentaje = valido ? Math.round((Number(cubiertos) * 100) / Number(total)) : 0;
      salida.textContent = valido ? `${porcentaje} %` : '—';
      const medidor = fila.querySelector('meter');
      if (medidor) medidor.value = porcentaje;
    });
  }
  document.addEventListener('input', calcular);
  calcular();

  document.querySelectorAll('[data-imprimir]').forEach((boton) => {
    boton.addEventListener('click', () => window.print());
  });

  // data-desde="id-inicio": la fecha final no puede ser anterior.
  document.querySelectorAll('input[data-desde]').forEach((fin) => {
    const inicio = document.getElementById(fin.dataset.desde);
    if (!inicio) return;
    const ajustar = () => { fin.min = inicio.value; };
    inicio.addEventListener('input', ajustar);
    ajustar();
  });

  // data-cuando="nombre=valor": se ve y se envía solo si esa opción está marcada.
  const mostrarSegunMarcado = () => {
    document.querySelectorAll('[data-cuando]').forEach((bloque) => {
      const [nombre, valor] = bloque.dataset.cuando.split('=');
      bloque.hidden = !bloque.closest('form')?.querySelector(`input[name="${nombre}"][value="${valor}"]:checked`);
      bloque.querySelectorAll('input, select, textarea').forEach((c) => { c.disabled = bloque.hidden; });
    });
  };
  document.addEventListener('change', mostrarSegunMarcado);
  mostrarSegunMarcado();

  // data-vista-previa="id-img": muestra la imagen elegida.
  document.querySelectorAll('input[data-vista-previa]').forEach((entrada) => {
    const img = document.getElementById(entrada.dataset.vistaPrevia);
    entrada.addEventListener('change', () => {
      const archivo = entrada.files[0];
      img.hidden = !archivo;
      if (archivo) img.src = URL.createObjectURL(archivo);
    });
  });

  // Tema y atajos: localStorage, igual en todas las pestañas. tema.js los aplica antes de pintar.
  const botonTema = document.getElementById('tema');
  const pintarTema = () => {
    if (!botonTema) return;
    const oscuro = raiz.dataset.tema === 'dark';
    botonTema.setAttribute('aria-pressed', String(oscuro));
    botonTema.dataset.tooltip = oscuro ? 'Cambiar a claro' : 'Cambiar a oscuro';
  };
  const leer = (clave) => { try { return localStorage.getItem(clave); } catch (e) { return null; } };
  const guardar = (clave, valor) => {
    try { valor === null ? localStorage.removeItem(clave) : localStorage.setItem(clave, valor); } catch (e) {}
  };
  const aplicarAjustes = () => {
    raiz.dataset.tema = leer('tema') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    raiz.dataset.atajos = leer('atajos') || 'estandar';
    pintarTema();
    document.querySelectorAll('input[data-ajuste]').forEach((r) => {
      r.checked = r.value === (leer(r.name) || (r.name === 'tema' ? 'sistema' : 'estandar'));
    });
  };
  botonTema?.addEventListener('click', () => {
    guardar('tema', raiz.dataset.tema === 'dark' ? 'light' : 'dark');
    aplicarAjustes();
  });
  // Página de Ajustes: cada opción se guarda al marcarla.
  document.addEventListener('change', (e) => {
    if (!e.target.matches('input[data-ajuste]')) return;
    const { name, value } = e.target;
    guardar(name, ['sistema', 'estandar'].includes(value) ? null : value);
    aplicarAjustes();
    const estado = document.getElementById('ajuste-estado');
    if (estado) estado.textContent = `Guardado: ${e.target.closest('label').querySelector('span').firstChild.textContent.trim()}.`;
  });
  document.querySelector('[data-ver-atajos]')?.addEventListener('click', () => ayudaAtajos());
  window.addEventListener('storage', aplicarAjustes);
  aplicarAjustes();
})();
