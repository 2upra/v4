<?

//Evitar que wp genere los titulos por defecto 
remove_action('wp_head', '_wp_render_title_tag', 1);

function regenerate_colecciones_sitemap($post_ID, $post, $update) {
    // Verifica si el post es del tipo 'colecciones' y está publicado
    if ('colecciones' !== $post->post_type || 'publish' !== $post->post_status) {
        return;
    }

    // Obtén la URL de la página del sitemap de colecciones
    $sitemap_url = get_permalink(get_page_by_path('sitemapcolec')); // Reemplaza 'sitemap-colecciones' con el slug de tu página

    // Si la URL es válida, haz una solicitud a ella para que se regenere
    if ($sitemap_url) {
        wp_remote_get($sitemap_url);
    }
}
add_action('save_post', 'regenerate_colecciones_sitemap', 10, 3);