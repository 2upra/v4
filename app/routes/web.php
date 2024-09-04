<?php

add_action('init', 'registrar_rutas_personalizadas');
add_action('template_redirect', 'cargar_plantilla_rutas_personalizadas');

function registrar_rutas_personalizadas() {
    add_rewrite_rule('^dev/?$', 'index.php?custom_page=dev', 'top');
    add_rewrite_rule('^music/?$', 'index.php?custom_page=music', 'top');
    add_rewrite_rule('^$','index.php?custom_page=inicio','top');
    flush_rewrite_rules(false); 
}



