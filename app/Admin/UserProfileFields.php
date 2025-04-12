<?php
// Refactor(Org): Funciones extra_user_profile_fields, save_extra_user_profile_fields y sus hooks movidos desde app/Perfiles/perfiles.php

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

?>
