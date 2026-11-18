<?php
include_once __DIR__ . '/api/control01.php';

Flight::route('GET /', function () {
    include DEFINITION;

    $base    = isset($varhost) ? $varhost : '';
    $apphost = isset($apphost) ? $apphost : $base;
    $version = isset($version) ? $version : date('Ymd');
    $vista   = __DIR__;
    $estilo  = v65_estilo(isset($_GET['estilo']) ? $_GET['estilo'] : '01');

    perso::visita();

    // ===== Negocio =====
    $negocio = DB::queryFirstRow("SELECT neg_id, nombre, img_logo, celular_informes, direccion FROM reg_neg WHERE neg_id = %i LIMIT 1", $neg_id);
    $nombre_negocio = !empty($negocio['nombre']) ? $negocio['nombre'] : 'CocinaPro';
    $img_logo = !empty($negocio['img_logo']) ? $negocio['img_logo'] : 'https://picsum.photos/seed/v65logo/120/120';
    $celular  = isset($negocio['celular_informes']) ? $negocio['celular_informes'] : '';

    // ===== SEO / redes / WhatsApp (mismas claves txt_ del proyecto base) =====
    $meta_description = v65_meta('txt_meta_description', $neg_id, 'Equipamiento profesional para cocinas y restaurantes.');
    $meta_keywords    = v65_meta('txt_meta_keywords', $neg_id, 'cocinas industriales, refrigeración, menaje');
    $telefono_wa      = v65_txt('txt_telefono', $neg_id, 'contenido', $celular);
    $cel_limpio       = preg_replace('/[^0-9]/', '', $telefono_wa);
    if (strlen($cel_limpio) === 9) $cel_limpio = '51' . $cel_limpio;
    $msg = v65_txt('txt_whatsapp', $neg_id, 'contenido', "Hola {$nombre_negocio}, los contacto desde su página web.");
    $url_whatsapp = 'https://wa.me/' . $cel_limpio . '?text=' . urlencode($msg);
    $url_tiktok   = v65_txt('txt_tiktok', $neg_id, 'contenido', '#');
    $url_facebook = v65_txt('txt_facebook', $neg_id, 'contenido', '#');
    $nombre_dueno = v65_txt('txt_dueno_restaurante', $neg_id, 'contenido', 'Equipo ' . $nombre_negocio);

    // ===== Reservaciones (modal) =====
    $reserva_texto = v65_txt('txt_reservaciones', $neg_id, 'contenido', 'Reserva con anticipación y asegura tu lugar. Déjanos tus datos y nos pondremos en contacto contigo.');
    $reserva_img   = v65_reserva_imagen($neg_id);

    // ===== Fruta Captcha =====
    $frutas_disponibles = [
        ['nombre' => 'manzana', 'emoji' => '🍎'],
        ['nombre' => 'plátano', 'emoji' => '🍌'],
        ['nombre' => 'uva', 'emoji' => '🍇'],
        ['nombre' => 'fresa', 'emoji' => '🍓'],
        ['nombre' => 'sandía', 'emoji' => '🍉']
    ];
    shuffle($frutas_disponibles);
    $fruta_objetivo = $frutas_disponibles[0];
    $opciones_captcha = $frutas_disponibles;
    shuffle($opciones_captcha);

    // ===== Nosotros =====
    $nos = DB::queryFirstRow("SELECT i.titulo, i.subtitulo_detalle, i.contenido FROM reg_item_pag_web i
        INNER JOIN reg_subcat_pag_web s ON i.subcat_pag_web_id = s.subcat_pag_web_id
        INNER JOIN reg_cat_pag_web c ON s.cat_pag_web_id = c.cat_pag_web_id
        WHERE i.clave_txt IN ('txt_nosotros','txt_historia_titulo') AND c.neg_id = %i ORDER BY i.clave_txt = 'txt_nosotros' DESC LIMIT 1", $neg_id);
    if (!$nos) $nos = ['titulo' => 'Nosotros', 'subtitulo_detalle' => 'Más de 15 años en el rubro', 'contenido' => 'Equipamos restaurantes y negocios gastronómicos en todo el país.'];

    // ===== Sliders (hero) =====
    $sl = DB::query("SELECT img, titulo_superior, descripcion FROM reg_slider WHERE neg_id = %i AND is_visible = 1 ORDER BY orden ASC", $neg_id);
    if (empty($sl)) {
        $sl = [
            ['img' => 'https://picsum.photos/seed/v65h1/900/700', 'titulo_superior' => 'Equipa tu restaurante', 'descripcion' => 'Cocinas, refrigeración y menaje con garantía.'],
            ['img' => 'https://picsum.photos/seed/v65h2/900/700', 'titulo_superior' => 'Ofertas de temporada', 'descripcion' => 'Descuentos en freidoras, planchas y mesas.'],
        ];
    }
    $sliders = [];
    foreach ($sl as $i => $s) {
        $sliders[] = ['img' => $s['img'], 'titulo_superior' => $s['titulo_superior'], 'descripcion' => $s['descripcion'], 'idx' => $i, 'activo' => $i === 0 ? 'active' : ''];
    }

    // ===== Menú superior (TXT_TOPNAVBAR) =====
    $rows = DB::query("SELECT i.item_pag_web_id, i.titulo, i.url_amigable FROM reg_item_pag_web i
        INNER JOIN reg_subcat_pag_web s ON i.subcat_pag_web_id = s.subcat_pag_web_id
        INNER JOIN reg_cat_pag_web c ON s.cat_pag_web_id = c.cat_pag_web_id
        WHERE s.clave_txt = %s AND c.neg_id = %i ORDER BY i.orden ASC, i.item_pag_web_id ASC LIMIT 6", 'TXT_TOPNAVBAR', $neg_id);
    if ($rows) {
        $nav = array_map(function ($r) use ($apphost) {
            if (mb_strtolower(trim($r['titulo']), 'UTF-8') === 'cotizar') {
                return ['titulo' => 'Reservaciones', 'url' => '#', 'modal' => '#modalReserva'];
            }
            return ['titulo' => $r['titulo'], 'url' => $apphost . '/item/' . $r['item_pag_web_id'] . '-' . $r['url_amigable'], 'modal' => ''];
        }, $rows);
    } else {
        $nav = [
            ['titulo' => 'Inicio', 'url' => '#inicio', 'modal' => ''],
            ['titulo' => 'Nosotros', 'url' => '#', 'modal' => '#modalNosotros'],
            ['titulo' => 'Menú', 'url' => '#menu', 'modal' => ''],
            ['titulo' => 'Servicios', 'url' => '#servicios', 'modal' => ''],
            ['titulo' => 'Clientes', 'url' => '#', 'modal' => '#modalClientes'],
            ['titulo' => 'Contacto', 'url' => '#contacto', 'modal' => ''],
        ];
    }

    // ===== URL Mapa (contenido de clave_txt = txt_mapa) =====
    $url_mapa = trim(v65_txt('txt_mapa', $neg_id, 'contenido', ''));

    // ===== Datos de catálogo (Galería de fotos) y auxiliares =====
    $menu = v65_menu($neg_id);
    $catalogo_tabs = v65_catalogo_galeria($neg_id);
    $videos = v65_videos($neg_id);
    $contactos = [
        ['icono' => 'bi-geo-alt',  'titulo' => 'Dirección', 'contenido' => v65_txt('txt_direccion', $neg_id, 'contenido', isset($negocio['direccion']) ? $negocio['direccion'] : 'Lima, Perú'), 'url_boton' => $url_mapa, 'texto_boton' => 'Ver ubicación'],
        ['icono' => 'bi-telephone', 'titulo' => 'Teléfono',  'contenido' => $telefono_wa, 'url_boton' => '', 'texto_boton' => ''],
        ['icono' => 'bi-clock',     'titulo' => 'Horario',   'contenido' => v65_txt('txt_horario', $neg_id, 'contenido', 'Lunes a sábado 8:00 a 18:00'), 'url_boton' => '', 'texto_boton' => ''],
        ['icono' => 'bi-envelope',  'titulo' => 'Correo',    'contenido' => v65_txt('txt_email', $neg_id, 'contenido', 'ventas@ejemplo.com'), 'url_boton' => '', 'texto_boton' => ''],
    ];

    $data = [
        'title' => $nombre_negocio, 'logo' => $img_logo, 'favicon' => $img_logo,
        'assets' => $base . '/public/html', 'apphost' => $apphost, 'version' => $version, 'estilo' => $estilo,
        'estilos' => v65_lista_estilos($estilo),
        'meta_description' => $meta_description, 'meta_keywords' => $meta_keywords,
        'nombre_negocio' => $nombre_negocio, 'nombre_dueno' => $nombre_dueno,
        'celular' => $telefono_wa, 'url_whatsapp' => $url_whatsapp, 'url_tiktok' => $url_tiktok, 'url_facebook' => $url_facebook,
        'direccion' => $contactos[0]['contenido'], 'horario' => $contactos[2]['contenido'],
        'anio' => date('Y'), 'visitas' => perso::obtener_visitas(),
        'nav' => $nav, 'sliders' => $sliders, 'nosotros' => $nos,
        'reserva_img' => $reserva_img, 'reserva_texto' => $reserva_texto,
        'captcha_fruta_objetivo' => $fruta_objetivo['nombre'],
        'captcha_fruta_emoji' => $fruta_objetivo['emoji'],
        'captcha_opciones' => $opciones_captcha,
        'beneficios' => v65_lista('txt_marquesina', $neg_id, 3, [
            ['titulo' => 'Pide tu equipo', 'contenido' => 'Envíos a todo el Perú'],
            ['titulo' => 'Entrega o recojo', 'contenido' => 'Despacho rápido a tu local'],
            ['titulo' => 'Garantía', 'contenido' => 'Hasta 24 meses']]),
        'servicios' => v65_lista('txt_servicios_lista', $neg_id, 4, [
            ['titulo' => 'Diseño', 'contenido' => 'Planos de cocina'], ['titulo' => 'Instalación', 'contenido' => 'Técnicos certificados'],
            ['titulo' => 'Mantenimiento', 'contenido' => 'Repuestos originales'], ['titulo' => 'Financiamiento', 'contenido' => 'Cuotas flexibles']]),
        'mision' => v65_txt('txt_mision', $neg_id, 'contenido', 'Brindar equipamiento duradero y eficiente.'),
        'vision' => v65_txt('txt_vision', $neg_id, 'contenido', 'Ser líderes en equipamiento gastronómico.'),
        'valores' => v65_lista('txt_valores', $neg_id, 4, [['titulo' => 'Calidad', 'contenido' => 'Acero certificado']]),
        'clientes' => v65_lista('txt_testimonios', $neg_id, 6, [['titulo' => 'Cliente', 'subtitulo' => 'Propietario', 'contenido' => 'Excelente servicio.']]),
        'destacados' => $menu['destacados'],
        'catalogo_tabs' => $catalogo_tabs,
        'hay_videos' => !empty($videos), 'video_tabs' => $videos,
        'contactos' => $contactos,
    ];

    $nombres = ['head','navbar_items','hero','beneficios','nosotros','thumbnails','item','videos','servicios','contacto','footer','modal_nosotros','modal_clientes','modal_reserva','modal_reserva_ok','modal_galeria','scripts'];
    $partials = [];
    foreach ($nombres as $n) $partials[$n] = file_get_contents($vista . '/comp_pag_web/' . $n . '.html');

    echo (new Mustache)->render(file_get_contents($vista . '/pag_web.html'), $data, $partials);
});

// ============================================================
// POST BGYHC6/realizarReserva  -> registra la reserva en pos_cliente
// ============================================================
Flight::route('POST /BGYHC6/realizarReserva', function () {
    include DEFINITION;

    $b = v65_body();
    if (!isset($b['captcha_valido']) || $b['captcha_valido'] !== true) {
        Flight::json(['ok' => false, 'msg' => 'Por favor completa el captcha seleccionando la fruta correcta.'], 422);
        return;
    }

    list($d, $error) = v65_validar_reserva($b);
    if ($error !== '') {
        Flight::json(['ok' => false, 'msg' => $error], 422);
        return;
    }

    try {
        DB::insert('pos_cliente', [
            'nombres_apellidos' => $d['nombres_apellidos'],
            'dni'               => $d['dni'],
            'celular'           => $d['celular'],
            'fecha_nacimiento'  => $d['fecha_nacimiento'],
            'neg_id'            => $neg_id,
            'is_activo'         => 1,
            'puesto'            => V65_PUESTO_RESERVA,
        ]);
    } catch (Exception $e) {
        Flight::json(['ok' => false, 'msg' => 'No se pudo registrar la reserva. Inténtalo nuevamente.'], 500);
        return;
    }

    Flight::json(['ok' => true, 'msg' => 'Reserva registrada. Nos pondremos en contacto contigo.', 'cliente_id' => DB::insertId()]);
});

// ============================================================
// POST R1W4ry/miUsuario  -> login (reg_usu, usu_id = 3: sobrenombre + clavel)
// ============================================================
Flight::route('POST /R1W4ry/miUsuario', function () {
    include DEFINITION;
    v65_sesion();

    $b = v65_body();
    $usuario = trim((string)(isset($b['usuario']) ? $b['usuario'] : ''));
    $clave   = (string)(isset($b['clave']) ? $b['clave'] : '');

    if ($usuario === '' || $clave === '') {
        Flight::json(['ok' => false, 'msg' => 'Ingresa tu usuario y contraseña.'], 422);
        return;
    }

    $u = DB::queryFirstRow("SELECT usu_id, sobrenombre, clavel FROM reg_usu WHERE usu_id = %i AND borrado_el IS NULL LIMIT 1", 3);
    $ok = $u && hash_equals((string)$u['sobrenombre'], $usuario) && v65_clave_ok((string)$u['clavel'], $clave);

    if (!$ok) {
        usleep(400000);
        Flight::json(['ok' => false, 'msg' => 'Usuario o contraseña incorrectos.'], 401);
        return;
    }

    session_regenerate_id(true);
    $_SESSION['v65_admin'] = (int)$u['usu_id'];
    Flight::json(['ok' => true]);
});

// ============================================================
// GET FME9Uc/misReservas  -> página con login + DataTables
// ============================================================
Flight::route('GET /FME9Uc/misReservas', function () {
    include DEFINITION;
    v65_sesion();

    $base    = isset($varhost) ? $varhost : '';
    $apphost = isset($apphost) ? $apphost : $base;
    $version = isset($version) ? $version : date('Ymd');
    $vista   = __DIR__;
    $estilo  = v65_estilo(isset($_GET['estilo']) ? $_GET['estilo'] : '01');

    $negocio = DB::queryFirstRow("SELECT nombre, img_logo FROM reg_neg WHERE neg_id = %i LIMIT 1", $neg_id);
    $nombre_negocio = !empty($negocio['nombre']) ? $negocio['nombre'] : 'CocinaPro';
    $img_logo = !empty($negocio['img_logo']) ? $negocio['img_logo'] : 'https://picsum.photos/seed/v65logo/120/120';
    $logueado = !empty($_SESSION['v65_admin']);

    $data = [
        'title' => 'Mis reservas · ' . $nombre_negocio, 'logo' => $img_logo, 'favicon' => $img_logo,
        'assets' => $base . '/public/html', 'apphost' => $apphost, 'version' => $version, 'estilo' => $estilo,
        'estilos' => v65_lista_estilos($estilo),
        'meta_description' => 'Listado de reservas de ' . $nombre_negocio, 'meta_keywords' => '',
        'nombre_negocio' => $nombre_negocio, 'anio' => date('Y'),
        'logueado' => $logueado ? '1' : '0', 'panel_clase' => $logueado ? '' : 'd-none',
    ];

    $partials = [];
    foreach (['head', 'modal_login'] as $n) $partials[$n] = file_get_contents($vista . '/comp_pag_web/' . $n . '.html');

    echo (new Mustache)->render(file_get_contents($vista . '/misreservas.html'), $data, $partials);
});

// ============================================================
// GET FME9Uc/misReservas/datos  -> JSON para DataTables (requiere login)
// ============================================================
Flight::route('GET /FME9Uc/misReservas/datos', function () {
    include DEFINITION;
    v65_sesion();

    if (empty($_SESSION['v65_admin'])) {
        Flight::json(['ok' => false, 'msg' => 'No autorizado'], 401);
        return;
    }

    $rows = DB::query("SELECT cliente_id, nombres_apellidos, dni, celular, fecha_nacimiento
        FROM pos_cliente WHERE neg_id = %i AND puesto = %s AND borrado_el IS NULL
        ORDER BY cliente_id DESC", $neg_id, V65_PUESTO_RESERVA);

    foreach ($rows as &$r) {
        $t = $r['fecha_nacimiento'] ? strtotime($r['fecha_nacimiento']) : false;
        $r['fecha_nacimiento'] = $t ? date('d/m/Y', $t) : '';
    }
    unset($r);

    Flight::json(['ok' => true, 'data' => $rows]);
});