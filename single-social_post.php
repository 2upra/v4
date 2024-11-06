<?php
// Obtener el ID del usuario actual y otras meta
$user_id = get_current_user_id();
$acciones = get_user_meta($user_id, 'acciones', true);
$nologin_class = !is_user_logged_in() ? ' nologin' : '';

// Función para determinar el idioma activo
function get_active_language()
{
    $locale = get_locale();
    if (strpos($locale, 'es') === 0) {
        return 'es';
    }
    return 'en';
}

// Función de debugging para meta descriptions
function debug_meta_description()
{
    global $wp_filter;
    if (isset($wp_filter['wp_head'])) {
        error_log('WP Head Hooks: ' . print_r($wp_filter['wp_head'], true));
    }
}
add_action('init', 'debug_meta_description');

$active_lang = get_active_language();

if (have_posts()) :
    while (have_posts()) : the_post();
        $current_post_id = get_the_ID();
        $filtro = 'single';

        // Obtener y decodificar los datos del algoritmo
        $datosAlgoritmo = get_post_meta($current_post_id, 'datosAlgoritmo', true);

        // Log de datos originales
        error_log('Datos Algoritmo Original: ' . print_r($datosAlgoritmo, true));

        // Verifica si ya es un array y si no, intenta decodificarlo
        if (is_string($datosAlgoritmo)) {
            $datos_decoded = json_decode($datosAlgoritmo, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Error al decodificar JSON en datosAlgoritmo en single post: ' . json_last_error_msg());
                $datos_decoded = [];
            }
        } elseif (is_array($datosAlgoritmo)) {
            $datos_decoded = $datosAlgoritmo;
        } else {
            $datos_decoded = [];
        }

        // Log de datos decodificados
        error_log('Datos Decodificados: ' . print_r($datos_decoded, true));

        // Obtener las sugerencias de búsqueda
        $sugerencias_busqueda = isset($datos_decoded['sugerencia_busqueda'][$active_lang])
            ? $datos_decoded['sugerencia_busqueda'][$active_lang]
            : [];
        $sugerencias_busqueda = is_array($sugerencias_busqueda) ? array_slice($sugerencias_busqueda, 0, 2) : [];

        // Generar el título SEO
        if (!empty($sugerencias_busqueda)) {
            $seo_title = implode(', ', array_map('esc_html', $sugerencias_busqueda));
        } else {
            $seo_title = get_the_title();
        }

        // Añadir tipo de audio al título
        if (isset($datos_decoded['tipo_audio'][$active_lang][0])) {
            $tipo_audio = ' | ' . esc_html($datos_decoded['tipo_audio'][$active_lang][0]);
            $seo_title .= $tipo_audio;
        }

        // Establecer título
        add_filter('pre_get_document_title', function () use ($seo_title) {
            return $seo_title;
        });

        // Obtener y verificar la meta descripción
        if (isset($datos_decoded['descripcion_ia'][$active_lang])) {
            $meta_description = esc_attr($datos_decoded['descripcion_ia'][$active_lang]);
            error_log('Meta Description from IA: ' . $meta_description);
        } else {
            $meta_description = esc_attr(wp_trim_words(get_the_content(), 25));
            error_log('Using fallback meta description');
        }

        // Establecer meta descripción con debugging
        add_action('wp_head', function () use ($meta_description, $active_lang) {
            // Debug info para administradores
            if (current_user_can('administrator')) {
                echo "<!-- Debug Meta Description:\n";
                echo "Active Language: " . $active_lang . "\n";
                echo "Description: " . $meta_description . "\n";
                echo "-->";
            }

            if (!empty($meta_description)) {
                echo '<meta name="description" content="' . $meta_description . '">';
            } else {
                error_log('Warning: Meta description is empty');
            }
        }, 1);

        // Preparar el esquema JSON-LD
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "AudioObject",
            "name" => $seo_title,
            "description" => $meta_description,
            "datePublished" => get_the_date('c'),
            "author" => [
                "@type" => "Person",
                "name" => get_the_author()
            ]
        ];

        // Añadir datos adicionales al esquema
        if (!empty($datos_decoded)) {
            if (isset($datos_decoded['descripcion_ia'][$active_lang])) {
                $schema['description'] = esc_html($datos_decoded['descripcion_ia'][$active_lang]);
            }
            if (isset($datos_decoded['genero_posible'][$active_lang])) {
                $schema['genre'] = esc_html(implode(", ", $datos_decoded['genero_posible'][$active_lang]));
            }
            if (isset($datos_decoded['tags_posibles'][$active_lang])) {
                $schema['keywords'] = esc_html(implode(", ", $datos_decoded['tags_posibles'][$active_lang]));
            }
        }

        // Imprimir el esquema JSON-LD con debugging
        add_action('wp_head', function () use ($schema) {
            echo "<!-- Schema Debug Start -->\n";
            echo '<script type="application/ld+json">' .
                wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) .
                '</script>';
            echo "\n<!-- Schema Debug End -->";
        }, 2);

        // Verificación adicional de meta tags
        add_action('wp_head', function () {
            echo "<!-- Meta Tags Verification Start -->\n";
            $meta_tags = get_meta_tags(get_permalink());
            if ($meta_tags) {
                echo "<!-- Found Meta Tags: " . print_r($meta_tags, true) . " -->";
            }
            echo "\n<!-- Meta Tags Verification End -->";
        }, 3);

        get_header();
        ob_start();
?>
        <div id="main">
            <div id="content" class="<?php echo esc_attr($nologin_class); ?>">
                <div class="single">
                    <div class="fullH">
                        <?php echo htmlPost($filtro); ?>
                    </div>
                    <!-- Publicaciones Similares -->
                    <div class="publicaciones-similares" nosnippet>
                        <h3><?php echo ($active_lang === 'es') ? 'Publicaciones Similares' : 'Similar Posts'; ?></h3>
                        <?php
                        echo publicaciones([
                            'filtro' => 'nada',
                            'posts' => 10,
                            'similar_to' => $current_post_id,
                        ]);
                        ?>
                    </div>
                </div>
            </div>
        </div>
<?php
        $contenido = ob_get_clean();
        echo $contenido;
    endwhile;
else :
    echo '<p>' . (($active_lang === 'es') ? 'No se encontró el contenido.' : 'Content not found.') . '</p>';
endif;

// Función para verificar la presencia de meta descriptions
function verify_meta_description()
{
    $html = get_echo('wp_head');
    if (strpos($html, 'meta name="description"') === false) {
        error_log('Meta description tag not found in wp_head');
    }
}
add_action('shutdown', 'verify_meta_description');

// Función helper para capturar output
function get_echo($function)
{
    ob_start();
    $function();
    return ob_get_clean();
}

get_footer();
?>