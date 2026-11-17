// Estado global de la aplicación
const V65 = {
  idx: 0,          // slide activo del hero
  total: 0,        // total de slides
  timer: null,     // autoplay
  filtro: 'all',   // categoría activa del menú
  touchX: null,    // inicio del gesto swipe
  galIdx: 0,       // imagen activa de la galería
  galItems: [],    // imágenes de la galería (productos visibles)
  api: ''          // base de los endpoints
};