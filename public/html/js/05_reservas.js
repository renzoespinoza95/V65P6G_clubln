// Reservaciones: formulario del modal + diálogo de éxito
function v65ReservaInit() {
  const f = document.getElementById('formReserva');
  if (!f) return;

  // La fecha de nacimiento no puede ser futura
  const h = new Date();
  f.fecha_nacimiento.max = h.getFullYear() + '-' + String(h.getMonth() + 1).padStart(2, '0') + '-' + String(h.getDate()).padStart(2, '0');

  f.addEventListener('submit', v65Reservar);

  // Al abrir el modal desde el drawer, cerrar primero el drawer
  document.querySelectorAll('#menuOff [data-bs-toggle="modal"]').forEach(a => {
    a.addEventListener('click', () => {
      const inst = bootstrap.Offcanvas.getInstance(document.getElementById('menuOff'));
      if (inst) inst.hide();
    });
  });

  // Limpiar el formulario al cerrar el modal
  document.getElementById('modalReserva').addEventListener('hidden.bs.modal', () => {
    f.reset();
    f.classList.remove('was-validated');
    document.getElementById('reservaError').classList.add('d-none');
  });
}

function v65Reservar(e) {
  e.preventDefault();
  const f = e.target;
  const btn = document.getElementById('btnReservar');
  const err = document.getElementById('reservaError');
  const spin = btn.querySelector('.spinner-border');
  err.classList.add('d-none');

  if (!f.checkValidity()) { f.classList.add('was-validated'); return; }

  const datos = {
    nombres_apellidos: f.nombres_apellidos.value.trim(),
    dni: f.dni.value.trim(),
    fecha_nacimiento: f.fecha_nacimiento.value,
    celular: f.celular.value.trim()
  };

  btn.disabled = true;
  spin.classList.remove('d-none');

  axios.post(v65Api('/BGYHC6/realizarReserva'), datos)
    .then(r => {
      if (!r.data || !r.data.ok) throw { response: { data: r.data } };
      const modalEl = document.getElementById('modalReserva');
      modalEl.addEventListener('hidden.bs.modal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalReservaOk')).show();
      }, { once: true });
      bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    })
    .catch(ex => {
      const d = ex && ex.response && ex.response.data;
      err.textContent = (d && d.msg) ? d.msg : 'No se pudo registrar la reserva. Inténtalo nuevamente.';
      err.classList.remove('d-none');
    })
    .finally(() => {
      btn.disabled = false;
      spin.classList.add('d-none');
    });
}