
<?php
//single-colecciones.php
if (! defined('ABSPATH')) {
    exit;
}

$postId = get_the_ID();
$filtro = 'singleColec';

// Obtener el título de la colección
$post_title = get_the_title();

// Generar el título SEO: Primera letra en mayúscula y añadir "| Drum kit & Sample Pack"
$seo_title = ucfirst($post_title) . ' | Drum kit & Sample Pack'; // Modificado aquí
add_action('wp_head', function () use ($seo_title) {
    echo '<title>' . esc_html($seo_title) . '</title>' . "\n";
}, 1);

// Meta descripción
$meta_description_full = get_the_content(); // Obtener el contenido completo
$meta_description = mb_substr(wp_strip_all_tags($meta_description_full), 0, 160);
$meta_description = esc_attr($meta_description);

// Añadir la meta descripción en el <head>
add_action('wp_head', function () use ($meta_description) {
    if (! empty($meta_description)) {
        echo '<meta name="description" content="' . $meta_description . '">' . "\n";
    }
}, 1); // Prioridad baja para que se ejecute temprano en wp_head

// Esquema JSON-LD
$schema = [
    "@context"    => "https://schema.org",
    "@type"       => "CollectionPage", // Tipo de esquema para una colección
    "name"        => $seo_title,
    "description" => $meta_description,
    "datePublished" => get_the_date('c'),
    "author"      => [
        "@type" => "Person",
        "name"  => get_the_author()
    ]
];

// Añadir el esquema JSON-LD al <head>
add_action('wp_head', function () use ($schema) {
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}, 2); // Se ejecuta después de la meta descripción
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

    <?php get_header(); ?>

    <main id="main">
        <div id="content" class="<?php echo esc_attr(! is_user_logged_in() ? 'nologin' : ''); ?>">
            <div id="menuData" style="display:none;" pestanaActual="">
                <div data-tab="Coleccion"></div>
                <div data-tab="Ideas"></div>
            </div>

            <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                    <article <?php post_class(); ?>>
                        <div class="tabs">
                            <div class="tab-content">

                                <div class="tab active" id="Coleccion" colec="<?php echo $postId ?>">
                                    <div class="SINGLECOLECSGER">
                                        <?php echo singleColec($postId) ?>
                                    </div>
                                </div>

                                <div class="tab" id="Ideas" colec="<?php echo $postId ?>" idea="true">
                                    <div>
                                        <?php echo masIdeasColeb($postId) ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </article>
            <?php endwhile;
            endif; ?>
        </div>
    </main>

    <?php get_footer(); ?>

</body>

</html>