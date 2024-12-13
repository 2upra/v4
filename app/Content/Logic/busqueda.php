<?

add_action('wp_ajax_buscarResultado', 'buscar_resultados');
add_action('wp_ajax_nopriv_buscarResultado', 'buscar_resultados');

function buscar_resultados()
{
    $texto = sanitize_text_field($_POST['busqueda']);
    ob_start(); // Iniciar el buffer de salida
?>

    <?
    // Buscar en social_post y colecciones
    $args = array(
        'post_type'      => array('social_post', 'colecciones'),
        'post_status'    => 'publish',
        's'              => $texto,
        'posts_per_page' => 6,
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post();
            $tipo = get_post_type() === 'social_post' ? 'Social Post' : 'Colección';
    ?>
            <div class="resultado-item">
                <a href="<? the_permalink(); ?>"><? the_title(); ?></a>
                <p><? echo $tipo; ?></p>
            </div>
        <? endwhile; ?>
        <? endif;
    wp_reset_postdata();

    // Buscar usuarios
    $user_query = new WP_User_Query(array(
        'search'         => '*' . esc_attr($texto) . '*',
        'search_columns' => array(
            'user_login',
            'display_name',
        ),
    ));

    $users = $user_query->get_results();
    if (!empty($users)) :
        foreach ($users as $user) :
        ?>
            <div class="resultado-item">
                <a href="<? echo get_author_posts_url($user->ID); ?>"><? echo $user->display_name; ?></a>
                <p>Perfil</p>
            </div>
        <? endforeach; ?>
    <? endif; ?>

    <? if (!$query->have_posts() && empty($users)) : ?>
        <div class="resultado-item">No se encontraron resultados.</div>
    <? endif; ?>

<?
    $html = ob_get_clean(); // Obtener el contenido del buffer y limpiarlo
    wp_send_json(array('success' => true, 'data' => $html));
}
?>