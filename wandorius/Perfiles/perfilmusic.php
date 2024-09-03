<?php

function custom_user_profile_shortcode_music() {
    $url_path = trim(parse_url(add_query_arg([]), PHP_URL_PATH), '/');
    $url_segments = explode('/', $url_path);
    $user_slug = end($url_segments);
    $user = get_user_by('slug', $user_slug);
    

    if ($user !== false) {
        $user_id = $user->ID;
        $current_user = wp_get_current_user();

        $suscripciones_a = get_user_meta($current_user->ID, 'offering_user_ids', true);
        $esta_suscrito = in_array($user_id, (array) $suscripciones_a);

        $subscription_price_id = 'price_1OqGjlCdHJpmDkrryMzL0BCK';
        $profile_description = get_user_meta($user_id, 'profile_description', true);

        $imagen_perfil_id = get_user_meta($user_id, 'imagen_perfil_id', true);
        if ($imagen_perfil_id) {
            $image_attributes = wp_get_attachment_image_src($imagen_perfil_id, 'medium'); 
            if ($image_attributes) {
                $imagen_perfil_url = $image_attributes[0]; 
                $imagen_html = '<img src="' . esc_url($imagen_perfil_url) . '" alt="Imagen de perfil" class="gravatar avatar avatar-96 um-avatar um-avatar-default" width="' . $image_attributes[1] . '" height="' . $image_attributes[2] . '" onerror="if ( ! this.getAttribute(\'data-load-error\') ){ this.setAttribute(\'data-load-error\', \'1\');this.setAttribute(\'src\', this.getAttribute(\'data-default\'));}" loading="lazy">';
            }
        } else {
            $imagen_html = '<img src="https://2upra.com/wp-content/plugins/ultimate-member/assets/img/default_avatar.jpg" alt="Imagen de perfil" class="gravatar avatar avatar-96 um-avatar um-avatar-default lazyloaded" width="96" height="96" data-default="https://2upra.com/wp-content/plugins/ultimate-member/assets/img/default_avatar.jpg" onerror="if ( ! this.getAttribute(\'data-load-error\') ){ this.setAttribute(\'data-load-error\', \'1\');this.setAttribute(\'src\', this.getAttribute(\'data-default\'));}" loading="lazy">';
        }

        $insignia_urls = get_insignia_urls();
        $insignia_html = '';

        $user_roles = $user->roles;
        $es_admin = in_array('administrator', $user_roles);
        $es_pro = get_user_meta($user_id, 'user_pro', true);
        $es_member = get_user_meta($user_id, 'member', true);
        $oyentes_unicos = contar_oyentes_unicos($user_id);

        if ($es_admin) {
            $insignia_html .= '<img src="' . esc_url($insignia_urls['admin']) . '" alt="Insignia de Administrador" title="2UPRA TEAM" class="custom-user-insignia">';
        }

        if ($es_pro && $user_id != 1) {
            $insignia_html .= '<img src="' . esc_url($insignia_urls['pro']) . '" alt="Insignia de Usuario Pro" title="PARTNER" class="custom-user-insignia">';
        }

        if ($es_member && $user_id != 1) {
            $insignia_html .= '<img src="' . esc_url($insignia_urls['member']) . '" alt="Insignia de Usuario Pro" title="PARTNER" class="custom-user-insignia">';
        }

        $output = '<div class="music custom-uprofile-container" data-author-id="' . $user_id . '">';
        $output .= '<div class="music custom-uprofile-image">' . $imagen_html . '</div>';
        $output .= '<div class="music custom-uprofile-info">';
        $output .= '<p class="music custom-uprofile-username">' . esc_html($user->display_name) . $insignia_html . '</p>';
        $output .= '<p class="music custom-uprofile-listeners">' . $oyentes_unicos . ' Oyentes</p>';

        if (in_array('administrator', $user->roles)) {
            $output .= '<p class="music custom-uprofile-type">Artista</p>';
        } else {
            $output .= '<p class="music custom-uprofile-type">' . esc_html(ucfirst($user->roles[0])) . '</p>';
        }
        if ($user_id === $current_user->ID) {
            $output .= '<div contenteditable="true" id="editable-profile-description" data-user-id="' . esc_attr($user_id) . '" style="border: none; outline: none; max-width: 100%; overflow-wrap: break-word;">' . esc_html($profile_description) . '</div>';
        } else {
            $output .= '<p class="music custom-uprofile-description">' . esc_html($profile_description) . '</p>';
        }
        $output .= '<div class="music button-container">';
        $output .= '<button ';

        if ($user_id !== $current_user->ID) {
            $output .= '<button class="music custom-subscribe-btn';
            $output .= $esta_suscrito ? ' custom-subscribe-btn-suscrito"' : '"';
            $output .= ' data-offering-user-id="' . esc_attr($user_id) . '" ';
            $output .= 'data-offering-user-login="' . esc_attr($user->user_login) . '" ';
            $output .= 'data-offering-user-email="' . esc_attr($user->user_email) . '" ';
            $output .= 'data-subscriber-user-id="' . esc_attr($current_user->ID) . '" ';
            $output .= 'data-subscriber-user-login="' . esc_attr($current_user->user_login) . '" ';
            $output .= 'data-subscriber-user-email="' . esc_attr($current_user->user_email) . '" ';
            $output .= 'data-price="' . esc_attr($subscription_price_id) . '" ';
            $output .= 'data-url="' . esc_url(get_permalink()) . '" ';
            if ($esta_suscrito) {
                $output .= '>Suscripto</button>';
            } else {
                $output .= '>Suscribirse</button>';
            }
        }

        if ($user_id === $current_user->ID) { 
            $output .= '<button class="music custom-edit-profile-btn" onclick="abrirModalEditarPerfil()">Editar Perfil</button>';
        }

        $output .= '<button class="custom-start-chat-btn" data-chat-user-login="' . esc_attr($user->user_login) . '">Mensaje</button>';
        $output .= '</div>';
        $output .= '</div></div>';

        return $output;
    } else {
        return '<p>Perfil de usuario no encontrado.</p>';
    }
}
add_shortcode('custom_user_profile_music', 'custom_user_profile_shortcode_music');

