<?php get_header(); ?>
<?php
$user_id = get_current_user_id();
$acciones = get_user_meta($user_id, 'acciones', true);
$nologin_class = !is_user_logged_in() ? ' nologin' : '';

if (have_posts()) :
    while (have_posts()) : the_post();
        // Obtener el ID del post actual ANTES de usarlo
        $current_post_id = get_the_ID();
        $filtro = 'single';
        
        // Obtener los datos del algoritmo
        $datosAlgoritmo = get_post_meta($current_post_id, 'datosAlgoritmo', true);
        $datos_decoded = json_decode($datosAlgoritmo, true);

        // Verificar si los datos existen y tienen el formato correcto
        if ($datos_decoded && is_array($datos_decoded)) {
            // Determinar qué clave usar para la descripción
            $descripcion = isset($datos_decoded['descripcion_ia_pro']['es']) 
                ? $datos_decoded['descripcion_ia_pro']['es'] 
                : (isset($datos_decoded['descripcion_ia']['es']) 
                    ? $datos_decoded['descripcion_ia']['es'] 
                    : '');

            // Verificar y procesar los tags
            $tags = isset($datos_decoded['tags_posibles']['es']) && is_array($datos_decoded['tags_posibles']['es'])
                ? implode(", ", $datos_decoded['tags_posibles']['es'])
                : '';

            $schema = array(
                "@context" => "https://schema.org",
                "@type" => "AudioObject",
                "name" => get_the_title(),
                "description" => $descripcion,
                "genre" => isset($datos_decoded['genero_posible']['es']) ? $datos_decoded['genero_posible']['es'] : '',
                "keywords" => $tags,
                "datePublished" => get_the_date('c'),
                "author" => array(
                    "@type" => "Person",
                    "name" => get_the_author()
                )
            );
        }

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
                        // Llamar a la función 'publicaciones' pasando el ID del post actual
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