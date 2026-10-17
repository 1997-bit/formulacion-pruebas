// Interfaz: sidebar, menús, diálogos, tooltips, tablas editables, totales y tema. Módulo ES: diferido y en modo estricto, sin IIFE.
const raiz = document.documentElement;
const sidebar = document.getElementById('sidebar');
const alternar = document.getElementById('sidebar-alternar');
const fondo = document.getElementById('sidebar-fondo');
const escritorio = window.matchMedia('(min-width: 64rem)');

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

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && movilAbierto()) cerrarMovil();
  });

  // Sidebar colapsado: abrir un grupo lo expande.
  sidebar.querySelectorAll('.sidebar-desplegable > summary').forEach((summary) => {
    summary.addEventListener('click', (e) => {
      if (escritorio.matches && colapsado()) {
        e.preventDefault();
        fijarColapsado(false);
        summary.parentElement.open = true;
      } else if ('medio' in raiz.dataset && !movilAbierto()) {
        // Tablet en rail: abre el menú completo con el grupo abierto.
        e.preventDefault();
        summary.parentElement.open = true;
        abrirMovil();
      }
    });
  });

  // Ajustes → Apariencia: radio del menú lateral.
  document.addEventListener('change', (e) => {
    if (e.target.matches('input[name="sidebar"][data-ajuste]')) fijarColapsado(e.target.value === 'colapsado');
  });

  escritorio.addEventListener('change', () => {
    if (escritorio.matches && movilAbierto()) cerrarMovil(false);
    sincronizarAria();
  });
  sincronizarAria();
}

// Menús popover sin anchor positioning: se ubican junto a su botón al abrir. data-lado: abajo o arriba.
if (!CSS.supports('anchor-name: --a')) {
  document.querySelectorAll('[popover][data-lado]').forEach((menu) => {
    menu.addEventListener('toggle', (e) => {
      if (e.newState !== 'open') return;
      const r = document.querySelector(`[popovertarget="${menu.id}"]`).getBoundingClientRect();
      // Rail: escritorio colapsado o tablet. Se mide en lugar de repetir las reglas del CSS.
      const colapsado = (document.getElementById('sidebar')?.offsetWidth ?? 0) < 100;
      Object.assign(menu.style, colapsado
        ? { left: `${r.right + 8}px`, top: menu.dataset.lado === 'abajo' ? `${r.top}px` : 'auto', bottom: menu.dataset.lado === 'arriba' ? `${innerHeight - r.bottom}px` : 'auto', width: 'auto' }
        : { left: `${r.left}px`, width: `${r.width}px`, top: menu.dataset.lado === 'abajo' ? `${r.bottom + 4}px` : 'auto', bottom: menu.dataset.lado === 'arriba' ? `${innerHeight - r.top + 4}px` : 'auto' });
    });
  });
}

// <button command="show-modal" commandfor="id"> abre <dialog id="id"> sin JS. Respaldo para navegadores sin command.
if (!('command' in HTMLButtonElement.prototype)) {
  document.querySelectorAll('[command="show-modal"]').forEach((boton) => {
    boton.addEventListener('click', () => document.getElementById(boton.getAttribute('commandfor'))?.showModal());
  });
}
// <dialog closedby="any"> cierra con clic en el velo. Respaldo para navegadores sin closedby.
document.addEventListener('click', (e) => {
  if (!(e.target instanceof HTMLDialogElement) || 'closedBy' in HTMLDialogElement.prototype) return;
  const r = e.target.getBoundingClientRect();
  if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) e.target.close();
});

// Aviso flotante: cierra a los 5 s o con ✕. Espera con mouse o foco encima.
const aviso = document.querySelector('.aviso-flotante');
if (aviso) {
  const cerrar = () => (aviso.matches(':hover, :focus-within') ? setTimeout(cerrar, 1000) : aviso.remove());
  setTimeout(cerrar, 5000);
  aviso.querySelector('[data-cerrar-aviso]').addEventListener('click', () => aviso.remove());
}

// Error de validación: foco al primer campo marcado.
document.querySelector('.alerta-error') && document.querySelector('[aria-invalid="true"]')?.focus();

