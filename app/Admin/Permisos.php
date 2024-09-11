<?php

function otorgar_capacidad_modificar_notificaciones() {
    global $wp_roles;
    if (!isset($wp_roles)) {
        $wp_roles = new WP_Roles();
    }
    $todos_los_roles = $wp_roles->get_names(); 
    foreach ($todos_los_roles as $rol_nombre => $rol_display_name) {
        $rol = get_role($rol_nombre);
        $rol->add_cap('modificar_notificaciones');
    }
}
otorgar_capacidad_modificar_notificaciones();

function otorgar_capacidades_a_usuarios() {
    $role = get_role('artista');
    $role->add_cap('edit_posts');
    $role->add_cap('publish_posts');
    /*$role->add_cap('edit_pages');
    $role->add_cap('publish_pages');*/
    $role->add_cap('edit_comments');
}
add_action('init', 'otorgar_capacidades_a_usuarios');