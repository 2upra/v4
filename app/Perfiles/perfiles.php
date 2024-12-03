<?


/* 
Peligro esto borra todas las imagenes de perfil (la meta)
function restablecer_imagenes_perfil_a_defecto() {
    $usuarios = get_users(); // Obtiene todos los usuarios

    foreach ($usuarios as $usuario) {
        // Elimina el metadato de imagen de perfil para cada usuario
        delete_user_meta($usuario->ID, 'imagen_perfil_id');
    }
}
restablecer_imagenes_perfil_a_defecto(); */


function imagenPerfil($user_id)
{
    $imagenPerfilId = get_user_meta($user_id, 'imagen_perfil_id', true);
    if (!empty($imagenPerfilId)) {
        $url = wp_get_attachment_url($imagenPerfilId);
    } else {
        $url = 'https://2upra.com/wp-content/uploads/2024/05/perfildefault.jpg';
    }
    return img($url);
}

function obtener_seguidores_o_siguiendo($user_id, $metadato)
{
    $datos = get_user_meta($user_id, $metadato, true);
    return is_array($datos) ? $datos : array();
}

function perfil()
{
    $url_path = trim(parse_url(add_query_arg([]), PHP_URL_PATH), '/');
    $url_segments = explode('/', $url_path);
    $user_slug = end($url_segments);
    $user = get_user_by('slug', $user_slug);
    $user_id = $user->ID;

    ob_start();
?>
    <div class="tabs">
        <div class="tab-content">

            <div id="perfil" class="tab active">
                <div class="YRGFQO">
                    <div class="LRFPKL">
                        <? echo perfilBanner($user_id); ?>
                    </div>
                    <div class="JNDKWD">
                        <? echo publicaciones(['filtro' => 'nada', 'tab_id' => 'perfil', 'posts' => 12]);?>
                    </div>
                </div>
            </div>

        </div>
    </div>
<?
    return ob_get_clean();
}

function perfilBanner($user_id)
{
    $current_user_id = get_current_user_id();
    $mismoAutor = ($user_id === $current_user_id);

    $seguidores = obtener_seguidores_o_siguiendo($user_id, 'seguidores');
    $seguidores_count = count($seguidores);

    $siguiendo = obtener_seguidores_o_siguiendo($user_id, 'siguiendo');
    $siguiendo_count = count($siguiendo);

    $suscripciones_a = (array) get_user_meta($current_user_id, 'offering_user_ids', true);
    $esta_suscrito = in_array($user_id, $suscripciones_a);

    $subscription_price_id = 'price_1PBgGfCdHJpmDkrrHorFUNaV';
    $imagen_perfil = imagenPerfil($user_id);
    $user_info = get_userdata($user_id);

    // Verifica si $user_info es un objeto válido antes de acceder a sus propiedades
    if (!$user_info) {
        return 'Usuario no encontrado';
    }

    $descripcion = get_user_meta($user_id, 'profile_description', true);

    ob_start();
    ?>
    <div class="X522YA FRRVBB" data-iduser="<? echo esc_html($user_id); ?>">
        <div class="JKBZKR">
            <img src="<? echo esc_url($imagen_perfil); ?>" alt="">
            <div class="KFEVRT">
                <p class="ZEKRWP"><? echo esc_html($user_info->display_name); ?></p>
                <p class="NZERUU">@<? echo esc_html($user_info->user_login); ?></p>
                <p class="ZBNIRW"><? echo esc_html($descripcion); ?></p>
            </div>
        </div>

        <div class="KNIDBC">
            <p><? echo esc_html($seguidores_count); ?> seguidores ·</p>
            <p><? echo esc_html($siguiendo_count); ?> siguiendo</p>
        </div>

        <div class="R0A915">
            <? if (!$mismoAutor): ?>
                <?
                echo botonSeguirPerfilBanner($user_id);
                ?>
                <button class="borde PRJWWT mensajeBoton" data-receptor="<? echo esc_html($user_id); ?>">Enviar mensaje</button>
            <? endif; ?>
            <? if ($mismoAutor): ?>
                <button class="DSQKYW">Configuración</button>
            <? endif; ?>
        </div>
    </div>
    <?
    return ob_get_clean();
}

