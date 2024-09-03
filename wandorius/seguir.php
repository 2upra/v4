<?php 

function seguir_usuario() {
    $seguidor_id = isset($_POST['seguidor_id']) ? (int) $_POST['seguidor_id'] : 0;
    $seguido_id = isset($_POST['seguido_id']) ? (int) $_POST['seguido_id'] : 0;

    if (!is_numeric($seguidor_id) || !is_numeric($seguido_id)) {
        return false;
    }
    $siguiendo = get_user_meta($seguidor_id, 'siguiendo', true);
    if (!is_array($siguiendo)) {
        $siguiendo = array();
    }
    if (!in_array($seguido_id, $siguiendo)) {
        $siguiendo[] = $seguido_id;
        update_user_meta($seguidor_id, 'siguiendo', $siguiendo);

        $seguidores = get_user_meta($seguido_id, 'seguidores', true);
        if (!is_array($seguidores)) {
            $seguidores = array();
        }
        $seguidores[] = $seguidor_id;
        return update_user_meta($seguido_id, 'seguidores', $seguidores);
    }
    return false;
}
add_action('wp_ajax_seguir_usuario', 'seguir_usuario');

function dejar_de_seguir_usuario() {
    $seguidor_id = isset($_POST['seguidor_id']) ? (int) $_POST['seguidor_id'] : 0;
    $seguido_id = isset($_POST['seguido_id']) ? (int) $_POST['seguido_id'] : 0;

    if (!is_numeric($seguidor_id) || !is_numeric($seguido_id)) {
        return false;
    }
    $siguiendo = get_user_meta($seguidor_id, 'siguiendo', true);
    if (is_array($siguiendo)) {
        $clave = array_search($seguido_id, $siguiendo);
        
        if ($clave !== false) {
            unset($siguiendo[$clave]);
            update_user_meta($seguidor_id, 'siguiendo', $siguiendo);

            $seguidores = get_user_meta($seguido_id, 'seguidores', true);
            if (is_array($seguidores)) {
                $clave = array_search($seguidor_id, $seguidores);
                if ($clave !== false) {
                    unset($seguidores[$clave]);
                    return update_user_meta($seguido_id, 'seguidores', $seguidores);
                }
            }
        }
    }

    return false;
}
add_action('wp_ajax_dejar_de_seguir_usuario', 'dejar_de_seguir_usuario');

/*
function actualizar_seguidores() {
    $usuarios = get_users();
    foreach ($usuarios as $usuario) {
        $siguiendo = get_user_meta($usuario->ID, 'siguiendo', true);
        if (is_array($siguiendo)) {
            foreach ($siguiendo as $seguido_id) {
                $seguidores = get_user_meta($seguido_id, 'seguidores', true);
                if (!is_array($seguidores)) {
                    $seguidores = array();
                }
                if (!in_array($usuario->ID, $seguidores)) {
                    $seguidores[] = $usuario->ID;
                    update_user_meta($seguido_id, 'seguidores', $seguidores);
                }
            }
        }
    }
}
actualizar_seguidores();
*/



add_shortcode('mostrar_contadores', function() {
    $user_id = get_current_user_id();

    $seguidores = get_user_meta($user_id, 'seguidores', true);
    if (!is_array($seguidores)) {
        $seguidores = array();
    }
    $seguidores_count = count($seguidores);

    $siguiendo = get_user_meta($user_id, 'siguiendo', true);
    if (!is_array($siguiendo)) {
        $siguiendo = array();
    }
    $siguiendo_count = count($siguiendo);

    $args = array(
        'author' => $user_id,
        'post_type' => 'social_post'
    );
    $query = new WP_Query($args);
    $posts_count = $query->found_posts;

    return "{$seguidores_count} seguidores {$siguiendo_count} seguidos {$posts_count} posts";
});




function seguir_usuario_automaticamente($user_id) {
    // Verificar si el usuario existe
    if (get_user_by('id', $user_id)) {
        $siguiendo = get_user_meta($user_id, 'siguiendo', true);
        if (!is_array($siguiendo)) {
            $siguiendo = array();
        }
        // Agregar al usuario a sí mismo si aún no se sigue
        if (!in_array($user_id, $siguiendo)) {
            $siguiendo[] = $user_id;
            update_user_meta($user_id, 'siguiendo', $siguiendo);
        }
    }
}

// Hook para ejecutar la función cuando se crea un nuevo usuario



function seguir_usuarios_automaticamente1() {
    // Obtener todos los usuarios
    $usuarios = get_users();

    // Recorrer cada usuario
    foreach ($usuarios as $usuario) {
        $user_id = $usuario->ID;
        $siguiendo = get_user_meta($user_id, 'siguiendo', true);

        // Verificar si el usuario sigue a sí mismo
        if (!is_array($siguiendo) || !in_array($user_id, $siguiendo)) {
            // Si no se sigue a sí mismo, agregarlo
            if (!is_array($siguiendo)) {
                $siguiendo = array();
            }
            $siguiendo[] = $user_id;
            update_user_meta($user_id, 'siguiendo', $siguiendo);
        }
    }
}
add_action('user_register', 'seguir_usuario_automaticamente');


function enqueue_seguir_script() {

    wp_enqueue_script('seguir', get_template_directory_uri() . '/js/seguir.js', array('jquery'), '1.0.9', true);
    
    wp_localize_script('seguir', 'ajax_params', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_seguir_script');
