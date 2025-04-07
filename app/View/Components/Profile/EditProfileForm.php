<?php
// Refactor(Org): Mueve shortcode editar_perfil_usuario_shortcode() de app/Perfiles/perfiles.php a este archivo.

/**
 * Shortcode para mostrar el formulario modal de edición de perfil de usuario.
 * Incluye campos para fecha de nacimiento, URL de Spotify, correo de PayPal e imagen de perfil.
 * Maneja la actualización de los metadatos del usuario y la subida de la imagen de perfil.
 *
 * @return string HTML del formulario modal o cadena vacía si el usuario no está logueado.
 */
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
                // Extraer y guardar Spotify ID si la URL es válida
                if ($nombreCampo == 'url_spotify' && preg_match("/\/artist\/([a-zA-Z0-9]+)(\?.*)?$/", $_POST['url_spotify'], $coincidencias)) {
                    update_user_meta($usuarioActual->ID, 'spotify_id', $coincidencias[1]);
                }
            }
        }

        if (isset($_FILES['imagen_perfil']) && $_FILES['imagen_perfil']['error'] === UPLOAD_ERR_OK) {
            // Asegurarse de que las funciones de manejo de medios estén disponibles
            if (!function_exists('media_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
            }

            // El '0' indica que no se asocia a ningún post
            $idAdjunto = media_handle_upload('imagen_perfil', 0);
            if (!is_wp_error($idAdjunto)) {
                update_user_meta($usuarioActual->ID, 'imagen_perfil_id', $idAdjunto);
            } else {
                 // Opcional: Loggear el error si falla la subida
                 error_log('Error al subir imagen de perfil para usuario ' . $usuarioActual->ID . ': ' . $idAdjunto->get_error_message());
            }
        }
        // Redirigir para evitar reenvío del formulario al recargar
        // Es importante hacer esto ANTES de cualquier salida HTML si es posible,
        // aunque dentro de un shortcode puede ser complicado.
        // Considerar manejar el guardado vía AJAX para una mejor UX.
        // wp_redirect(wp_get_referer()); exit;
    }

    return $html;
}
add_shortcode('editar_perfil_usuario', 'editar_perfil_usuario_shortcode');

?>