// Un envío por form. Atrás lo libera.
document.addEventListener('submit', (e) => {
  const form = e.target;
  if (form.method === 'dialog' || e.submitter?.formMethod === 'dialog') return;
  if ('enviando' in form.dataset) { e.preventDefault(); return; }
  form.dataset.enviando = '';
  e.submitter?.setAttribute('aria-disabled', 'true');
});
// Página lenta: esqueleto en el contenido si la nueva tarda más de 150 ms. Atrás lo quita.
const principal = document.getElementById('principal');
let esqueleto;
const esperar = () => { esqueleto = setTimeout(() => principal?.setAttribute('aria-busy', 'true'), 150); };
document.addEventListener('click', (e) => {
  const a = e.target instanceof Element ? e.target.closest('a[href]') : null;
  if (!a || e.defaultPrevented || e.button || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
  // Con cambios sin guardar el navegador pregunta. Si el usuario se queda, no hay esqueleto.
  if (sinGuardar || a.target || a.hasAttribute('download') || a.origin !== location.origin || (a.hash && a.pathname === location.pathname)) return;
  esperar();
});
document.addEventListener('submit', (e) => { if (!e.defaultPrevented && e.target.method !== 'dialog') esperar(); });
// Salir: Atrás no muestra páginas de la sesión cerrada desde bfcache.
document.addEventListener('submit', (e) => {
  if (e.target.action.endsWith('/salir')) try { localStorage.setItem('salida', String(Date.now())); } catch { /* sin localStorage: modo privado o bloqueado */ }
});
window.addEventListener('pageshow', (e) => {
  let salida = 0;
  try { salida = Number(localStorage.getItem('salida')); } catch { /* sin localStorage: modo privado o bloqueado */ }
  if (e.persisted && salida > performance.timeOrigin) location.reload();
  clearTimeout(esqueleto);
  principal?.removeAttribute('aria-busy');
  document.querySelectorAll('form[data-enviando]').forEach((f) => {
    delete f.dataset.enviando;
    f.querySelectorAll('[aria-disabled]').forEach((b) => b.removeAttribute('aria-disabled'));
  });
});

// Form POST sin guardar: el navegador pregunta antes de salir.
let sinGuardar = false;
document.addEventListener('input', (e) => { if (e.target.form?.method === 'post') sinGuardar = true; });
document.addEventListener('submit', () => { sinGuardar = false; });
window.addEventListener('beforeunload', (e) => { if (sinGuardar) e.preventDefault(); });

// Escape esconde los tooltips hasta mover el mouse o el foco.
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') raiz.setAttribute('data-tooltips-ocultos', '');
});
const mostrarTooltips = () => raiz.removeAttribute('data-tooltips-ocultos');
document.addEventListener('pointermove', mostrarTooltips);
document.addEventListener('focusin', mostrarTooltips);

// data-agregar-fila="tbody" copia <template id="tbody-fila">. data-quitar-fila borra. Mínimo 1 fila.
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

// Totales en vivo, solo visuales: el servidor recalcula. data-suma, data-promedio, data-porcentaje.
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
    // Redondeo de CoberturaServicio::porcentaje(): multiplica antes de dividir.
    const valido = /^\d+$/.test(total) && /^\d+$/.test(cubiertos) && Number(total) > 0 && Number(cubiertos) <= Number(total);
    const porcentaje = valido ? Math.round((Number(cubiertos) * 100) / Number(total)) : 0;
    salida.textContent = valido ? `${porcentaje} %` : '—';
    const medidor = fila.querySelector('meter');
    if (medidor) medidor.value = porcentaje;
  });
}
document.addEventListener('input', calcular);
calcular();

// Tablero: soltar en otra columna pulsa su botón "Mover a".
let arrastrada = null;
const columnaDe = (e) => (e.target instanceof Element ? e.target.closest('[data-columna-estado]') : null);
document.addEventListener('dragstart', (e) => {
  arrastrada = e.target instanceof Element ? e.target.closest('[data-arrastrable]') : null;
  if (arrastrada) e.dataTransfer.effectAllowed = 'move';
});
document.addEventListener('dragover', (e) => {
  const columna = columnaDe(e);
  if (!arrastrada || !columna) return;
  e.preventDefault();
  document.querySelectorAll('.soltar').forEach((c) => c !== columna && c.classList.remove('soltar'));
  columna.classList.add('soltar');
});
document.addEventListener('drop', (e) => {
  const columna = columnaDe(e);
  if (!arrastrada || !columna) return;
  e.preventDefault();
  const boton = [...arrastrada.querySelectorAll('button[name="destino"]')].find((b) => b.value.endsWith(`-${columna.dataset.columnaEstado}`));
  if (!boton) return;
  columna.querySelector('ul').append(arrastrada);
  boton.form.requestSubmit(boton);
});
document.addEventListener('dragend', () => {
  arrastrada = null;
  document.querySelectorAll('.soltar').forEach((c) => c.classList.remove('soltar'));
});

