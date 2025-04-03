<?php



function obtener_seguidores_o_siguiendo($idUsuario, $metadato) {
    $data = get_user_meta($idUsuario, $metadato, true);
    return is_array($data) ? $data : [];
}

function perfilBanner($idUsuario) {
    $idUsuarioActual = get_current_user_id();
    $esMismoAutor = ($idUsuario === $idUsuarioActual);

    $numSeguidores = count(obtener_seguidores_o_siguiendo($idUsuario, 'seguidores'));
    $numSiguiendo = count(obtener_seguidores_o_siguiendo($idUsuario, 'siguiendo'));

    $suscripciones = (array) get_user_meta($idUsuarioActual, 'offering_user_ids', true);
    $estaSuscrito = in_array($idUsuario, $suscripciones);

    $idPrecioSub = 'price_1PBgGfCdHJpmDkrrHorFUNaV'; // Mantenido por si es un ID externo importante
    $urlImagen = imagenPerfil($idUsuario);
    $infoUsuario = get_userdata($idUsuario);

    if (!$infoUsuario) {
        return 'Usuario no encontrado';
    }

    $desc = get_user_meta($idUsuario, 'profile_description', true);

    ob_start();
?>
    <div class="X522YA FRRVBB" data-iduser="<? echo esc_attr($idUsuario); ?>">
        <div class="JKBZKR">
            <img src="<? echo esc_url($urlImagen); ?>" alt="">
            <div class="KFEVRT">
                <p class="ZEKRWP"><? echo esc_html($infoUsuario->display_name); ?></p>
                <p class="NZERUU">@<? echo esc_html($infoUsuario->user_login); ?></p>
                <p class="ZBNIRW"><? echo esc_html($desc); ?></p>
            </div>
        </div>

        <div class="KNIDBC">
            <p><? echo esc_html($numSeguidores); ?> seguidores ·</p>
            <p><? echo esc_html($numSiguiendo); ?> siguiendo</p>
        </div>

        <div class="R0A915">
            <? if (!$esMismoAutor): ?>
                <?
                // Asumiendo que botonSeguirPerfilBanner existe y funciona correctamente
                echo botonSeguirPerfilBanner($idUsuario);
                ?>
                <button class="borde PRJWWT mensajeBoton" data-receptor="<? echo esc_attr($idUsuario); ?>">Enviar mensaje</button>
            <? endif; ?>
            <? if ($esMismoAutor): ?>
                <button class="botonConfig borde">Configuración</button>
                <button class="compartirPerfil borde" data-username="<? echo esc_attr($infoUsuario->user_login); ?>">Compartir perfil</button>
            <? endif; ?>
        </div>
    </div>
<?
    return ob_get_clean();
}

function editar_perfil_usuario_shortcode() {
    if (!is_user_logged_in()) return '';

    $usuarioActual = wp_get_current_user();
    $html = '<div id="editarPerfilModal" style="display:none;"><div class="modal-content-perfil"><span class="cerrar" id="cerrarModal">×</span><form class="editarperfil-form" action="" method="post" enctype="multipart/form-data">';

    // Mantenemos nombres originales por ser claves de $_POST y meta keys
    $camposForm = [
        'fecha_nacimiento' => 'Fecha de Nacimiento:',
        'url_spotify' => 'URL de Spotify:',
        'correo_paypal' => 'Correo de PayPal:',
    ];

    foreach ($camposForm as $nombreCampo => $label) {
        $val = get_user_meta($usuarioActual->ID, $nombreCampo, true);
        $tipoInput = $nombreCampo == 'correo_paypal' ? 'email' : ($nombreCampo == 'fecha_nacimiento' ? 'date' : 'text');
        $ph = $nombreCampo == 'url_spotify' ? 'url de Spotify' : ($nombreCampo == 'correo_paypal' ? 'correo@ejemplo.com' : '');
        $html .= "<label for='{$nombreCampo}'>{$label}</label><input placeholder='{$ph}' type='{$tipoInput}' id='{$nombreCampo}' name='{$nombreCampo}' value='" . esc_attr($val) . "'><br>";
    }

    $html .= '<label for="imagen_perfil">Imagen de Perfil:</label><input type="file" id="imagen_perfil" name="imagen_perfil"><br><input class="btn-editarperfil" type="submit" name="editar_perfil_usuario_submit" value="Guardar Cambios"></form></div></div>';

    if (isset($_POST['editar_perfil_usuario_submit'])) {
        foreach ($camposForm as $nombreCampo => $label) {
            if (isset($_POST[$nombreCampo])) {
                $valSanitizado = ($nombreCampo == 'correo_paypal') ? sanitize_email($_POST[$nombreCampo]) : sanitize_text_field($_POST[$nombreCampo]);
                update_user_meta($usuarioActual->ID, $nombreCampo, $valSanitizado);
                if ($nombreCampo == 'url_spotify' && preg_match("/\/artist\/([a-zA-Z0-9]+)$/", $_POST['url_spotify'], $coincidencias)) {
                    update_user_meta($usuarioActual->ID, 'spotify_id', $coincidencias[1]);
                }
            }
        }

        if (isset($_FILES['imagen_perfil']) && $_FILES['imagen_perfil']['error'] === UPLOAD_ERR_OK) {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            // El '0' indica que no se asocia a ningún post
            $idAdjunto = media_handle_upload('imagen_perfil', 0);
            if (!is_wp_error($idAdjunto)) {
                update_user_meta($usuarioActual->ID, 'imagen_perfil_id', $idAdjunto);
            } else {
                 // Opcional: Loggear el error si falla la subida
                 // error_log('Error al subir imagen de perfil: ' . $idAdjunto->get_error_message());
            }
        }
        // Podrías agregar un wp_redirect aquí para evitar reenvío del formulario al recargar
        // wp_redirect(wp_get_referer()); exit;
    }

    return $html;
}
add_shortcode('editar_perfil_usuario', 'editar_perfil_usuario_shortcode');

