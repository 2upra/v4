<?php get_header(); ?>
<?php
$user_id = get_current_user_id();
$acciones = get_user_meta($user_id, 'acciones', true);
$nologin_class = !is_user_logged_in() ? ' nologin' : '';

if (have_posts()) :
    while (have_posts()) : the_post();
        $current_post_id = get_the_ID();
        $filtro = 'single';

        // Obtener datos del algoritmo
        $datosAlgoritmo = get_post_meta($current_post_id, 'datosAlgoritmo', true);
        $datos_decoded = !empty($datosAlgoritmo) ? json_decode($datosAlgoritmo, true) : null;

        // Preparar el tipo de audio para el título si está disponible
        $tipo_audio = '';
        if (isset($datos_decoded['tipo_audio']['es'][0])) {
            $tipo_audio = ' | ' . esc_html($datos_decoded['tipo_audio']['es'][0]);
        }

        // Generar el título para SEO (post title + tipo de audio)
        $seo_title = get_the_title() . $tipo_audio;
        echo '<title>' . esc_attr($seo_title) . '</title>';

        // Meta description optimizada
        $seo_description = get_post_meta($current_post_id, 'sugerencia_busqueda', true);
        if (isset($seo_description['es'][0])) {
            $meta_description = esc_attr($seo_description['es'][0]);
        } else {
            $meta_description = esc_attr(get_the_excerpt() ?: get_the_title());
        }
        echo '<meta name="description" content="' . $meta_description . '">';

        // Inicializar el schema básico
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

        // Añadir datos adicionales al schema si existen
        if ($datos_decoded) {
            // Añadir descripción
            if (isset($datos_decoded['descripcion_ia']['es'])) {
                $schema['description'] = $datos_decoded['descripcion_ia']['es'];
            }

            // Añadir género y tags si están disponibles
            $schema['genre'] = $datos_decoded['genero_posible']['es'] ?? '';
            $schema['keywords'] = isset($datos_decoded['tags_posibles']['es']) 
                ? implode(", ", $datos_decoded['tags_posibles']['es']) 
                : '';
        }

        // Imprimir el schema en el head
        add_action('wp_head', function() use ($schema) {
            echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
        });

        ob_start();
?>
        <div id="main">
            <div id="content" class="<?php echo esc_attr($nologin_class); ?>">
                <div class="single">
                    <?php echo htmlPost($filtro); ?>

                    <!-- Publicaciones similares -->
                    <div class="publicaciones-similares">
                        <h3>Publicaciones Similares</h3>
                        <?php
                        echo publicaciones([
                            'filtro' => 'nada',
                            'posts' => 10,
                            'similar_to' => $current_post_id
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
    echo '<p>No se encontró el contenido.</p>';
endif;
?>

<?php get_footer(); ?>
