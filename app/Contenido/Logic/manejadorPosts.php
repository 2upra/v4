<?

/*necesito una forma de mostrar publicaciones similiares a un post en especifico, asi muestro los post normalmente, pero necesito una funcion que reciba un id de un post por ejemplo y muestre post similares en base al contenido de datosAlgoritmo, que son metas con valores y tags, se suelen ver asi

{"tags":["choir"],"bpm":140,"key":"B","scale":"minor","autor":{"id":"1","usuario":"1ndoryu","nombre":"Wandorius"},"descripcion_ia_pro":{"es":"Un breve sample vocal de un coro interpretando una armonía ascendente de cuatro acordes con un sonido etéreo y atmosférico.","en":"A short vocal sample of a choir performing a four-chord ascending harmony with an ethereal and atmospheric sound."},"instrumentos_posibles":{"es":["Coro"],"en":["Choir"]},"estado_animo":{"es":["Etéreo","Atmosférico","Angelical","Sereno","Espiritual"],"en":["Ethereal","Atmospheric","Angelic","Serene","Spiritual"]},"genero_posible":{"es":["Ambient","Clásica","New age","Banda sonora"],"en":["Ambient","Classical","New age","Soundtrack"]},"tipo_audio":{"es":["Sample","Loop","A capella"],"en":["Sample","Loop","A capella"]},"tags_posibles":{"es":["Voces","Armonía","Coros","Etéreo","Atmosférico","Angelical","Música celestial","Música relajante"],"en":["Vocals","Harmony","Choirs","Ethereal","Atmospheric","Angelic","Heavenly music","Relaxing music"]},"sugerencia_busqueda":{"es":["Sample de coro","Loop de coro","Voces angelicales","Música celestial","Atmósfera etérea","Música relajante para estudiar","Música para meditar","Música para yoga"],"en":["Choir sample","Choir loop","Angelic voices","Heavenly music","Ethereal atmosphere","Relaxing music for studying","Meditation music","Yoga music"]}}

*/

function publicaciones($args = [], $is_ajax = false, $paged = 1)
{
    $user_id = obtenerUserId($is_ajax);
    $current_user_id = get_current_user_id();

    $defaults = [
        'filtro' => '',
        'tab_id' => '',
        'posts' => 12,
        'exclude' => [],
        'post_type' => 'social_post',
    ];
    $args = array_merge($defaults, $args);
    $query_args = configuracionQueryArgs($args, $paged, $user_id, $current_user_id);
    $output = procesarPublicaciones($query_args, $args, $is_ajax);

    if ($is_ajax) {
        echo $output;
        die();
    } else {
        return $output;
    }
}

function configuracionQueryArgs($args, $paged, $user_id, $current_user_id)
{
    //si llega identifier es que es una busqueda
    $identifier = $_POST['identifier'] ?? '';
    $posts = $args['posts'];

    if ($args['post_type'] === 'social_post') {
        $posts_personalizados = calcularFeedPersonalizado($current_user_id);
        $post_ids = array_keys($posts_personalizados);

        if ($paged == 1) {
            $post_ids = array_slice($post_ids, 0, $posts);
        }

        $query_args = [
            'post_type' => $args['post_type'],
            'posts_per_page' => $posts,
            'paged' => $paged,
            'post__in' => $post_ids,
            'orderby' => 'post__in',
            'meta_query' => !empty($identifier) ? [['key' => 'datosAlgoritmo', 'value' => $identifier, 'compare' => 'LIKE']] : [],
        ];
    } else {
        $query_args = [
            'post_type' => $args['post_type'],
            'posts_per_page' => $posts,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC', 
        ];

    }

    if (!empty($args['exclude'])) {
        $query_args['post__not_in'] = $args['exclude'];
    }
    $query_args = aplicarFiltros($query_args, $args, $user_id, $current_user_id);
    return $query_args;
}



function procesarPublicaciones($query_args, $args, $is_ajax)
{
    ob_start();

    $query = new WP_Query($query_args);
    if ($query->have_posts()) {


        $filtro = !empty($args['identifier']) ? $args['identifier'] : $args['filtro'];
        $tipoPost = $args['post_type'];
        
        if (!wp_doing_ajax()) {
            $clase_extra = 'clase-' . esc_attr($filtro);
            if (in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes'])) {
                $clase_extra = 'clase-rolastatus';
            }

            echo '<ul class="social-post-list ' . esc_attr($clase_extra) . '" 
                  data-filtro="' . esc_attr($filtro) . '" 
                  data-posttype="' . esc_attr($tipoPost) . '" 
                  data-tab-id="' . esc_attr($args['tab_id']) . '">';
        }

        // Itera sobre los resultados de la consulta
        while ($query->have_posts()) {
            $query->the_post();

            if ($tipoPost === 'social_post') {
                echo htmlPost($filtro);
            } 
            elseif ($tipoPost === 'colab') {
                echo htmlColab($filtro);
            }
            else {
                echo '<p>Tipo de publicación no reconocido.</p>';
            }
        }

        if (!wp_doing_ajax()) {
            echo '</ul>';
        }
    } else {
        echo nohayPost($filtro, $is_ajax);
    }

    wp_reset_postdata();
    return ob_get_clean();
}


function obtenerUserId($is_ajax)
{
    if ($is_ajax && isset($_POST['user_id'])) {
        return sanitize_text_field($_POST['user_id']);
    }

    $url_segments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
    $indices = ['perfil', 'music', 'author', 'sello'];
    foreach ($indices as $index) {
        $pos = array_search($index, $url_segments);
        if ($pos !== false) {
            if ($index === 'sello') {
                return get_current_user_id();
            } elseif (isset($url_segments[$pos + 1])) {
                $usuario = get_user_by('slug', $url_segments[$pos + 1]);
                if ($usuario) return $usuario->ID;
            }
            break;
        }
    }

    return null;
}


function publicacionAjax()
{
    
    $paged = isset($_POST['paged']) ? (int) $_POST['paged'] : 1;
    $filtro = isset($_POST['filtro']) ? sanitize_text_field($_POST['filtro']) : '';
    $tipoPost = isset($_POST['posttype']) ? sanitize_text_field($_POST['posttype']) : '';
    $data_identifier = isset($_POST['identifier']) ? sanitize_text_field($_POST['identifier']) : '';
    $tab_id = isset($_POST['tab_id']) ? sanitize_text_field($_POST['tab_id']) : '';
    $user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';
    $publicacionesCargadas = isset($_POST['cargadas']) && is_array($_POST['cargadas'])
        ? array_map('intval', $_POST['cargadas'])
        : array();
    publicaciones(
        array(
            'filtro' => $filtro,
            'post_type' => $tipoPost,
            'tab_id' => $tab_id,
            'user_id' => $user_id,
            'identifier' => $data_identifier,
            'exclude' => $publicacionesCargadas
        ),
        true,
        $paged
    );
}

add_action('wp_ajax_cargar_mas_publicaciones', 'publicacionAjax');
add_action('wp_ajax_nopriv_cargar_mas_publicaciones', 'publicacionAjax');