function editar_perfil_usuario_shortcode()
{
    if (!is_user_logged_in()) return;

    $current_user = wp_get_current_user();
    $output = '<div id="editarPerfilModal" style="display:none;"><div class="modal-content-perfil"><span class="cerrar" id="cerrarModal">&times;</span><form class="editarperfil-form" action="" method="post" enctype="multipart/form-data">';

    $campos = [
        'fecha_nacimiento' => 'Fecha de Nacimiento:',
        'url_spotify' => 'URL de Spotify:',
        'correo_paypal' => 'Correo de PayPal:',
    ];

    foreach ($campos as $campo => $etiqueta) {
        $valor = get_user_meta($current_user->ID, $campo, true);
        $tipo = $campo == 'correo_paypal' ? 'email' : ($campo == 'fecha_nacimiento' ? 'date' : 'text');
        $placeholder = $campo == 'url_spotify' ? 'url de Spotify' : ($campo == 'correo_paypal' ? 'correo@gmail.com' : '');
        $output .= "<label for='{$campo}'>{$etiqueta}</label><input placeholder='{$placeholder}' type='{$tipo}' id='{$campo}' name='{$campo}' value='" . esc_attr($valor) . "'><br>";
    }

    $output .= '<label for="imagen_perfil">Imagen de Perfil:</label><input type="file" id="imagen_perfil" name="imagen_perfil"><br><input class="btn-editarperfil" type="submit" name="editar_perfil_usuario_submit" value="Guardar Cambios"></form></div></div>';

    if (isset($_POST['editar_perfil_usuario_submit'])) {
        foreach ($campos as $campo => $etiqueta) {
            $valor_sanitizado = $campo == 'correo_paypal' ? sanitize_email($_POST[$campo]) : sanitize_text_field($_POST[$campo]);
            update_user_meta($current_user->ID, $campo, $valor_sanitizado);
            if ($campo == 'url_spotify' && preg_match("/\/artist\/([a-zA-Z0-9]+)$/", $_POST['url_spotify'], $matches)) {
                update_user_meta($current_user->ID, 'spotify_id', $matches[1]);
            }
        }

        if (isset($_FILES['imagen_perfil']) && $_FILES['imagen_perfil']['error'] === 0) {
            require_once(ABSPATH . 'wp-admin/includes/media.');
            require_once(ABSPATH . 'wp-admin/includes/file.');
            require_once(ABSPATH . 'wp-admin/includes/image.');
            $attachment_id = media_handle_upload('imagen_perfil', 0);
            if (!is_wp_error($attachment_id)) {
                update_user_meta($current_user->ID, 'imagen_perfil_id', $attachment_id);
            }
        }
    }

    return $output;
}
add_shortcode('editar_perfil_usuario', 'editar_perfil_usuario_shortcode');





function mostrar_imagen_perfil_usuario()
{
    $current_user = wp_get_current_user();
    $imagenPerfilId = get_user_meta($current_user->ID, 'imagen_perfil_id', true);
    if ($imagenPerfilId) {
        $imagen_perfil_url = wp_get_attachment_url($imagenPerfilId);
        echo '<img src="' . esc_url($imagen_perfil_url) . '" alt="Imagen de perfil">';
    }
}







function my_custom_avatar($avatar, $id_or_email, $size, $default, $alt)
{

    $default_avatar_url = 'https://i.pinimg.com/564x/d2/64/e3/d264e36c185da291cf7964ec3dfa37b8.jpg';

    $user = false;
    if (is_numeric($id_or_email)) {
        $user = get_user_by('id', $id_or_email);
    } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
        $user = get_user_by('id', $id_or_email->user_id);
    } elseif (is_email($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
    }

    if ($user) {
        $imagenPerfilId = get_user_meta($user->ID, 'imagen_perfil_id', true);
        if (!empty($imagenPerfilId)) {
            $avatar_url = wp_get_attachment_url($imagenPerfilId);
            $avatar = "<img src='" . esc_url($avatar_url) . "' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' alt='{$alt}' />";
        } else {
            $avatar = "<img src='" . esc_url($default_avatar_url) . "' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' alt='{$alt}' />";
        }
    }

    return $avatar;
}
add_filter('get_avatar', 'my_custom_avatar', 10, 5);

function config_user()
{
    wp_enqueue_script('config-user-script', get_template_directory_uri() . '/js/config-user.js', array(), '1.0.3', true);
}
add_action('wp_enqueue_scripts', 'config_user');


function extra_user_profile_fields($user)
{
?>
    <h3>Información adicional del perfil</h3>

    <table class="form-table">
        <tr>
            <th><label for="profile_description">Descripción del Perfil</label></th>
            <td>
                <textarea name="profile_description" id="profile_description" rows="1" cols="30"><? echo esc_attr(get_user_meta($user->ID, 'profile_description', true)); ?></textarea>
                <br />
                <span class="description">Por favor, introduce una descripción para tu perfil.</span>
            </td>
        </tr>
    </table>
<?
}

add_action('personal_options_update', 'save_extra_user_profile_fields');
add_action('edit_user_profile_update', 'save_extra_user_profile_fields');


function save_extra_user_profile_fields($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    update_user_meta($user_id, 'profile_description', $_POST['profile_description']);
}
add_action('show_user_profile', 'extra_user_profile_fields');
add_action('edit_user_profile', 'extra_user_profile_fields');






function save_profile_description_ajax()
{
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $profile_description = isset($_POST['profile_description']) ? sanitize_text_field($_POST['profile_description']) : '';

    if ($user_id && current_user_can('edit_user', $user_id)) {
        update_user_meta($user_id, 'profile_description', $profile_description);
        echo 'Descripción actualizada.';
    } else {
        echo 'No tienes permiso para actualizar este perfil.';
    }

    wp_die();
}
add_action('wp_ajax_save_profile_description', 'save_profile_description_ajax');


