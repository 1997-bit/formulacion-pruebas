// Comportamiento de la interfaz: sidebar, menú de usuario, diálogos, tooltips, tablas editables, totales, impresión y tema.
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
      // La cookie permite que PHP pinte el estado correcto en la próxima página.
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
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
        e.preventDefault();
        conmutar();
      }
      if (e.key === 'Escape' && movilAbierto()) cerrarMovil();
    });

    // Colapsado: al pulsar un grupo con submenú, se expande el sidebar y se abre ese grupo.
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

  // Menú del usuario: se cierra al hacer clic fuera o con Escape.
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

  // Diálogos: <button data-abrir-dialogo="id"> abre <dialog id="id">. Se cierran con Escape,
  // con un botón dentro de <form method="dialog"> o haciendo clic en el velo.
  document.querySelectorAll('[data-abrir-dialogo]').forEach((boton) => {
    const dialogo = document.getElementById(boton.dataset.abrirDialogo);
    if (!dialogo) return;
    boton.addEventListener('click', () => dialogo.showModal());
    dialogo.addEventListener('click', (e) => {
      // Solo si el clic cae fuera del recuadro (en el velo), no en el relleno del diálogo.
      const r = dialogo.getBoundingClientRect();
      const fuera = e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
      if (e.target === dialogo && fuera) dialogo.close();
    });
  });

  // Tooltips: Escape los esconde; vuelven al mover el mouse o el foco.
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') raiz.setAttribute('data-tooltips-ocultos', '');
  });
  const mostrarTooltips = () => raiz.removeAttribute('data-tooltips-ocultos');
  document.addEventListener('pointermove', mostrarTooltips);
  document.addEventListener('focusin', mostrarTooltips);

  // Tablas editables: <button data-agregar-fila="id-del-tbody"> agrega al final una copia de
  // <template id="id-del-tbody-fila">; <button data-quitar-fila> borra su fila. Siempre queda una.
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

  // Totales en vivo. El servidor los vuelve a calcular al guardar; esto es solo para ver.
  // <output data-suma="g"> / <output data-promedio="g">: suma o promedia los controles con
  //   data-grupo="g" (radios marcados, selects y números con valor).
  // <output data-porcentaje> dentro de una fila: cubiertos / total de esa misma fila, y mueve
  //   el <meter> de la fila.
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
      const total = Number(fila.querySelector('[data-parte="total"]')?.value);
      const cubiertos = Number(fila.querySelector('[data-parte="cubiertos"]')?.value);
      const porcentaje = total > 0 ? Math.min(100, Math.round((cubiertos / total) * 100)) : 0;
      salida.textContent = total > 0 ? `${porcentaje} %` : '—';
      const medidor = fila.querySelector('meter');
      if (medidor) medidor.value = porcentaje;
    });
  }
  document.addEventListener('input', calcular);
  calcular();

  // Imprimir: <button data-imprimir> abre el diálogo de impresión del navegador.
  document.querySelectorAll('[data-imprimir]').forEach((boton) => {
    boton.addEventListener('click', () => window.print());
  });

  // Rango de fechas: <input type="date" data-desde="id-inicio"> no deja elegir una fecha
  // anterior a la de inicio. El servidor lo vuelve a validar al guardar.
  document.querySelectorAll('input[data-desde]').forEach((fin) => {
    const inicio = document.getElementById(fin.dataset.desde);
    if (!inicio) return;
    const ajustar = () => { fin.min = inicio.value; };
    inicio.addEventListener('input', ajustar);
    ajustar();
  });

  // Campos según un radio: <div data-cuando="nombre=valor"> se ve solo con ese radio marcado,
  // y sus controles se desactivan para no enviarse. Sin JS se ven todos.
  const mostrarSegunRadio = () => {
    document.querySelectorAll('[data-cuando]').forEach((bloque) => {
      const [nombre, valor] = bloque.dataset.cuando.split('=');
      const marcado = bloque.closest('form')?.querySelector(`input[name="${nombre}"]:checked`);
      bloque.hidden = marcado?.value !== valor;
      bloque.querySelectorAll('input, select, textarea').forEach((c) => { c.disabled = bloque.hidden; });
    });
  };
  document.addEventListener('change', mostrarSegunRadio);
  mostrarSegunRadio();

  // Vista previa: <input type="file" data-vista-previa="id-img"> muestra la imagen elegida.
  document.querySelectorAll('input[data-vista-previa]').forEach((entrada) => {
    const img = document.getElementById(entrada.dataset.vistaPrevia);
    entrada.addEventListener('change', () => {
      const archivo = entrada.files[0];
      img.hidden = !archivo;
      if (archivo) img.src = URL.createObjectURL(archivo);
    });
  });

  // Tema: alterna entre claro y oscuro y recuerda la elección.
  const botonTema = document.getElementById('tema');
  if (botonTema) {
    const pintar = () => {
      const oscuro = raiz.dataset.tema === 'dark';
      botonTema.setAttribute('aria-pressed', String(oscuro));
      botonTema.dataset.tooltip = oscuro ? 'Cambiar a claro' : 'Cambiar a oscuro';
    };
    botonTema.addEventListener('click', () => {
      raiz.dataset.tema = raiz.dataset.tema === 'dark' ? 'light' : 'dark';
      try { localStorage.setItem('tema', raiz.dataset.tema); } catch (e) {}
      pintar();
    });
    pintar();
  }
})();
