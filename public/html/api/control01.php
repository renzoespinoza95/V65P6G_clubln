<?php
// Subcontrolador: consultas auxiliares para la web V65P6G

// Marca con la que se guardan las reservas web en pos_cliente (campo puesto)
if (!defined('V65_PUESTO_RESERVA')) define('V65_PUESTO_RESERVA', 'RESERVA WEB');

function v65_estilo($e) {
    $e = str_pad(preg_replace('/\D/', '', $e), 2, '0', STR_PAD_LEFT);
    return ($e >= '01' && $e <= '12') ? $e : '01';
}

function v65_lista_estilos($actual) {
    $n = ['Solar Pop','Luxury Gold','Ocean Fresh','Purple Dream','Natural Green','Urban Black','Pink Bloom','Tropical Sun','Royal Blue','Earth Terracotta','Neon Energy','Chocolate Cream'];
    $r = [];
    foreach ($n as $i => $nombre) {
        $id = sprintf('%02d', $i + 1);
        $r[] = ['id' => $id, 'nombre' => $nombre, 'act' => $id === $actual ? 'active' : ''];
    }
    return $r;
}

// Inicia la sesión PHP si aún no está activa
function v65_sesion() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
}

// Cuerpo de la petición: JSON (axios) o formulario
function v65_body() {
    $j = json_decode(file_get_contents('php://input'), true);
    return is_array($j) ? $j : $_POST;
}

// Compara la clave guardada (texto plano o bcrypt) con la ingresada
function v65_clave_ok($guardada, $ingresada) {
    if ($guardada === '') return false;
    if (strpos($guardada, '$2y$') === 0) return password_verify($ingresada, $guardada);
    return hash_equals($guardada, $ingresada);
}

// Valida y normaliza los datos de la reserva. Devuelve [datos|null, mensaje_error]
function v65_validar_reserva($b) {
    $nom = trim(preg_replace('/\s+/u', ' ', (string)(isset($b['nombres_apellidos']) ? $b['nombres_apellidos'] : '')));
    $dni = strtoupper(trim((string)(isset($b['dni']) ? $b['dni'] : '')));
    $fec = trim((string)(isset($b['fecha_nacimiento']) ? $b['fecha_nacimiento'] : ''));
    $cel = preg_replace('/\D/', '', (string)(isset($b['celular']) ? $b['celular'] : ''));

    $len = mb_strlen($nom, 'UTF-8');
    if ($len < 5 || $len > 150) return [null, 'Ingresa tus nombres y apellidos completos.'];
    if (!preg_match('/^[0-9A-Z]{8,12}$/', $dni)) return [null, 'Ingresa un DNI (8 dígitos) o carnet de extranjería válido.'];

    $d = DateTime::createFromFormat('!Y-m-d', $fec);
    if (!$d || $d->format('Y-m-d') !== $fec || $d > new DateTime('today') || (int)$d->format('Y') < 1900) {
        return [null, 'Ingresa una fecha de nacimiento válida.'];
    }
    if (!preg_match('/^\d{9}$/', $cel)) return [null, 'Ingresa un celular de 9 dígitos.'];

    return [['nombres_apellidos' => $nom, 'dni' => $dni, 'fecha_nacimiento' => $fec, 'celular' => $cel], ''];
}

