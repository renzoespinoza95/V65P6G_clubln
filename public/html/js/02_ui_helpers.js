// Utilidades de interfaz
function v65Vacio() {
  const hay = document.querySelector('#menuGrid .menu-item:not(.d-none)');
  document.getElementById('menuVacio').classList.toggle('d-none', !!hay);
}

// URL completa de un endpoint
function v65Api(ruta) {
  return (V65.api || '') + ruta;
}