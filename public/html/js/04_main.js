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

  // Modal detalle de productos destacados (sección Nosotros / Nuestros orígenes)
  const abrirModalProducto = (card) => {
    const nombre = card.dataset.name || '';
    const precio = card.dataset.precio || '';
    const desc = card.dataset.desc || '';
    let varJson = {};
    try {
      varJson = JSON.parse(card.dataset.variantes || '{}') || {};
    } catch (err) {
      varJson = {};
    }

    document.getElementById('modalProductoT').textContent = nombre;
    document.getElementById('prodModalNombre').textContent = nombre;
    document.getElementById('prodModalPrecio').textContent = precio;
    document.getElementById('prodModalDesc').textContent = desc || 'Sin descripción disponible.';

    const etqEl = document.getElementById('prodModalEtiqueta');
    if (varJson.etiqueta) {
      etqEl.textContent = varJson.etiqueta;
      etqEl.classList.remove('d-none');
    } else {
      etqEl.classList.add('d-none');
    }

    const pAntEl = document.getElementById('prodModalPrecioAnt');
    if (Number(varJson.precio_anterior) > 0) {
      pAntEl.textContent = 'S/ ' + Number(varJson.precio_anterior).toFixed(2);
      pAntEl.classList.remove('d-none');
    } else {
      pAntEl.classList.add('d-none');
    }

    const metaParts = [];
    if (varJson.rating) metaParts.push('★ ' + varJson.rating + (varJson.reviews ? ' (' + varJson.reviews + ' reseñas)' : ''));
    if (varJson.stock !== undefined && varJson.stock !== null) metaParts.push('Stock disponible: ' + varJson.stock);
    const metaEl = document.getElementById('prodModalMeta');
    if (metaParts.length) {
      metaEl.textContent = metaParts.join('  ·  ');
      metaEl.classList.remove('d-none');
    } else {
      metaEl.classList.add('d-none');
    }

    const especWrap = document.getElementById('prodModalEspecWrap');
    const especUl = document.getElementById('prodModalEspec');
    especUl.innerHTML = '';
    if (varJson.especificaciones && typeof varJson.especificaciones === 'object') {
      const entries = Object.entries(varJson.especificaciones);
      if (entries.length) {
        entries.forEach(([k, v]) => {
          const li = document.createElement('li');
          const b = document.createElement('b');
          b.textContent = k + ': ';
          li.appendChild(b);
          li.appendChild(document.createTextNode(String(v)));
          especUl.appendChild(li);
        });
        especWrap.classList.remove('d-none');
      } else {
        especWrap.classList.add('d-none');
      }
    } else {
      especWrap.classList.add('d-none');
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalProducto')).show();
  };

  document.querySelectorAll('#nosotros .card-dest').forEach(card => {
    card.style.cursor = 'pointer';
    card.addEventListener('click', () => abrirModalProducto(card));
    card.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        abrirModalProducto(card);
      }
    });
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