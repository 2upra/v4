<?php
// Obtener el ID del usuario actual y otras meta
$user_id = get_current_user_id();
$acciones = get_user_meta($user_id, 'acciones', true);
$nologin_class = !is_user_logged_in() ? ' nologin' : '';

// Función para determinar el idioma activo
function get_user_browser_language()
{
    if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
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

// Función segura para obtener meta tags
function get_meta_tags_safely($url)
{
    try {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $content = curl_exec($ch);
        curl_close($ch);

        if ($content === false) {
            return [];
        }

        return get_meta_tags($url) ?: [];
    } catch (Exception $e) {
        error_log("Error getting meta tags: " . $e->getMessage());
        return [];
    }
}

// Función de debugging para meta descriptions
function debug_meta_description()
{
    if (WP_DEBUG && current_user_can('administrator')) {
        global $wp_filter;
        if (isset($wp_filter['wp_head'])) {
            error_log('WP Head Hooks: ' . print_r($wp_filter['wp_head'], true));
        }
    }
}
add_action('init', 'debug_meta_description');

$active_lang = get_user_browser_language();

if (have_posts()) :
    while (have_posts()) : the_post();
        $current_post_id = get_the_ID();
        $filtro = 'single';

        // Obtener y decodificar los datos del algoritmo
        $datosAlgoritmo = get_post_meta($current_post_id, 'datosAlgoritmo', true);
        $datos_decoded = [];

        if (!empty($datosAlgoritmo)) {
            if (is_string($datosAlgoritmo)) {
                $datos_decoded = json_decode($datosAlgoritmo, true) ?: [];
            } elseif (is_array($datosAlgoritmo)) {
                $datos_decoded = $datosAlgoritmo;
            }
        }

        // Obtener las sugerencias de búsqueda
        $sugerencias_busqueda = isset($datos_decoded['sugerencia_busqueda'][$active_lang])
            ? array_slice((array)$datos_decoded['sugerencia_busqueda'][$active_lang], 0, 2)
            : [];

        // Generar el título SEO
        $seo_title = !empty($sugerencias_busqueda)
            ? implode(', ', array_map('esc_html', $sugerencias_busqueda))
            : get_the_title();

        // Añadir tipo de audio al título
        if (isset($datos_decoded['tipo_audio'][$active_lang][0])) {
            $seo_title .= ' | ' . esc_html($datos_decoded['tipo_audio'][$active_lang][0]);
        }

        // Establecer título
        add_filter('pre_get_document_title', function () use ($seo_title) {
            return $seo_title;
        });

        // Obtener meta descripción
        $meta_description = isset($datos_decoded['descripcion_ia'][$active_lang])
            ? esc_attr($datos_decoded['descripcion_ia'][$active_lang])
            : esc_attr(wp_trim_words(get_the_content(), 25));

        // Establecer meta descripción
        add_action('wp_head', function () use ($meta_description, $active_lang) {
            if (current_user_can('administrator')) {
                echo "<!-- Debug Meta Description:\n";
                echo "Active Language: " . esc_html($active_lang) . "\n";
                echo "Description: " . esc_html($meta_description) . "\n";
                echo "-->\n";
            }

            if (!empty($meta_description)) {
                echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
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

        // Imprimir el esquema JSON-LD
        add_action('wp_head', function () use ($schema) {
            echo '<script type="application/ld+json">' .
                wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) .
                '</script>' . "\n";
        }, 2);

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
        $content = ob_get_clean();
        echo $content;

        // Función para verificar meta description
        function verify_meta_description()
        {
            if (WP_DEBUG) {
                $html = get_echo('wp_head');
                if (strpos($html, 'meta name="description"') === false) {
                    error_log('Meta description tag not found in wp_head');
                }
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
    endwhile;
endif;
?>