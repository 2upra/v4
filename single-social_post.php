<?php
get_header();

/*
    [03-Nov-2024 05:11:03 UTC] PHP Fatal error:  Uncaught TypeError: json_decode(): Argument #1 ($json) must be of type string, array given in /var/www/wordpress/wp-content/themes/2upra3v/app/Logic/manejadorPosts.php:160
    Stack trace:
    #0 /var/www/wordpress/wp-content/themes/2upra3v/app/Logic/manejadorPosts.php(160): json_decode()
    #1 /var/www/wordpress/wp-content/themes/2upra3v/app/Logic/manejadorPosts.php(145): configurarSimilarTo()
    #2 /var/www/wordpress/wp-content/themes/2upra3v/app/Logic/manejadorPosts.php(17): configuracionQueryArgs()
    #3 /var/www/wordpress/wp-content/themes/2upra3v/single-social_post.php(121): publicaciones()
    #4 /var/www/wordpress/wp-includes/template-loader.php(106): include('...')
    #5 /var/www/wordpress/wp-blog-header.php(19): require_once('...')
    #6 /var/www/wordpress/index.php(17): require('...')
    #7 {main}
      thrown in /var/www/wordpress/wp-content/themes/2upra3v/app/Logic/manejadorPosts.php on line 160
*/

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
    // Puedes expandir esto para otros idiomas si es necesario
    return 'en';
}

$active_lang = get_active_language();

if (have_posts()) :
    while (have_posts()) : the_post();
        $current_post_id = get_the_ID();
        $filtro = 'single';

        // Obtener y decodificar los datos del algoritmo
        $datosAlgoritmo = get_post_meta($current_post_id, 'datosAlgoritmo', true);

        // Verifica si ya es un array y si no, intenta decodificarlo
        if (is_string($datosAlgoritmo)) {
            $datos_decoded = json_decode($datosAlgoritmo, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Registro del error para depuración
                error_log('Error al decodificar JSON en datosAlgoritmo: ' . json_last_error_msg());
                $datos_decoded = [];
            }
        } elseif (is_array($datosAlgoritmo)) {
            $datos_decoded = $datosAlgoritmo;
        } else {
            $datos_decoded = [];
        }

        // Obtener las sugerencias de búsqueda según el idioma activo
        $sugerencias_busqueda = isset($datos_decoded['sugerencia_busqueda'][$active_lang]) ? $datos_decoded['sugerencia_busqueda'][$active_lang] : [];
        $sugerencias_busqueda = is_array($sugerencias_busqueda) ? array_slice($sugerencias_busqueda, 0, 2) : [];

        // Generar el título SEO usando las dos primeras sugerencias de búsqueda
        if (!empty($sugerencias_busqueda)) {
            $seo_title = implode(', ', array_map('esc_html', $sugerencias_busqueda));
        } else {
            $seo_title = get_the_title();
        }

        // Añadir el tipo de audio al título si está disponible
        if (isset($datos_decoded['tipo_audio'][$active_lang][0])) {
            $tipo_audio = ' | ' . esc_html($datos_decoded['tipo_audio'][$active_lang][0]);
            $seo_title .= $tipo_audio;
        }

        // Establecer el título de la página
        add_filter('pre_get_document_title', function () use ($seo_title) {
            return $seo_title;
        });

        // Obtener la descripción SEO usando 'descripcion_ia' según el idioma
        if (isset($datos_decoded['descripcion_ia'][$active_lang])) {
            $meta_description = esc_attr($datos_decoded['descripcion_ia'][$active_lang]);
        } else {
            $meta_description = esc_attr(get_the_excerpt() ?: get_the_title());
        }

        // Establecer la meta descripción
        add_action('wp_head', function () use ($meta_description) {
            echo '<meta name="description" content="' . $meta_description . '">';
        });

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

        // Añadir datos adicionales al esquema si existen
        if (!empty($datos_decoded)) {
            // Añadir descripción detallada si está disponible
            if (isset($datos_decoded['descripcion_ia'][$active_lang])) {
                $schema['description'] = esc_html($datos_decoded['descripcion_ia'][$active_lang]);
            }

            // Añadir género si está disponible
            if (isset($datos_decoded['genero_posible'][$active_lang])) {
                $schema['genre'] = esc_html(implode(", ", $datos_decoded['genero_posible'][$active_lang]));
            }

            // Añadir palabras clave si están disponibles
            if (isset($datos_decoded['tags_posibles'][$active_lang])) {
                $schema['keywords'] = esc_html(implode(", ", $datos_decoded['tags_posibles'][$active_lang]));
            }
        }

        // Imprimir el esquema JSON-LD en el head
        add_action('wp_head', function () use ($schema) {
            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
        });

        // Capturar el contenido principal
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

get_footer();
?>