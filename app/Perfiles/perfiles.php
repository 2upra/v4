<?php


// Funcion obtener_seguidores_o_siguiendo movida a app/Services/FollowService.php (Acción verificada)

// Funcion perfilBanner movida a app/View/Components/ProfileBanner.php

// Funcion editar_perfil_usuario_shortcode movida a app/View/Components/Profile/EditProfileForm.php

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

// Refactor(Org): Funcion my_custom_avatar movida a app/View/Helpers/UserHelper.php

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
