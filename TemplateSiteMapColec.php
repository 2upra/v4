<?
/*
Template Name: Sitemap Colecciones
*/

header('Content-Type: text/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

$args = array(
    'post_type'      => 'colecciones',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'author__in'     => array(1, 44), // Filtra por autores con ID 1 y 44
);

$colecciones = new WP_Query($args);

if ($colecciones->have_posts()) {
    while ($colecciones->have_posts()) {
        $colecciones->the_post();

        $permalink = get_permalink();
        $lastmod   = get_the_modified_date('Y-m-d');

        echo '<url>';
        echo '<loc>' . esc_url($permalink) . '</loc>';
        echo '<lastmod>' . esc_html($lastmod) . '</lastmod>';
        echo '<priority>0.9</priority>';
        echo '<changefreq>weekly</changefreq>'; // Cambiado a semanal
        echo '</url>';
    }
    wp_reset_postdata();
}

echo '</urlset>';
?>