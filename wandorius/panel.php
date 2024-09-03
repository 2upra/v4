<?php
//PANEL NUEVO 2.0
function panel()
{
    ob_start();
    $user = wp_get_current_user();
    $nombre_usuario = $user->display_name;
    $url_imagen_perfil = obtener_url_imagen_perfil_o_defecto($user->ID);

    // Aplicar Jetpack Photon para optimizar la imagen de perfil
    if (function_exists('jetpack_photon_url')) {
        $url_imagen_perfil = jetpack_photon_url($url_imagen_perfil, array('quality' => 40, 'strip' => 'all'));
    }

    // $tiene_posts = get_user_meta($user->ID, 'tiene_posts_en_rola', true);
    // $tiene_colabs_pendientes = get_user_meta($user->ID, 'tiene_colabs_pendientes', true);
    $user_id = get_current_user_id();
    $fan_meta = get_user_meta($user_id, 'fan', true);

    $zona_horaria_usuario = isset($_COOKIE['usuario_zona_horaria']) ? $_COOKIE['usuario_zona_horaria'] : 'UTC';
    $datetime = new DateTime('now', new DateTimeZone($zona_horaria_usuario));
    $hour = $datetime->format('G');

    if ($hour < 6) {
        $greeting = 'Buenas noches';
    } elseif ($hour < 12) {
        $greeting = 'Buenos días';
    } elseif ($hour < 18) {
        $greeting = 'Buenas tardes';
    } else {
        $greeting = 'Buenas noches';
    }

    ?>

    <div class="P6LANGAN">

        <div class="tabs">
            <div class="tab-content">
                <div class="tab active" id="rolas" data-post-id="tab1-posts" data-id="unico1">
                    <?php if ($fan_meta !== '1'): ?>
                        <p class="titulorolasenviadas">Tus rolas enviadas</p>
                        <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="rolastatus" tab_id="tab1-posts"]'); ?>
                    <?php else: ?>
                        <p class="titulorolasenviadas">Rolas que te gustan</p>
                        <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="likes1" tab_id="tab1-posts"]'); ?>
                    <?php endif; ?>
                </div>

                <div class="tab" id="eliminadas" data-post-id="tab2-posts" data-id="unico2">
                    <?php if ($fan_meta !== '1'): ?>
                        <p class="titulorolasenviadas">Tus rolas eliminadas</p>
                        <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="rolasEliminadas" tab_id="tab2-posts"]'); ?>
                    <?php else: ?>
                        <p class="titulorolasenviadas">Rolas que te gustan</p>
                        <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="likes1" tab_id="tab2-posts"]'); ?>
                    <?php endif; ?>
                </div>

                <div class="tab" id="rechazadas" data-post-id="tab3-posts" data-id="unico3">
                    <?php if ($fan_meta !== '1'): ?>
                        <p class="titulorolasenviadas">Rolas rechazadas</p>
                        <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="rolasRechazadas" tab_id="tab3-posts"]'); ?>
                    <?php else: ?>
                        <p class="titulorolasenviadas">Rolas que te gustan</p>
                        <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="likes1" tab_id="tab3-posts"]'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('panel', 'panel');



function cambiar_tipo_usuario_callback()
{
    $user_id = get_current_user_id();
    $tipo = $_POST['tipo'];

    if ($tipo === 'fan') {
        $estado_actual = get_user_meta($user_id, 'fan', true);
        update_user_meta($user_id, 'fan', !$estado_actual);
    }

    echo !$estado_actual;
    wp_die();
}

add_action('wp_ajax_cambiar_tipo_usuario', 'cambiar_tipo_usuario_callback');
add_action('wp_ajax_nopriv_cambiar_tipo_usuario', 'cambiar_tipo_usuario_callback');
