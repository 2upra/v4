<?php

function usuarioEsAdminOPro($user_id)
{
    // Verificar que el ID de usuario sea válido
    if (empty($user_id) || !is_numeric($user_id)) {
        ////guardarLog("usuarioEsAdminOPro: Error - ID de usuario inválido.");
        return false;
    }

    // Obtener el objeto usuario
    $user = get_user_by('id', $user_id);

    // Verificar si el usuario existe
    if (!$user) {
        ////guardarLog("usuarioEsAdminOPro: Error - Usuario no encontrado para el ID: " . $user_id);
        return false;
    }

    // Verificar si el usuario tiene roles asignados
    if (empty($user->roles)) {
        ////guardarLog("usuarioEsAdminOPro: Error - Usuario sin roles asignados. ID: " . $user_id);
        ////guardarLog("usuarioEsAdminOPro: Información del usuario - " . print_r($user, true));
        return false;
    }

    // Verificar si el usuario es administrador
    if (in_array('administrator', (array) $user->roles)) {
        ////guardarLog("usuarioEsAdminOPro: Usuario es administrador. ID: " . $user_id);
        return true;
    }

    // Verificar si tiene la meta `pro`
    $is_pro = get_user_meta($user_id, 'pro', true);
    if (!empty($is_pro)) {
        ////guardarLog("usuarioEsAdminOPro: Usuario tiene la meta 'pro'. ID: " . $user_id);
        return true;
    }

    // Si no es administrador ni tiene la meta 'pro'
    ////guardarLog("usuarioEsAdminOPro: Usuario no es administrador ni tiene la meta 'pro'. ID: " . $user_id);
    return false;
}

// Refactor(Org): Funcion saberSi() movida a app/Services/LikeService.php

// Refactor(Org): Funcion obtenerNombreUsuario movida a app/Helpers/UserHelper.php

// Refactor(Org): Funcion guardarBloqueo y su hook AJAX movidos a app/Services/UserService.php

// Funciones de seguimiento movidas a app/Services/FollowService.php

// Refactor(Org): Funciones de manejo de 'pinkys' y tipo de usuario movidas a app/Services/UserService.php

