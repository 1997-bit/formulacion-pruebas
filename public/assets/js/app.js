// Interfaz: sidebar, menús, diálogos, tooltips, tablas editables, totales y tema.
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
  // El valor por defecto no se guarda.
  const POR_DEFECTO = { tema: 'sistema', atajos: 'si', pista: 'si' };
  const aplicarAjustes = () => {
    raiz.dataset.tema = leer('tema') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    raiz.dataset.atajos = leer('atajos') || POR_DEFECTO.atajos;
    raiz.dataset.pista = leer('pista') || POR_DEFECTO.pista;
    pintarTema();
    // Radio: marcado si es el valor guardado. Switch (value="si"): marcado si vale 'si'.
    document.querySelectorAll('input[data-ajuste]').forEach((r) => {
      r.checked = r.value === (leer(r.name) || POR_DEFECTO[r.name]);
    });
  };
  botonTema?.addEventListener('click', () => {
    guardar('tema', raiz.dataset.tema === 'dark' ? 'light' : 'dark');
    aplicarAjustes();
  });
  // Página de Ajustes: cada opción se guarda al marcarla.
  document.addEventListener('change', (e) => {
    if (!e.target.matches('input[data-ajuste]')) return;
    const { name, type, checked } = e.target;
    const value = type === 'checkbox' ? (checked ? 'si' : 'no') : e.target.value;
    guardar(name, value === POR_DEFECTO[name] ? null : value);
    aplicarAjustes();
    const estado = document.getElementById('ajuste-estado');
    const texto = e.target.closest('label').querySelector('span').firstChild.textContent.trim();
    if (estado) estado.textContent = `Guardado: ${texto}${type === 'checkbox' ? (checked ? ' activada' : ' desactivada') : ''}.`;
  });
  window.addEventListener('storage', aplicarAjustes);
  // Tema "Sistema": sigue al sistema operativo sin recargar.
  matchMedia('(prefers-color-scheme: dark)').addEventListener('change', aplicarAjustes);
  aplicarAjustes();
})();
