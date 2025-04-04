<?php

// Refactor(Org): Función config() movida desde app/Perfiles/configuracion.php

function config()
{
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_name = $current_user->display_name;
    $descripcion = get_user_meta($user_id, 'profile_description', true);
    $linkUser = get_user_meta($user_id, 'user_link', true);
    $tipoUsuario = get_user_meta($user_id, 'tipoUsuario', true); // Obtenemos el tipo de usuario del meta

    ob_start();
?>

    <div class="LEDDCN modal" id="modalConfig" style="display: none;">
        <p class="ONDNYU">Configuración de Perfil</p>

        <form class="PVSHOT">

            <!-- Cambiar foto de perfil -->
            <div class="PTORKC">
                <div class="previewAreaArchivos" id="previewAreaImagenPerfil">Arrastra tu foto de perfil
                    <label></label>
                </div>
                <input type="file" id="profilePicture" accept="image/*" style="display:none;">
            </div>

            <!-- Cambiar nombre de usuario -->
            <div class="PTORKC">
                <label for="username">Nombre de Usuario:</label>
                <input type="text" id="username" name="username" value="<?php echo esc_attr($user_name); ?>">
            </div>

            <!-- Cambiar descripción -->
            <div class="PTORKC">
                <label for="description">Descripción:</label>
                <textarea id="description" name="description" rows="2"><?php echo esc_attr($descripcion); ?></textarea>
            </div>

            <!-- Agregar un enlace -->
            <div class="PTORKC">
                <label for="link">Enlace:</label>
                <input type="url" id="link" name="link" placeholder="Ingresa un enlace (opcional)" value="<?php echo esc_attr($linkUser); ?>">
            </div>

            <!-- Tipo de usuario -->
            <div class="PTORKC ADGOR3">
                <label for="typeUser">Tipo de usuario:</label>
                <div class="DRHMDE">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="fanTipoCheck" name="fanTipoCheck" value="1" <?php echo $tipoUsuario === 'Fan' ? 'checked' : ''; ?>>
                        <span class="checkmark"></span>
                        Fan
                    </label>
                    <label class="custom-checkbox">
                        <input type="checkbox" id="artistaTipoCheck" name="artistaTipoCheck" value="1" <?php echo $tipoUsuario === 'Artista' ? 'checked' : ''; ?>>
                        <span class="checkmark"></span>
                        Artista
                    </label>
                </div>
            </div>

        </form>
        <button class="guardarConfig">Guardar cambios</button>
    </div>
<?php
    return ob_get_clean();
}

?>