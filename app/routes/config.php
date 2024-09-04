<?php
function cargar_plantilla_rutas_personalizadas() {
    $custom_page = get_query_var('custom_page');
    if ($custom_page) {
        $template_path = get_template_directory() . '/app/routes/' . $custom_page . '.php';
        if (file_exists($template_path)) {
            include $template_path;
            exit; 
        } else {
            include get_404_template();
            exit;
        }
    }
}
add_filter('query_vars', 'agregar_query_var_custom_page');
function agregar_query_var_custom_page($vars) {
    $vars[] = 'custom_page';
    return $vars;
}