function enqueue_scripts42() {
    wp_enqueue_script('color-thief', 'https://cdn.jsdelivr.net/npm/colorthief/dist/color-thief.umd.js', array(), null, true);
    if (!wp_script_is('colormusic', 'registered')) {
        wp_register_script('colormusic', get_template_directory_uri() . '/js/colormusic.js', array('jquery', 'color-thief'), '1.0.3', true);
    }
    wp_enqueue_script('colormusic');
}

add_action('wp_enqueue_scripts', 'enqueue_scripts42');









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




function postrolaresumen() {
    global $post;
    $current_user_id = get_current_user_id();
    $author_id = get_the_author_meta('ID');
    $user = get_userdata($author_id); 
    $insignia_urls = get_insignia_urls();
    $insignia_html = ''; 
    $author_name = get_the_author();
    $audio_id_lite = get_post_meta(get_the_ID(), 'post_audio_lite', true);   
    $audio_id = get_post_meta(get_the_ID(), 'post_audio', true);
    $audio_url = wp_get_attachment_url($audio_id);
    $audio_lite = wp_get_attachment_url($audio_id_lite);
    $wave = get_post_meta(get_the_ID(), 'audio_waveform_image', true);
    $duration = get_post_meta(get_the_ID(), 'audio_duration', true); 

    // Obtener información de 'likes' NUEVO
    $current_post_id = get_the_ID();
    $like_count = get_like_count($current_post_id);
    $user_has_liked = check_user_liked_post($current_post_id, $current_user_id);
    $liked_class = $user_has_liked ? 'liked' : 'not-liked';

    $post_content = get_the_content();
    $post_content = wp_strip_all_tags($post_content); 
    $post_content = esc_attr($post_content); 
    $post_thumbnail_id = get_post_thumbnail_id();
    $post_thumbnail_url = function_exists('jetpack_photon_url') 
        ? jetpack_photon_url(wp_get_attachment_image_url($post_thumbnail_id, 'medium'), array('quality' => 50, 'strip' => 'all')) 
        : wp_get_attachment_image_url($post_thumbnail_id, 'medium');

    ob_start();
    ?>
    <li class="social-post rola" data-post-id="<?php echo get_the_ID(); ?>">
        <input type="hidden" class="post-id" value="<?php echo get_the_ID(); ?>" />
        <div class="rola social-post-content" style="font-size: 13px;">
                    
            <div id="audio-container-<?php echo get_the_ID(); ?>" class="audio-container" 
             data-imagen="<?php echo esc_url($post_thumbnail_url); ?>"
             data-title="<?php echo $post_content; ?>"
             data-author="<?php echo esc_attr($author_name); ?>"
             data-post-id="<?php echo get_the_ID(); ?>"
             data-artist="<?php echo esc_attr($author_id); ?>"
             data-liked="<?php echo $user_has_liked ? 'true' : 'false'; ?>"
             style="width: 40px; height: 40px; aspect-ratio: 1 / 1; position: relative;">

                <img class="imagen-post" src="<?php echo esc_url($post_thumbnail_url); ?>" alt="Imagen del post" style="position: absolute; border-radius: 3%; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                <div class="play-pause-sobre-imagen" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); cursor: pointer; display: none;">
                    <img src="https://2upra.com/wp-content/uploads/2024/03/1.svg" alt="Play" style="width: 50px; height: 50px;"> 
                </div>
                <audio id="audio-<?php echo get_the_ID(); ?>" src="<?php echo site_url('?custom-audio-stream=1&audio_id=' . $audio_id_lite); ?>"></audio>
            </div>


    <div class="contentrola"><?php the_content(); ?></div>
    <div class="duracionrola"><?php echo esc_html($duration); ?></div>
    <div class="social-post-like rola">
        <?php
        $current_post_id = get_the_ID();
        $nonce = wp_create_nonce('like_post_nonce');
        $like_count = get_like_count($current_post_id);
        like($current_post_id);
        ?>          
    </div>

</li>
  <?php
  return ob_get_clean();
}

