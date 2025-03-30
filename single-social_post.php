<?
if (! defined('ABSPATH')) {
    exit;
}

// Ejemplo de como veo el titulo y descripcion en el SEO:
//
// Titulo actual:
// "Memphis rap vocal sample | sample free"
//
// Descripcion actual:
// "memphis rap sample, hip hop vocal sample, rap beat download samples, beats and drum kits free"
//
// Se espera que la descripcion quede asi:
// "Memphis rap sample, hip hop vocal sample, rap beat - Download samples, beats and drum kits free"
// Es decir: la descripcion debe iniciar en mayusculas y, si supera 160 caracteres, terminar en "..."


$active_lang     = obtenerIdiomaDelNavegador();
$current_post_id = get_the_ID();
$filtro          = 'single';

$datos_algoritmo_pri      = get_post_meta($current_post_id, 'datosAlgoritmo', true);
$datos_algoritmo_respaldo = get_post_meta($current_post_id, 'datosAlgoritmo_respaldo', true);

$datosAlgoritmo = empty($datos_algoritmo_pri) ? $datos_algoritmo_respaldo : $datos_algoritmo_pri;
$datos_decoded  = is_string($datosAlgoritmo) ? json_decode($datosAlgoritmo, true) : $datosAlgoritmo;

// Generar el titulo SEO
$post_title = get_the_title();
$tipo_audio = isset($datos_decoded['tipo_audio'][$active_lang][0]) ? $datos_decoded['tipo_audio'][$active_lang][0] : '';

// Para evitar duplicar "Sample": si el titulo ya lo tiene, no se anade al tipo de audio
if (stripos($post_title, 'sample') === false && stripos($tipo_audio, 'sample') === false) {
    $tipo_audio .= ' Sample';
}

// Anadir el sufijo (en este ejemplo se usa "free" para ambos idiomas)
$seo_suffix = 'free';
if (stripos($tipo_audio, $seo_suffix) === false) {
    $tipo_audio .= ' ' . $seo_suffix;
}

$seo_title = $post_title . ' | ' . $tipo_audio;
add_action('wp_head', function () use ($seo_title) {
    echo '<title>' . esc_html($seo_title) . '</title>' . "
";
}, 1);

// Construir la parte base de la descripcion (usando sugerencias o descripcion corta)
$base_desc = '';
if (isset($datos_decoded['sugerencia_busqueda'][$active_lang]) && ! empty($datos_decoded['sugerencia_busqueda'][$active_lang])) {
    $base_desc = implode(', ', $datos_decoded['sugerencia_busqueda'][$active_lang]);
} elseif (isset($datos_decoded['descripcion_corta'][$active_lang]) && ! empty($datos_decoded['descripcion_corta'][$active_lang])) {
    $base_desc = implode(', ', $datos_decoded['descripcion_corta'][$active_lang]);
}

// Definir el sufijo fijo para la descripcion
$desc_suffix = 'Download samples, beats and drum kits free';

// Armar la descripcion final:
// - Se asegura que la parte base inicie en mayuscula
// - Se agrega el separador " - " antes del sufijo
$final_desc = ucfirst(trim($base_desc));
if (! empty($final_desc)) {
    $final_desc .= ' - ' . $desc_suffix;
} else {
    $final_desc = $desc_suffix;
}

// Si la descripcion excede los 160 caracteres, se trunca y se anade "..."
if (mb_strlen($final_desc) > 160) {
    $final_desc = mb_substr($final_desc, 0, 160 - 3) . '...';
}

$meta_description = esc_attr(trim($final_desc));

// Anadir la meta descripcion en el <head>
add_action('wp_head', function () use ($meta_description) {
    if (! empty($meta_description)) {
        echo '<meta name="description" content="' . $meta_description . '">' . "
";
    }
}, 1);

// Esquema JSON-LD
$schema = [
    "@context"      => "https://schema.org",
    "@type"         => "AudioObject",
    "name"          => $seo_title,
    "description"   => $meta_description,
    "datePublished" => get_the_date('c'),
    "author"        => [
        "@type" => "Person",
        "name"  => get_the_author()
    ]
];

if (! empty($datos_decoded)) {
    if (isset($datos_decoded['genero_posible'][$active_lang])) {
        $schema['genre'] = esc_html(implode(", ", $datos_decoded['genero_posible'][$active_lang]));
    }
    if (isset($datos_decoded['tags_posibles'][$active_lang])) {
        // Limitar a 5 tags para el SEO
        $tags_array         = array_slice($datos_decoded['tags_posibles'][$active_lang], 0, 5);
        $schema['keywords'] = esc_html(implode(", ", $tags_array));
    }
}

// Anadir el esquema JSON-LD al <head>
add_action('wp_head', function () use ($schema) {
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "
";
}, 2);
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
            <? if (have_posts()) : while (have_posts()) : the_post(); ?>
                    <article <? post_class(); ?>>
                        <div style="
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            padding-top: 20px;
                            background: #000000;
                            margin-bottom: -20px;">
                            <h1 style="font-size: 14px;"><? echo esc_html($post_title); ?></h1>
                            <h2 style="font-size: 12px;FONT-WEIGHT: 400;;"><? echo esc_html($seo_title); ?></h2>
                        </div>
                        <div class="single">
                            <div class="fullH">
                                <? echo htmlPost($filtro); ?>
                            </div>
                            <div class="publicaciones-similares" nosnippet>
                                <h3 style="display: none;">Publicaciones Similares</h3>
                                <?
                                echo publicaciones([
                                    'filtro'     => 'nada',
                                    'posts'      => 10,
                                    'similar_to' => $current_post_id,
                                ]);
                                ?>
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