<?php
// Asegúrate de que este archivo sea parte de un tema de WordPress y se use como plantilla adecuada.

// Evita el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sale si se intenta acceder directamente.
}

// Función para detectar el idioma del navegador.
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

// Obtener el idioma activo.
$active_lang = get_user_browser_language();

// Obtener el ID del post actual.
$current_post_id = get_the_ID();
$filtro = 'single';
$datosAlgoritmo = get_post_meta( $current_post_id, 'datosAlgoritmo', true );
$datos_decoded = is_string( $datosAlgoritmo ) ? json_decode( $datosAlgoritmo, true ) ?: [] : $datosAlgoritmo;

// Sugerencias de búsqueda y título SEO.
$sugerencias_busqueda = isset( $datos_decoded['sugerencia_busqueda'][ $active_lang ] ) ? array_slice( (array) $datos_decoded['sugerencia_busqueda'][ $active_lang ], 0, 2 ) : [];
$seo_title = ! empty( $sugerencias_busqueda ) ? implode( ', ', array_map( 'esc_html', $sugerencias_busqueda ) ) : get_the_title();
if ( isset( $datos_decoded['tipo_audio'][ $active_lang ][0] ) ) {
    $seo_title .= ' | ' . esc_html( $datos_decoded['tipo_audio'][ $active_lang ][0] );
}
add_filter( 'pre_get_document_title', function() use ( $seo_title ) {
    return $seo_title;
} );

// Meta descripción.
$meta_description = isset( $datos_decoded['descripcion_ia'][ $active_lang ] ) ? esc_attr( $datos_decoded['descripcion_ia'][ $active_lang ] ) : esc_attr( wp_trim_words( get_the_content(), 25 ) );
add_action( 'wp_head', function () use ( $meta_description, $active_lang ) {
    if ( current_user_can( 'administrator' ) ) {
        echo "<!-- Debug Meta Description:\nActive Language: " . esc_html( $active_lang ) . "\nDescription: " . esc_html( $meta_description ) . "\n-->\n";
    }
    if ( ! empty( $meta_description ) ) {
        echo '<meta name="description" content="' . esc_attr( $meta_description ) . '">' . "\n";
    }
}, 1 );

// Esquema JSON-LD.
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
<?php get_header(); ?>
<body <?php body_class(); ?>>

    <?php

    $user_id = get_current_user_id();
    $acciones = get_user_meta( $user_id, 'acciones', true );
    $nologin_class = ! is_user_logged_in() ? ' nologin' : '';

    // Comienza el loop de WordPress.
    if ( have_posts() ) :
        while ( have_posts() ) : the_post();
            ?>
            <div id="main">
                <div id="content" class="<?php echo esc_attr( $nologin_class ); ?>">
                    <div class="single">
                        <div class="fullH">
                            <?php echo htmlPost( $filtro ); // Asegúrate de que esta función esté definida correctamente. ?>
                        </div>
                        <div class="publicaciones-similares" nosnippet>
                            <h3>Publicaciones Similares</h3>
                            <?php
                            // Asegúrate de que la función 'publicaciones' esté correctamente definida en tu tema o en un plugin.
                            echo publicaciones( [
                                'filtro'      => 'nada',
                                'posts'       => 10,
                                'similar_to'  => $current_post_id,
                            ] );
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        endwhile;
    endif;

    // Incluir el footer de WordPress.
    get_footer();
    ?>

</body>
</html>