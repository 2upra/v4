<?php
// Componente de vista para renderizar y editar la presentación del perfil.

// Refactor(Org): Moved function presentacion_shortcode() and its hook from app/Perfiles/perfilmusic.php
function presentacion_shortcode($atts) {
    // Determina el usuario actual y el usuario de la URL, si es aplicable
    $current_user_id = get_current_user_id();  
    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $url_segments = explode('/', trim($url_path, '/'));
    $perfil_index = array_search('music', $url_segments);
    $user_id = $perfil_index !== false && isset($url_segments[$perfil_index + 1]) ? get_user_by('slug', $url_segments[$perfil_index + 1])->ID : null;

    // Recupera los valores guardados o utiliza los predeterminados si no existen
    $saved_text = get_user_meta($user_id, 'presentacion_texto', true);
    $saved_image = get_user_meta($user_id, 'presentacion_imagen', true);
    $atts = shortcode_atts(array(
        'texto' => $saved_text ?: 'Este es un texto de ejemplo blablabla, 1ndoryü tu patrona.',
        'imagen' => $saved_image ?: 'https://2upra.com/wp-content/uploads/2024/03/GC1r9wVXgAA5e2T.jpg',
    ), $atts);

    // Construye el HTML del shortcode
    $html = "<div class='presentacion-container' id='presentacion'>";
    $html .= "<div class='imagen-container'>";
    $html .= "<img src='{$atts['imagen']}' alt='Imagen de Presentación' id='presentacion-imagen'>";
    $html .= "<p id='presentacion-texto'>{$atts['texto']}</p>";

    // Botón para editar si el usuario actual coincide con el usuario de la URL
    if ($user_id && $current_user_id == $user_id) {
        $html .= "<button onclick='openModal()'>Editar</button>";
    }

    $html .= "</div></div>";

    // Modal para editar la presentación
    $html .= "<div id='modal' class='modal'>";
    $html .= "<div class='modal-content'>";
    $html .= "<span class='close' onclick='closeModal()'>&times;</span>";
    $html .= "<form id='editForm' enctype='multipart/form-data'>";
    $html .= "<input type='file' id='newImage' name='newImage'>";
    $html .= "<textarea id='editedText' name='editedText' placeholder='Editar texto de presentación'></textarea>";
    $html .= "<input type='button' value='Guardar Cambios' onclick='updatePresentacion()'>";
    $html .= "</form></div></div>";

    return $html;
}

add_shortcode('presentacion', 'presentacion_shortcode');

// Refactor(Exec): Moved function ajax_update_presentacion() and its hooks from app/Perfiles/perfilmusic.php
function ajax_update_presentacion() {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $user_id = get_current_user_id();

    $texto = isset($_POST['texto']) ? sanitize_text_field($_POST['texto']) : 'Texto predeterminado';
    $imagen_url = isset($_POST['imagen']) ? esc_url_raw($_POST['imagen']) : ''; 
    
    if (isset($_FILES['newImage']) && $_FILES['newImage']['size'] > 0) {
        $imagen_id = media_handle_upload('newImage', 0);
        if (is_wp_error($imagen_id)) {
            $imagen_url = 'URL de imagen de error';
        } else {
            $imagen_url = wp_get_attachment_url($imagen_id);
        }
    }

    update_user_meta($user_id, 'presentacion_texto', $texto);
    update_user_meta($user_id, 'presentacion_imagen', $imagen_url);

    // Crear HTML para respuesta
    $html = "<div>";
    $html .= "<div class='imagen-container'>";
    $html .= "<img src='{$imagen_url}' alt='Imagen de Presentación' id='presentacion-imagen'>";
    $html .= "<p id='presentacion-texto'>{$texto}</p>";
    $html .= "</div>";

    echo $html;

    wp_die();
}

add_action('wp_ajax_update_presentacion', 'ajax_update_presentacion');
add_action('wp_ajax_nopriv_update_presentacion', 'ajax_update_presentacion');
