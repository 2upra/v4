<?

add_action('wp_ajax_buscarResultado', 'buscar_resultados');
add_action('wp_ajax_nopriv_buscarResultado', 'buscar_resultados');

function buscar_resultados()
{

    $texto = sanitize_text_field($_POST['busqueda']);
    $cache_key = 'resultados_busqueda_' . md5($texto);
    //$resultados_cache = obtenerCache($cache_key);

    //if ($resultados_cache !== false) {
    //   wp_send_json(array('success' => true, 'data' => $resultados_cache));
    //    return;
    // }

    ob_start();

    $resultados = array(
        'social_post' => array(),
        'colecciones' => array(),
        'perfiles'    => array(),
    );

    // Buscar en social_post
    $args_social = array(
        'post_type'      => 'social_post',
        'post_status'    => 'publish',
        's'              => $texto,
        'posts_per_page' => 3,
    );
    $query_social = new WP_Query($args_social);
    if ($query_social->have_posts()) {
        while ($query_social->have_posts()) {
            $query_social->the_post();
            $resultados['social_post'][] = array(
                'titulo' => get_the_title(),
                'url'    => get_permalink(),
                'tipo'   => 'Social Post',
            );
        }
    }
    wp_reset_postdata();

    // Buscar en colecciones
    $args_colecciones = array(
        'post_type'      => 'colecciones',
        'post_status'    => 'publish',
        's'              => $texto,
        'posts_per_page' => 3,
    );
    $query_colecciones = new WP_Query($args_colecciones);
    if ($query_colecciones->have_posts()) {
        while ($query_colecciones->have_posts()) {
            $query_colecciones->the_post();
            $resultados['colecciones'][] = array(
                'titulo' => get_the_title(),
                'url'    => get_permalink(),
                'tipo'   => 'Colección',
            );
        }
    }
    wp_reset_postdata();

    // Buscar usuarios
    $user_query = new WP_User_Query(array(
        'search'         => '*' . esc_attr($texto) . '*',
        'search_columns' => array(
            'user_login',
            'display_name',
        ),
        'number'         => 3,
    ));
    $users = $user_query->get_results();
    if (!empty($users)) {
        foreach ($users as $user) {
            $resultados['perfiles'][] = array(
                'titulo' => $user->display_name,
                'url'    => get_author_posts_url($user->ID),
                'tipo'   => 'Perfil',
            );
        }
    }

    // Balancear resultados
    $num_resultados = count($resultados['social_post']) + count($resultados['colecciones']) + count($resultados['perfiles']);
    if ($num_resultados > 6) {
        $social_post_count = count($resultados['social_post']);
        $colecciones_count = count($resultados['colecciones']);
        $perfiles_count = count($resultados['perfiles']);

        $max_each = 2;

        if ($social_post_count < $max_each) {
            $diff = $max_each - $social_post_count;
            $colecciones_count >= $max_each + $diff ? $max_each += $diff : ($perfiles_count >= $max_each + $diff ? $max_each += $diff : null);
        }
        if ($colecciones_count < $max_each) {
            $diff = $max_each - $colecciones_count;
            $social_post_count >= $max_each + $diff ? $max_each += $diff : ($perfiles_count >= $max_each + $diff ? $max_each += $diff : null);
        }
        if ($perfiles_count < $max_each) {
            $diff = $max_each - $perfiles_count;
            $social_post_count >= $max_each + $diff ? $max_each += $diff : ($colecciones_count >= $max_each + $diff ? $max_each += $diff : null);
        }

        $resultados['social_post'] = array_slice($resultados['social_post'], 0, $max_each);
        $resultados['colecciones'] = array_slice($resultados['colecciones'], 0, $max_each);
        $resultados['perfiles'] = array_slice($resultados['perfiles'], 0, $max_each);
    }

    // Generar HTML
    foreach ($resultados as $tipo_grupo => $grupo) {
        foreach ($grupo as $resultado) {
?>
            <div class="resultado-item">
                <a href="<?php echo $resultado['url']; ?>"><?php echo $resultado['titulo']; ?></a>
                <p><?php echo $resultado['tipo']; ?></p>
            </div>
        <?php
        }
    }

    if ($num_resultados === 0) {
        ?>
        <div class="resultado-item">No se encontraron resultados.</div>
<?php
    }

    $html = ob_get_clean();
    //guardarCache($cache_key, $html, 7200); // Guardar en caché por 2 horas (7200 segundos)
    wp_send_json(array('success' => true, 'data' => $html));
}
