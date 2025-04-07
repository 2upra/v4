<?
//single-colecciones.php
if (! defined('ABSPATH')) {
    exit;
}

// Refactor: SEO meta generation moved to SEOService->generateColeccionMetaTags()
// The following block was removed:
// $postId = get_the_ID();
// $filtro = 'singleColec';
// // Obtener el título de la colección
// $post_title = get_the_title();
// // Generar el título SEO: Primera letra en mayúscula y añadir "| Drum kit & Sample Pack"
// $seo_title = ucfirst($post_title) . ' | Drum kit & Sample Pack'; // Modificado aquí
// add_action('wp_head', function () use ($seo_title) {
//     echo '<title>' . esc_html($seo_title) . '</title>' . "\n";
// }, 1);
// // Meta descripción
// $meta_description_full = get_the_content(); // Obtener el contenido completo
// $meta_description = mb_substr(wp_strip_all_tags($meta_description_full), 0, 160);
// $meta_description = esc_attr($meta_description);
// // Añadir la meta descripción en el <head>
// add_action('wp_head', function () use ($meta_description) {
//     if (! empty($meta_description)) {
//         echo '<meta name="description" content="' . $meta_description . '">' . "\n";
//     }
// }, 1); // Prioridad baja para que se ejecute temprano en wp_head
// // Esquema JSON-LD
// $schema = [
//     "@context"    => "https://schema.org",
//     "@type"       => "CollectionPage", // Tipo de esquema para una colección
//     "name"        => $seo_title,
//     "description" => $meta_description,
//     "datePublished" => get_the_date('c'),
//     "author"      => [
//         "@type" => "Person",
//         "name"  => get_the_author()
//     ]
// ];
// // Añadir el esquema JSON-LD al <head>
// add_action('wp_head', function () use ($schema) {
//     echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
// }, 2); // Se ejecuta después de la meta descripción

// Note: You might need to instantiate SEOService and call generateColeccionMetaTags()
// and then use its return value to output the tags via wp_head action if needed here.
// Example (needs proper integration):
// $seo_service = new SEOService();
// $postId = get_the_ID();
// $seo_data = $seo_service->generateColeccionMetaTags($postId);
// add_action('wp_head', function() use ($seo_data) {
//     echo '<title>' . esc_html($seo_data['title']) . '</title>' . "\n";
//     if (!empty($seo_data['description'])) {
//         echo '<meta name="description" content="' . $seo_data['description'] . '">' . "\n";
//     }
//     echo '<script type="application/ld+json">' . wp_json_encode($seo_data['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
// }, 1);

?>
<!DOCTYPE html>
<html <? language_attributes(); ?>>

<head>
    <meta charset="<? bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <? wp_head(); ?>
</head>

<body <? body_class(); ?>>

    <? get_header(); ?>

    <main id="main">
        <div id="content" class="<? echo esc_attr(! is_user_logged_in() ? 'nologin' : ''); ?>">
            <div id="menuData" style="display:none;" pestanaActual="">
                <div data-tab="Coleccion"></div>
                <div data-tab="Ideas"></div>
            </div>

            <? if (have_posts()) : while (have_posts()) : the_post(); ?>
                    <? $postId = get_the_ID(); // Keep postId definition if needed by the template below ?>
                    <article <? post_class(); ?>>
                        <div class="tabs">
                            <div class="tab-content">

                                <div class="tab active" id="Coleccion" colec="<? echo $postId ?>">
                                    <div class="SINGLECOLECSGER">
                                        <? echo singleColec($postId) ?>
                                    </div>
                                </div>

                                <div class="tab" id="Ideas" colec="<? echo $postId ?>" idea="true">
                                    <div>
                                        <? echo masIdeasColeb($postId) ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </article>
            <? endwhile;
            endif; ?>
        </div>
    </main>

    <? get_footer(); ?>

</body>

</html>
