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

// Refactor(Org): Funciones extra_user_profile_fields, save_extra_user_profile_fields y sus hooks movidos a app/Admin/UserProfileFields.php

// Function save_profile_description_ajax and its hook moved to app/Services/UserService.php

?>