function postcover() {
    global $post;
    $current_user_id = get_current_user_id();
    $author_id = get_the_author_meta('ID');
    $user = get_userdata($author_id); 
    $insignia_urls = get_insignia_urls();
    $insignia_html = ''; 
    $likes = get_post_meta(get_the_ID(), '_post_likes', true);
    $like_count = is_array($likes) ? count($likes) : 0;
    $user_has_liked = is_array($likes) && in_array($current_user_id, $likes); 
    $liked_class = $user_has_liked ? 'liked' : ''; 
    $author_name = get_the_author();
    $audio_id_lite = get_post_meta(get_the_ID(), 'post_audio_lite', true);   
    $audio_id = get_post_meta(get_the_ID(), 'post_audio', true);
    $audio_url = wp_get_attachment_url($audio_id);
    $audio_lite = wp_get_attachment_url($audio_id_lite);
    $wave = get_post_meta(get_the_ID(), 'audio_waveform_image', true);
    $duration = get_post_meta(get_the_ID(), 'audio_duration', true); 
    ob_start();
    $post_content = get_the_content();
    $post_content = wp_strip_all_tags($post_content); 
    $post_content = esc_attr($post_content); 
    $post_thumbnail_id = get_post_thumbnail_id();
    $post_thumbnail_url = function_exists('jetpack_photon_url') 
        ? jetpack_photon_url(wp_get_attachment_image_url($post_thumbnail_id, 'medium'), array('quality' => 50, 'strip' => 'all')) 
        : wp_get_attachment_image_url($post_thumbnail_id, 'medium');

    ?>
    <li class="social-post cover" data-post-id="<?php echo get_the_ID(); ?>">
        <input type="hidden" class="post-id" value="<?php echo get_the_ID(); ?>" />
        <div class="cover social-post-content" style="font-size: 13px;">
                    
            <div id="audio-container-<?php echo get_the_ID(); ?>" class="audio-container" 
             data-imagen="<?php echo esc_url($post_thumbnail_url); ?>"
             data-title="<?php echo $post_content; ?>"
             data-author="<?php echo esc_attr($author_name); ?>"
             data-post-id="<?php echo get_the_ID(); ?>"
             data-artist="<?php echo esc_attr($author_id); ?>"
             data-liked="<?php echo $user_has_liked ? 'true' : 'false'; ?>"
             style="width: 150px;height: 150px;aspect-ratio: 1 / 1;position: relative;/* margin: auto; */">

                <img class="imagen-post" src="<?php echo esc_url($post_thumbnail_url); ?>" alt="Imagen del post" style="position: absolute; border-radius: 3%; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                <div class="play-pause-sobre-imagen" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); cursor: pointer; display: none;">
                    <img src="https://2upra.com/wp-content/uploads/2024/03/1.svg" alt="Play" style="width: 50px; height: 50px;"> 
                </div>
                <audio id="audio-<?php echo get_the_ID(); ?>" src="<?php echo site_url('?custom-audio-stream=1&audio_id=' . $audio_id_lite); ?>"></audio>
            </div>


        <div class="contentrola"><?php the_content(); ?></div>
        
    <div class="social-post-like" style="display: none;">
        <button class="post-like-button <?php echo esc_attr($liked_class); ?>" data-post_id="<?php echo get_the_ID(); ?>" data-nonce="<?php echo wp_create_nonce('like_post_nonce'); ?>"><i class="fa-heart fas"></i></button>           
    </div>
</li>
  <?php
  return ob_get_clean();
}
