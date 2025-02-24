<?php
if (! defined('ABSPATH')) {
    exit;
}

// Función para obtener el idioma preferido del navegador
function obtenerIdiomaDelNavegador()
{
    if (! isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        return 'en';
    }
    $accepted_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    foreach ($accepted_languages as $language) {
        $lang = substr($language, 0, 2);
        if (in_array($lang, ['es', 'en'])) {
            return $lang;
        }
    }
    return 'en';
}

$active_lang      = obtenerIdiomaDelNavegador();
$current_post_id  = get_the_ID();
$filtro           = 'single';

$datos_algoritmo_pri    = get_post_meta($current_post_id, 'datosAlgoritmo', true);
$datos_algoritmo_respaldo = get_post_meta($current_post_id, 'datosAlgoritmo_respaldo', true);

$datosAlgoritmo = empty($datos_algoritmo_pri) ? $datos_algoritmo_respaldo : $datos_algoritmo_pri;
$datos_decoded  = is_string($datosAlgoritmo) ? json_decode($datosAlgoritmo, true) : $datosAlgoritmo;

// Generar el título SEO
$post_title = get_the_title();
$tipo_audio = isset($datos_decoded['tipo_audio'][$active_lang][0]) ? $datos_decoded['tipo_audio'][$active_lang][0] : 'Sample';

// Asegurarse de que el título contenga "Sample" (evitando duplicados)
if (stripos($tipo_audio, 'sample') === false) {
    $tipo_audio .= ' Sample';
}

// Añadir el sufijo de "free" o "gratis" según el idioma, si aún no está presente
$seo_suffix = ($active_lang === 'es') ? 'gratis' : 'free';
if (stripos($tipo_audio, $seo_suffix) === false) {
    $tipo_audio .= ' ' . $seo_suffix;
}

$seo_title = $post_title . ' | ' . $tipo_audio;
add_action('wp_head', function () use ($seo_title) {
    echo '<title>' . esc_html($seo_title) . '</title>' . "\n";
}, 1);

// Meta descripción
$sugerencias_busqueda = isset($datos_decoded['sugerencia_busqueda'][$active_lang]) ? implode(', ', $datos_decoded['sugerencia_busqueda'][$active_lang]) : '';
$descripcion_ia       = isset($datos_decoded['descripcion_ia'][$active_lang]) ? $datos_decoded['descripcion_ia'][$active_lang] : '';

// Añadir frase final en español para SEO en la descripción
$desc_suffix = ($active_lang === 'es') ? ' descargar samples gratis' : '';

// Para asegurar que la descripción no supere los 160 caracteres y conserve el sufijo
$base_desc       = trim($sugerencias_busqueda . ' ' . $descripcion_ia);
$available_length = 160 - mb_strlen($desc_suffix);
$base_desc_truncated = mb_substr($base_desc, 0, $available_length);
$meta_description = esc_attr(trim($base_desc_truncated . $desc_suffix));

// Añadir la meta descripción en el <head>
add_action('wp_head', function () use ($meta_description) {
    if (! empty($meta_description)) {
        echo '<meta name="description" content="' . $meta_description . '">' . "\n";
    }
}, 1);

// Esquema JSON-LD
$schema = [
    "@context"     => "https://schema.org",
    "@type"        => "AudioObject",
    "name"         => $seo_title,
    "description"  => $meta_description,
    "datePublished" => get_the_date('c'),
    "author"       => [
        "@type" => "Person",
        "name"  => get_the_author()
    ]
];

if (! empty($datos_decoded)) {
    if (isset($datos_decoded['descripcion_ia'][$active_lang])) {
        $schema['description'] = esc_html($datos_decoded['descripcion_ia'][$active_lang]);
    }
    if (isset($datos_decoded['genero_posible'][$active_lang])) {
        $schema['genre'] = esc_html(implode(", ", $datos_decoded['genero_posible'][$active_lang]));
    }
    if (isset($datos_decoded['tags_posibles'][$active_lang])) {
        // Limitar a 4 tags
        $tags_array = array_slice($datos_decoded['tags_posibles'][$active_lang], 0, 4);
        $schema['keywords'] = esc_html(implode(", ", $tags_array));
    }
}

// Añadir el esquema JSON-LD al <head>
add_action('wp_head', function () use ($schema) {
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}, 2);
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
            <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                    <article <?php post_class(); ?>>
                        <h1 style="font-size: 0px;"><?php echo esc_html($post_title); ?></h1>
                        <h2 style="font-size: 0px;"><?php echo esc_html($seo_title); ?></h2>
                        <div class="single">
                            <div class="fullH">
                                <?php echo htmlPost($filtro); ?>
                            </div>
                            <div class="publicaciones-similares" nosnippet>
                                <h3 style="display: none;">Publicaciones Similares</h3>
                                <?php
                                echo publicaciones([
                                    'filtro'     => 'nada',
                                    'posts'      => 10,
                                    'similar_to' => $current_post_id,
                                ]);
                                ?>
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