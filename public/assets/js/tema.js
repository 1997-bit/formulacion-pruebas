// Sin defer: aplica los ajustes antes de pintar, sin parpadeo. app.js la llama al cambiar un ajuste.
// <html data-tema data-densidad data-atajos data-movimiento data-subrayar data-paleta>: el CSS y los scripts leen de ahí.
const AJUSTES = { tema: 'sistema', densidad: 'comoda', atajos: 'si', movimiento: 'sistema', subrayar: 'no', paleta: 'si' };
function aplicarPreferencias() {
  const raiz = document.documentElement;
  Object.entries(AJUSTES).forEach(([clave, porDefecto]) => {
    let valor = null;
    try { valor = localStorage.getItem(clave); } catch (e) {}
    raiz.dataset[clave] = valor || porDefecto;
  });
  if (raiz.dataset.tema === 'sistema') raiz.dataset.tema = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}
aplicarPreferencias();
