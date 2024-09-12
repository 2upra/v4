<?php

function otorgar_capacidades_roles() {
    global $wp_roles;
    if (!isset($wp_roles)) $wp_roles = new WP_Roles();
    
    foreach ($wp_roles->get_names() as $rol_nombre => $rol_display_name) {
        $rol = get_role($rol_nombre);
        $rol->add_cap('modificar_notificaciones');
    }

    $artista = get_role('artista');
    $artista->add_cap('edit_posts');
    $artista->add_cap('publish_posts');
    $artista->add_cap('edit_comments');
    $artista->add_cap('delete_posts');
    $artista->add_cap('delete_published_posts');
}

add_action('init', 'otorgar_capacidades_roles');
