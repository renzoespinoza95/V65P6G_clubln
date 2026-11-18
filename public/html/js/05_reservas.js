// Reservaciones: formulario del modal + diálogo de éxito
function v65ReservaInit() {
  const f = document.getElementById('formReserva');
  if (!f) return;

  // La fecha de nacimiento no puede ser futura
  const h = new Date();
  f.fecha_nacimiento.max = h.getFullYear() + '-' + String(h.getMonth() + 1).padStart(2, '0') + '-' + String(h.getDate()).padStart(2, '0');

  // Lógica del Captcha de Frutas
  let captchaValido = false;
  const objetivoEl = document.getElementById('captchaObjetivo');
  const frutaObjetivo = objetivoEl ? objetivoEl.dataset.fruta : '';
  const btnReservar = document.getElementById('btnReservar');
  const feedbackEl = document.getElementById('captchaFeedback');

  document.querySelectorAll('.captcha-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.captcha-btn').forEach(b => b.classList.remove('active', 'border-primary', 'bg-light'));
      const seleccionada = btn.dataset.fruta;
      if (seleccionada === frutaObjetivo) {
        captchaValido = true;
        btn.classList.add('active', 'border-success', 'bg-success-subtle');
        feedbackEl.classList.add('d-none');
        btnReservar.removeAttribute('disabled');
      } else {
        captchaValido = false;
        btn.classList.add('active', 'border-danger', 'bg-danger-subtle');
        feedbackEl.textContent = 'Fruta incorrecta. Inténtalo de nuevo.';
        feedbackEl.classList.remove('d-none');
        btnReservar.setAttribute('disabled', 'true');
      }
    });
  });

  f.addEventListener('submit', (e) => {
    if (!captchaValido) {
      e.preventDefault();
      feedbackEl.textContent = 'Debes seleccionar la fruta correcta para habilitar el botón.';
      feedbackEl.classList.remove('d-none');
      return;
    }
    v65Reservar(e, captchaValido);
  });

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
    document.querySelectorAll('.captcha-btn').forEach(b => b.classList.remove('active', 'border-success', 'border-danger', 'bg-success-subtle', 'bg-danger-subtle'));
    feedbackEl.classList.add('d-none');
    captchaValido = false;
    btnReservar.setAttribute('disabled', 'true');
  });
}

function v65Reservar(e, captchaValido) {
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
    celular: f.celular.value.trim(),
    captcha_valido: captchaValido
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