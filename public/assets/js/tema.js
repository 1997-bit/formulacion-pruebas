// Sin defer: fija el tema antes de pintar, sin parpadeo.
(() => {
  let t = null;
  try { t = localStorage.getItem('tema'); } catch (e) {}
  document.documentElement.dataset.tema = t || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
})();
