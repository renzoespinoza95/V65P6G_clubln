// Página misReservas: login + listado con DataTables
const MR = { api: '', tabla: null };

function mrEsc(s) {
  return String(s === null || s === undefined ? '' : s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function mrMostrarLogin() {
  document.getElementById('panelReservas').classList.add('d-none');
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalLogin')).show();
}

function mrCargar() {
  return axios.get(MR.api + '/FME9Uc/misReservas/datos')
    .then(r => {
      if (!r.data || !r.data.ok) throw { response: { status: 500 } };
      const filas = r.data.data || [];
      if (MR.tabla) {
        MR.tabla.clear().rows.add(filas).draw();
        return;
      }
      MR.tabla = $('#tblReservas').DataTable({
        data: filas,
        order: [[0, 'desc']],
        columns: [
          { data: 'cliente_id' },
          { data: 'nombres_apellidos', render: d => mrEsc(d) },
          { data: 'dni', render: d => mrEsc(d) },
          { data: 'fecha_nacimiento', render: d => mrEsc(d) },
          { data: 'celular', render: d => {
              const n = String(d || '').replace(/\D/g, '');
              if (!n) return '';
              return '<a href="https://wa.me/51' + mrEsc(n) + '" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> ' + mrEsc(n) + '</a>';
            } }
        ],
        language: {
          search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ reservas',
          info: 'Mostrando _START_ a _END_ de _TOTAL_ reservas', infoEmpty: 'Sin reservas',
          infoFiltered: '(filtrado de _MAX_)', zeroRecords: 'No se encontraron reservas',
          emptyTable: 'Aún no hay reservas',
          paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
        }
      });
    })
    .catch(ex => {
      if (ex && ex.response && ex.response.status === 401) mrMostrarLogin();
      else alert('No se pudo cargar el listado de reservas.');
    });
}

function mrLogin(e) {
  e.preventDefault();
  const f = e.target;
  const err = document.getElementById('loginError');
  const btn = document.getElementById('btnLogin');
  const spin = btn.querySelector('.spinner-border');
  err.classList.add('d-none');

  const usuario = f.usuario.value.trim();
  const clave = f.clave.value;
  if (!usuario || !clave) {
    err.textContent = 'Ingresa tu usuario y contraseña.';
    err.classList.remove('d-none');
    return;
  }

  btn.disabled = true;
  spin.classList.remove('d-none');

  axios.post(MR.api + '/R1W4ry/miUsuario', { usuario: usuario, clave: clave })
    .then(r => {
      if (!r.data || !r.data.ok) throw { response: { data: r.data } };
      f.reset();
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modalLogin')).hide();
      document.getElementById('panelReservas').classList.remove('d-none');
      mrCargar();
    })
    .catch(ex => {
      const d = ex && ex.response && ex.response.data;
      err.textContent = (d && d.msg) ? d.msg : 'No se pudo iniciar sesión. Inténtalo nuevamente.';
      err.classList.remove('d-none');
    })
    .finally(() => {
      btn.disabled = false;
      spin.classList.add('d-none');
    });
}

document.addEventListener('DOMContentLoaded', () => {
  MR.api = document.body.dataset.api || '';
  document.getElementById('formLogin').addEventListener('submit', mrLogin);
  document.getElementById('btnActualizar').addEventListener('click', mrCargar);

  if (document.body.dataset.logueado === '1') {
    document.getElementById('panelReservas').classList.remove('d-none');
    mrCargar();
  } else {
    mrMostrarLogin();
  }
});