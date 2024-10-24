<?php get_header(); ?>
<?php
$user_id = get_current_user_id();
$acciones = get_user_meta($user_id, 'acciones', true);
$nologin_class = !is_user_logged_in() ? ' nologin' : '';

if (have_posts()) :
    while (have_posts()) : the_post();
        $current_post_id = get_the_ID();
        $filtro = 'single';
        
        // Inicializar el schema con datos básicos que siempre estarán disponibles
        $schema = array(
            "@context" => "https://schema.org",
            "@type" => "AudioObject",
            "name" => get_the_title(),
            "description" => get_the_excerpt() ?: get_the_title(), // Usar excerpt como fallback
            "datePublished" => get_the_date('c'),
            "author" => array(
                "@type" => "Person",
                "name" => get_the_author()
            )
        );

        // Intentar obtener los datos del algoritmo si existen
        $datosAlgoritmo = get_post_meta($current_post_id, 'datosAlgoritmo', true);
        if (!empty($datosAlgoritmo)) {
            $datos_decoded = json_decode($datosAlgoritmo, true);
            
            if ($datos_decoded && is_array($datos_decoded)) {
                // Actualizar la descripción si está disponible
                if (isset($datos_decoded['descripcion_ia_pro']['es'])) {
                    $schema['description'] = $datos_decoded['descripcion_ia_pro']['es'];
                } elseif (isset($datos_decoded['descripcion_ia']['es'])) {
                    $schema['description'] = $datos_decoded['descripcion_ia']['es'];
                }

                // Añadir género si está disponible
                if (isset($datos_decoded['genero_posible']['es'])) {
                    $schema['genre'] = $datos_decoded['genero_posible']['es'];
                }

                // Añadir tags si están disponibles
                if (isset($datos_decoded['tags_posibles']['es']) && is_array($datos_decoded['tags_posibles']['es'])) {
                    $schema['keywords'] = implode(", ", $datos_decoded['tags_posibles']['es']);
                }
            }
        }

        // Imprimir el schema en el head (puedes moverlo a donde sea necesario)
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