// Recientes: caso o incidente abierto, máximo 5. Para el panel y la paleta.
const marca = document.querySelector('[data-reciente]');
let recientes = [];
try { recientes = JSON.parse(localStorage.getItem('recientes')) || []; } catch { /* sin localStorage: modo privado o bloqueado */ }
if (marca) {
  const actual = { url: location.pathname + location.search, codigo: marca.dataset.reciente, texto: marca.dataset.recienteTexto };
  recientes = [actual, ...recientes.filter((r) => r.url !== actual.url)].slice(0, 5);
  try { localStorage.setItem('recientes', JSON.stringify(recientes)); } catch { /* sin localStorage: modo privado o bloqueado */ }
}
const panelRecientes = document.querySelector('[data-recientes]');
if (panelRecientes && recientes.length) {
  panelRecientes.querySelector('ul').replaceChildren(...recientes.map((r) => {
    const a = Object.assign(document.createElement('a'), { href: r.url });
    a.append(Object.assign(document.createElement('span'), { className: 'codigo', textContent: r.codigo }), ' ',
      Object.assign(document.createElement('span'), { textContent: r.texto }));
    const li = document.createElement('li');
    li.append(a);
    return li;
  }));
  panelRecientes.hidden = false;
}

document.querySelectorAll('[data-imprimir]').forEach((boton) => {
  boton.addEventListener('click', () => window.print());
});

// Al imprimir: abre los <details> cerrados del contenido y los cierra después. push: beforeprint puede llegar dos veces.
const plegados = [];
addEventListener('beforeprint', () => {
  plegados.push(...document.querySelectorAll('.app-principal details:not([open])'));
  plegados.forEach((d) => { d.open = true; });
});
addEventListener('afterprint', () => {
  plegados.splice(0).forEach((d) => { d.open = false; });
});

// data-desde="id-inicio": fecha final no anterior.
document.querySelectorAll('input[data-desde]').forEach((fin) => {
  const inicio = document.getElementById(fin.dataset.desde);
  if (!inicio) return;
  const ajustar = () => { fin.min = inicio.value; };
  inicio.addEventListener('input', ajustar);
  ajustar();
});

// data-cuando="nombre=valor": visible y enviado solo con esa opción marcada.
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

// Tema y atajos en localStorage, igual en todas las pestañas. tema.js los aplica antes de pintar.
const botonTema = document.getElementById('tema');
const pintarTema = () => {
  if (!botonTema) return;
  const oscuro = raiz.dataset.tema === 'dark';
  botonTema.setAttribute('aria-pressed', String(oscuro));
  botonTema.dataset.tooltip = oscuro ? 'Cambiar a claro' : 'Cambiar a oscuro';
};
const leer = (clave) => { try { return localStorage.getItem(clave); } catch { return null; } };
const guardar = (clave, valor) => {
  try { valor === null ? localStorage.removeItem(clave) : localStorage.setItem(clave, valor); } catch { /* sin localStorage: modo privado o bloqueado */ }
};
// El valor por defecto no se guarda. AJUSTES está en tema.js. El menú lateral va en su cookie.
const valorAjuste = (nombre) => (nombre === 'sidebar' ? raiz.dataset.sidebar : leer(nombre) || AJUSTES[nombre]);
const aplicarAjustes = () => {
  aplicarPreferencias();
  pintarTema();
  // Radio: marcado si es el valor guardado. Switch value="si": marcado si vale 'si'.
  document.querySelectorAll('input[data-ajuste]').forEach((r) => {
    r.checked = r.value === valorAjuste(r.name);
  });
};
botonTema?.addEventListener('click', () => {
  guardar('tema', raiz.dataset.tema === 'dark' ? 'light' : 'dark');
  aplicarAjustes();
});
// Ajustes: cada opción se guarda al marcarla.
document.addEventListener('change', (e) => {
  if (!e.target.matches('input[data-ajuste]')) return;
  const { name, type, checked } = e.target;
  const value = type === 'checkbox' ? (checked ? 'si' : 'no') : e.target.value;
  if (name !== 'sidebar') guardar(name, value === AJUSTES[name] ? null : value);
  aplicarAjustes();
  document.getElementById('ajuste-estado').textContent = 'Guardado.';
});
window.addEventListener('storage', aplicarAjustes);
// Tema "Sistema": sigue al SO sin recargar.
matchMedia('(prefers-color-scheme: dark)').addEventListener('change', aplicarAjustes);
aplicarAjustes();