// Un campo de un ítem por clave_txt y neg_id
function v65_txt($clave, $neg_id, $campo = 'contenido', $def = '') {
    $campo = in_array($campo, ['contenido', 'titulo', 'subtitulo_detalle']) ? $campo : 'contenido';
    $r = DB::queryFirstRow("SELECT i.$campo AS v FROM reg_item_pag_web i
        INNER JOIN reg_subcat_pag_web s ON i.subcat_pag_web_id = s.subcat_pag_web_id
        INNER JOIN reg_cat_pag_web c ON s.cat_pag_web_id = c.cat_pag_web_id
        WHERE i.clave_txt = %s AND c.neg_id = %i LIMIT 1", $clave, $neg_id);
    return ($r && trim($r['v']) !== '') ? $r['v'] : $def;
}

// SEO: primero subcategoría (texto01), luego ítem
function v65_meta($clave, $neg_id, $def) {
    $r = DB::queryFirstRow("SELECT s.texto01 AS v FROM reg_subcat_pag_web s
        INNER JOIN reg_cat_pag_web c ON s.cat_pag_web_id = c.cat_pag_web_id
        WHERE s.clave_txt = %s AND c.neg_id = %i LIMIT 1", $clave, $neg_id);
    if ($r && trim($r['v']) !== '') return $r['v'];
    return v65_txt($clave, $neg_id, 'contenido', $def);
}

// Ítems de una subcategoría (por clave_txt) con su primera imagen
function v65_lista($subcat, $neg_id, $limite, $def = []) {
    $rows = DB::query("SELECT i.item_pag_web_id, i.titulo, i.subtitulo_detalle, i.contenido,
        (SELECT url_img FROM reg_pag_item_img WHERE item_pag_web_id = i.item_pag_web_id AND is_visible = 1 ORDER BY orden LIMIT 1) AS img
        FROM reg_item_pag_web i
        INNER JOIN reg_subcat_pag_web s ON i.subcat_pag_web_id = s.subcat_pag_web_id
        INNER JOIN reg_cat_pag_web c ON s.cat_pag_web_id = c.cat_pag_web_id
        WHERE s.clave_txt = %s AND c.neg_id = %i AND i.is_visible = 1
        ORDER BY i.orden ASC, i.item_pag_web_id ASC LIMIT %i", $subcat, $neg_id, $limite);
    if (empty($rows)) $rows = $def;
    $out = [];
    foreach ($rows as $i => $r) {
        $out[] = [
            'num' => sprintf('%02d', $i + 1),
            'titulo' => isset($r['titulo']) ? $r['titulo'] : '',
            'subtitulo' => isset($r['subtitulo_detalle']) ? $r['subtitulo_detalle'] : (isset($r['subtitulo']) ? $r['subtitulo'] : ''),
            'contenido' => isset($r['contenido']) ? $r['contenido'] : '',
            'img' => !empty($r['img']) ? $r['img'] : 'https://picsum.photos/seed/v65' . md5($subcat . $i) . '/200/200',
        ];
    }
    return $out;
}

// Categorías + productos (pivote pos_product_category)
function v65_menu($neg_id) {
    $cats = DB::query("SELECT category_id, name FROM pos_category WHERE neg_id = %i AND borrado_el IS NULL AND is_activo = 1 ORDER BY priority, category_id LIMIT 6", $neg_id);
    $prods = DB::query("SELECT p.product_id, p.name, p.price, p.description,
        (SELECT img FROM pos_product_image WHERE product_id = p.product_id AND borrado_el IS NULL AND is_visible = 1 ORDER BY orden LIMIT 1) AS img,
        (SELECT GROUP_CONCAT(pc.category_id SEPARATOR ' ') FROM pos_product_category pc WHERE pc.product_id = p.product_id AND pc.borrado_el IS NULL) AS cats
        FROM pos_product p WHERE p.neg_id = %i AND p.borrado_el IS NULL AND p.is_visible = 1 ORDER BY p.product_id LIMIT 12", $neg_id);

    $categorias = [];
    foreach ($cats as $c) $categorias[] = ['id' => $c['category_id'], 'name' => $c['name']];

    $productos = [];
    foreach ($prods as $p) {
        $productos[] = [
            'name' => $p['name'], 'description' => mb_strimwidth((string)$p['description'], 0, 120, '…', 'UTF-8'),
            'precio' => 'S/ ' . number_format($p['price'], 2),
            'cats' => (string)$p['cats'],
            'img' => !empty($p['img']) ? $p['img'] : 'https://picsum.photos/seed/v65p' . $p['product_id'] . '/600/800',
        ];
    }
    return ['categorias' => $categorias, 'productos' => $productos, 'destacados' => array_slice($productos, 0, 2)];
}

// URL de Vimeo / YouTube (o código iframe) -> URL embebible. Vacío si no es válida
function v65_video_embed($url) {
    $url = trim((string)$url);
    if ($url === '') return '';
    if (preg_match('~src=["\']([^"\']+)~i', $url, $m)) $url = html_entity_decode($m[1]);

    if (preg_match('~vimeo\.com/(?:video/)?(\d+)(?:/([a-f0-9]+))?~i', $url, $m)) {
        $e = 'https://player.vimeo.com/video/' . $m[1];
        $h = !empty($m[2]) ? $m[2] : (preg_match('~[?&]h=([a-f0-9]+)~i', $url, $q) ? $q[1] : '');
        return $h !== '' ? $e . '?h=' . $h : $e;
    }
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    return '';
}

// Videos: tabs = ítems de txt_video_eventos; contenido = reg_pag_item_vid de cada ítem
function v65_videos($neg_id) {
    $items = DB::query("SELECT i.item_pag_web_id, i.titulo FROM reg_item_pag_web i
        INNER JOIN reg_subcat_pag_web s ON i.subcat_pag_web_id = s.subcat_pag_web_id
        INNER JOIN reg_cat_pag_web c ON s.cat_pag_web_id = c.cat_pag_web_id
        WHERE s.clave_txt = %s AND c.neg_id = %i AND i.is_visible = 1 AND i.borrado_el IS NULL
        ORDER BY i.orden ASC, i.item_pag_web_id ASC", 'txt_video_eventos', $neg_id);

    $tabs = [];
    foreach ($items as $it) {
        $vids = DB::query("SELECT pag_item_vid_id, titulo, codigo_web, url_img FROM reg_pag_item_vid
            WHERE item_pag_web_id = %i AND is_visible = 1 ORDER BY orden ASC, pag_item_vid_id ASC", $it['item_pag_web_id']);
        $lista = [];
        foreach ($vids as $v) {
            $embed = v65_video_embed($v['codigo_web']);
            if ($embed === '') continue;
            $lista[] = [
                'titulo' => trim((string)$v['titulo']) !== '' ? $v['titulo'] : $it['titulo'],
                'embed' => $embed,
                'img' => (string)$v['url_img'],
                'tiene_img' => trim((string)$v['url_img']) !== '',
            ];
        }
        if (!$lista) continue;
        $tabs[] = [
            'id' => $it['item_pag_web_id'], 'titulo' => $it['titulo'],
            'act' => $tabs ? '' : 'active', 'ocultar' => $tabs ? 'd-none' : '',
            'videos' => $lista,
        ];
    }
    return $tabs;
}