function mostrar_imagen_perfil_usuario() {
    if (!is_user_logged_in()) return;
    $usuarioActual = wp_get_current_user();
    $idImagen = get_user_meta($usuarioActual->ID, 'imagen_perfil_id', true);
    if ($idImagen) {
        $urlImagen = wp_get_attachment_url($idImagen);
        echo '<img src="' . esc_url($urlImagen) . '" alt="Imagen de perfil">';
    }
    // Podrías agregar una imagen por defecto si no hay $idImagen
    // else { echo '<img src="url_por_defecto.jpg" alt="Imagen de perfil por defecto">'; }
}

function my_custom_avatar($avatar, $id_or_email, $size, $default, $alt) {
    $urlAvatarDefecto = 'https://i.pinimg.com/564x/d2/64/e3/d264e36c185da291cf7964ec3dfa37b8.jpg'; // URL por defecto
    $usuario = false;

    if (is_numeric($id_or_email)) {
        $usuario = get_user_by('id', $id_or_email);
    } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
        $usuario = get_user_by('id', $id_or_email->user_id);
    } elseif (is_string($id_or_email) && is_email($id_or_email)) {
        $usuario = get_user_by('email', $id_or_email);
    }

    if ($usuario) {
        $idImagen = get_user_meta($usuario->ID, 'imagen_perfil_id', true);
        $urlAvatar = !empty($idImagen) ? wp_get_attachment_url($idImagen) : $urlAvatarDefecto;
        $avatar = "<img src='" . esc_url($urlAvatar) . "' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' alt='" . esc_attr($alt) . "' />";
    } else {
        // Si no se encuentra usuario, usar la URL por defecto o el avatar original
         $avatar = "<img src='" . esc_url($urlAvatarDefecto) . "' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' alt='" . esc_attr($alt) . "' />";
    }

    return $avatar;
}
add_filter('get_avatar', 'my_custom_avatar', 10, 5);

function config_user() {
    // Usamos '1.0.4' como ejemplo de versión incrementada tras refactorizar
    wp_enqueue_script('config-user-script', get_template_directory_uri() . '/js/config-user.js', [], '1.0.4', true);
}
add_action('wp_enqueue_scripts', 'config_user');

function extra_user_profile_fields($usuario) { // Cambiado $user a $usuario
?>
    <h3>Información adicional del perfil</h3>
    <table class="form-table">
        <tr>
            <th><label for="profile_description">Descripción del Perfil</label></th>
            <td>
                <textarea name="profile_description" id="profile_description" rows="1" cols="30"><? echo esc_textarea(get_user_meta($usuario->ID, 'profile_description', true)); // Usar esc_textarea para textareas ?></textarea>
                <br />
                <span class="description">Por favor, introduce una descripción para tu perfil.</span>
            </td>
        </tr>
    </table>
<?
}
add_action('show_user_profile', 'extra_user_profile_fields');
add_action('edit_user_profile', 'extra_user_profile_fields');


function save_extra_user_profile_fields($idUsuario) { // Cambiado $user_id a $idUsuario
    if (!current_user_can('edit_user', $idUsuario)) {
        return false;
    }
    // Añadida sanitización que faltaba
    if (isset($_POST['profile_description'])) {
        update_user_meta($idUsuario, 'profile_description', sanitize_textarea_field($_POST['profile_description']));
    }
}
add_action('personal_options_update', 'save_extra_user_profile_fields');
add_action('edit_user_profile_update', 'save_extra_user_profile_fields');

// Function save_profile_description_ajax and its hook moved to app/Services/UserService.php

?>