<?php get_header();

// Asegurarse de que tenemos los datos necesarios antes de mostrar la página
if (have_posts()) :
    while (have_posts()) : the_post();

        $user_id = get_current_user_id();
        $acciones = get_user_meta($user_id, 'acciones', true);
        $nologin_class = !is_user_logged_in() ? ' nologin' : '';
        $current_post_id = get_the_ID();

        // Obtener los datos del algoritmo (asumiendo que están en un campo personalizado)
        $datosAlgoritmo = get_post_meta($current_post_id, 'datosAlgoritmo', true);
        $datos_decoded = json_decode($datosAlgoritmo, true);

        // Preparar los datos estructurados para Schema.org
        $schema = array(
            "@context" => "https://schema.org",
            "@type" => "AudioObject",
            "name" => get_the_title(),
            "description" => $datos_decoded['descripcion_ia_pro']['es'],
            "genre" => $datos_decoded['genero_posible']['es'],
            "keywords" => implode(", ", $datos_decoded['tags_posibles']['es']),
            "datePublished" => get_the_date('c'),
            "author" => array(
                "@type" => "Person",
                "name" => get_the_author()
            )
        );
?>
        <!--- Metadatos estructurados -->


        <div id="content">
            <div class="mainPost">
                <article id="post-<?php echo $current_post_id; ?>" <?php post_class('single-social-post'); ?>>
                    <div class="content-wrapper<?php echo esc_attr($nologin_class); ?>">
                        <div class="single">
                            <?php echo htmlPost($filtro); ?>

                            <section class="publicaciones-similares">
                                <h2>Publicaciones Similares</h2>
                                <?php
                                echo publicaciones([
                                    'filtro' => 'nada',
                                    'posts' => 10,
                                    'similar_to' => $current_post_id
                                ]);
                                ?>
                            </section>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    <?php
    endwhile;
else:
    ?>
    <div class="no-content">
        <p>No se encontró el contenido solicitado.</p>
    </div>
<?php
endif;

get_footer();
?>