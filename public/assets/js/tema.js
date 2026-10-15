// Sin defer: aplica los ajustes antes de pintar, sin parpadeo. app.js la llama al cambiar un ajuste.
// <html data-tema data-densidad data-atajos data-movimiento data-subrayar data-paleta>: el CSS y los scripts leen de ahí.
const AJUSTES = { tema: 'sistema', densidad: 'comoda', atajos: 'si', movimiento: 'no', subrayar: 'no', paleta: 'si' };
function aplicarPreferencias() {
  const raiz = document.documentElement;
  Object.entries(AJUSTES).forEach(([clave, porDefecto]) => {
    let valor = null;
    try { valor = localStorage.getItem(clave); } catch { /* sin localStorage: modo privado o bloqueado */ }
    // Movimiento era sistema o reducido, ahora sí o no. Se guarda convertido: Ajustes lee lo guardado.
    if (clave === 'movimiento' && (valor === 'reducido' || valor === 'sistema')) {
      valor = valor === 'reducido' ? 'si' : 'no';
      try { localStorage.setItem(clave, valor); } catch { /* sin localStorage */ }
    }
    raiz.dataset[clave] = valor || porDefecto;
  });
  if (raiz.dataset.tema === 'sistema') raiz.dataset.tema = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}
aplicarPreferencias();
