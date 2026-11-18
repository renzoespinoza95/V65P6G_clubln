document.addEventListener('DOMContentLoaded', () => {
  V65.api = document.body.dataset.api || '';

  v65Mostrar(0);

  const auto = () => { clearInterval(V65.timer); V65.timer = setInterval(() => v65Mostrar(V65.idx + 1), 6000); };
  auto();

  document.getElementById('hPrev').addEventListener('click', () => { v65Mostrar(V65.idx - 1); auto(); });
  document.getElementById('hNext').addEventListener('click', () => { v65Mostrar(V65.idx + 1); auto(); });

  // Swipe táctil
  const zona = document.querySelector('.hero-slides');
  zona.addEventListener('touchstart', e => { V65.touchX = e.touches[0].clientX; }, { passive: true });
  zona.addEventListener('touchend', e => {
    if (V65.touchX === null) return;
    const dx = e.changedTouches[0].clientX - V65.touchX;
    if (Math.abs(dx) > 50) { v65Mostrar(V65.idx + (dx < 0 ? 1 : -1)); auto(); }
    V65.touchX = null;
  });

  // Tabs del catálogo (Galería)
  document.querySelectorAll('#menu .cat-tab').forEach(t => t.addEventListener('click', () => v65CatTab(t.dataset.cattab)));

  // Galería de catálogo: clic en la imagen del ítem
  document.getElementById('menu').addEventListener('click', e => {
    const b = e.target.closest('.prod-img-btn');
    if (b) v65GaleriaAbrir(b.closest('.menu-item'));
  });
  document.getElementById('galPrev').addEventListener('click', () => v65GaleriaVer(V65.galIdx - 1));
  document.getElementById('galNext').addEventListener('click', () => v65GaleriaVer(V65.galIdx + 1));

  const gal = document.getElementById('modalGaleria');
  document.addEventListener('keydown', e => {
    if (!gal.classList.contains('show')) return;
    if (e.key === 'ArrowLeft') v65GaleriaVer(V65.galIdx - 1);
    if (e.key === 'ArrowRight') v65GaleriaVer(V65.galIdx + 1);
  });
  let gx = null;
  gal.addEventListener('touchstart', e => { gx = e.touches[0].clientX; }, { passive: true });
  gal.addEventListener('touchend', e => {
    if (gx === null) return;
    const dx = e.changedTouches[0].clientX - gx;
    if (Math.abs(dx) > 50) v65GaleriaVer(V65.galIdx + (dx < 0 ? 1 : -1));
    gx = null;
  });

  // Videos: tabs y reproducción al hacer clic en la card
  document.querySelectorAll('#videos .vtab').forEach(t => t.addEventListener('click', () => v65VideoTab(t.dataset.vtab)));
  document.querySelectorAll('#videos .vid-frame').forEach(box => {
    box.addEventListener('click', () => v65VideoPlay(box));
    box.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); v65VideoPlay(box); } });
  });

  // Reservaciones
  v65ReservaInit();
});