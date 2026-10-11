// Sin defer: fija el tema y los atajos antes de pintar, sin parpadeo.
(() => {
  let t = null;
  let a = null;
  try { t = localStorage.getItem('tema'); a = localStorage.getItem('atajos'); } catch (e) {}
  document.documentElement.dataset.tema = t || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  document.documentElement.dataset.atajos = a || 'si';
})();
