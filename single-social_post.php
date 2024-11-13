<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

// Detecta el idioma del navegador
function get_user_browser_language() {
    if ( ! isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
        return 'en';
    }
    $accepted_languages = explode( ',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
    foreach ( $accepted_languages as $language ) {
        $lang = substr( $language, 0, 2 );
        if ( in_array( $lang, ['es', 'en'] ) ) {
            return $lang;
        }
    }
    return 'en';
}

$active_lang = get_user_browser_language();
$current_post_id = get_the_ID();
$filtro = 'single';

$datos_algoritmo_pri = get_post_meta($current_post_id, 'datosAlgoritmo', true);
$datos_algoritmo_respaldo = get_post_meta($current_post_id, 'datosAlgoritmo_respaldo', true);

$datosAlgoritmo = empty($datos_algoritmo_pri) ? $datos_algoritmo_respaldo : $datos_algoritmo_pri;
$datos_decoded = is_string($datosAlgoritmo) ? json_decode($datosAlgoritmo, true) : $datosAlgoritmo;

// Generar el título SEO
$post_title = get_the_title();
$tipo_audio = isset( $datos_decoded['tipo_audio'][ $active_lang ][0] ) ? $datos_decoded['tipo_audio'][ $active_lang ][0] : 'Sample';
$seo_title = $post_title . ' | ' . $tipo_audio . ' free';

// Utilizar el filtro document_title_parts
add_filter( 'document_title_parts', function( $title ) use ( $seo_title ) {
    $title['title'] = $seo_title;
    return $title;
} );

// Meta descripción
$sugerencias_busqueda = isset( $datos_decoded['sugerencia_busqueda'][ $active_lang ] ) ? implode( ', ', $datos_decoded['sugerencia_busqueda'][ $active_lang ] ) : '';
$descripcion_ia = isset( $datos_decoded['descripcion_ia'][ $active_lang ] ) ? $datos_decoded['descripcion_ia'][ $active_lang ] : '';
$meta_description_full = $sugerencias_busqueda . ' ' . $descripcion_ia;

// Se recomienda que las meta descripciones tengan entre 150 y 160 caracteres
$meta_description = mb_substr( wp_strip_all_tags( $meta_description_full ), 0, 160 );
$meta_description = esc_attr( $meta_description );

add_action( 'wp_head', function () use ( $meta_description ) {
    if ( ! empty( $meta_description ) ) {
        echo '<meta name="description" content="' . $meta_description . '">' . "\n";
    }
}, 1 );

// Esquema JSON-LD
$schema = [
    "@context"    => "https://schema.org",
    "@type"       => "AudioObject",
    "name"        => $seo_title,
    "description" => $meta_description,
    "datePublished"=> get_the_date( 'c' ),
    "author"      => [
        "@type" => "Person",
        "name"  => get_the_author()
    ]
];
if ( ! empty( $datos_decoded ) ) {
    if ( isset( $datos_decoded['descripcion_ia'][ $active_lang ] ) ) {
        $schema['description'] = esc_html( $datos_decoded['descripcion_ia'][ $active_lang ] );
    }
    if ( isset( $datos_decoded['genero_posible'][ $active_lang ] ) ) {
        $schema['genre'] = esc_html( implode( ", ", $datos_decoded['genero_posible'][ $active_lang ] ) );
    }
    if ( isset( $datos_decoded['tags_posibles'][ $active_lang ] ) ) {
        $schema['keywords'] = esc_html( implode( ", ", $datos_decoded['tags_posibles'][ $active_lang ] ) );
    }
}
add_action( 'wp_head', function () use ( $schema ) {
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 2 );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php get_header(); ?>

<main id="main">
    <div id="content" class="<?php echo esc_attr( ! is_user_logged_in() ? 'nologin' : '' ); ?>">
        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
            <article <?php post_class(); ?>>
                <h1><?php echo esc_html( $post_title ); ?></h1>
                <h2><?php echo esc_html( $seo_title ); ?></h2>
                <div class="single">
                    <div class="fullH">
                        <?php echo htmlPost( $filtro ); ?>
                    </div>
                    <div class="publicaciones-similares" nosnippet>
                        <h3>Publicaciones Similares</h3>
                        <?php
                        echo publicaciones( [
                            'filtro'      => 'nada',
                            'posts'       => 10,
                            'similar_to'  => $current_post_id,
                        ] );
                        ?>
                    </div>
                </div>
            </article>
        <?php endwhile; endif; ?>
    </div>
</main>

<?php get_footer(); ?>

</body>
</html>