// Manejo de tarjetas: hero (carrusel infinito), filtro del menú, galería y videos
function v65Mostrar(i) {
  const slides = document.querySelectorAll('.hero-slide');
  V65.total = slides.length;
  if (!V65.total) return;
  V65.idx = (i + V65.total) % V65.total; // infinito
  slides.forEach((s, k) => s.classList.toggle('active', k === V65.idx));
}

function v65Filtrar(f) {
  V65.filtro = f;
  document.querySelectorAll('#menu .menu-tabs .tab').forEach(t => t.classList.toggle('active', t.dataset.filter === f));
  document.querySelectorAll('#menuGrid .menu-item').forEach(el => {
    const ids = el.dataset.cats.split(' ');
    el.classList.toggle('d-none', f !== 'all' && !ids.includes(f));
  });
  v65Vacio();
}

// ===== Galería a pantalla completa (productos visibles) =====
function v65GaleriaVer(i) {
  const n = V65.galItems.length;
  if (!n) return;
  V65.galIdx = (i + n) % n; // infinito
  const it = V65.galItems[V65.galIdx];
  const img = document.getElementById('galImg');
  img.src = it.src;
  img.alt = it.name;
  document.getElementById('galCap').textContent = it.name + (n > 1 ? '  (' + (V65.galIdx + 1) + '/' + n + ')' : '');
  document.getElementById('galPrev').classList.toggle('d-none', n < 2);
  document.getElementById('galNext').classList.toggle('d-none', n < 2);
}

function v65GaleriaAbrir(card) {
  const visibles = Array.from(document.querySelectorAll('#menuGrid .menu-item:not(.d-none)'));
  V65.galItems = visibles.map(el => ({
    src: el.querySelector('img').getAttribute('src'),
    name: (el.querySelector('h6') || {}).textContent || ''
  }));
  v65GaleriaVer(visibles.indexOf(card));
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalGaleria')).show();
}

// ===== Videos =====
function v65VideoDetener() {
  document.querySelectorAll('#videos .vid-frame[data-playing]').forEach(box => {
    box.innerHTML = box._orig || '';
    box.removeAttribute('data-playing');
  });
}

function v65VideoPlay(box) {
  const src = box.dataset.embed;
  if (!src || box.dataset.playing) return;
  box._orig = box.innerHTML;
  box.dataset.playing = '1';
  const sep = src.indexOf('?') === -1 ? '?' : '&';
  box.innerHTML = '<iframe src="' + src + sep + 'autoplay=1" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen title="Video"></iframe>';
}

function v65VideoTab(id) {
  v65VideoDetener();
  document.querySelectorAll('#videos .vtab').forEach(t => t.classList.toggle('active', t.dataset.vtab === id));
  document.querySelectorAll('#videos .vpanel').forEach(p => p.classList.toggle('d-none', p.dataset.vpanel !== id));
}