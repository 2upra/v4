<?php

function perfil_pestanas() {
    ob_start();
    $user = wp_get_current_user();
    $nombre_usuario = $user->display_name; 
    $url_imagen_perfil = obtener_url_imagen_perfil_o_defecto($user->ID); 
    ?>
    <div class="tabs inicio perfil">
        <ul class="tab-links perfil">
            <li class="perfil-usuario">
                <a href="https://2upra.com/perfil" class="perfil-enlace">
                    <img src="<?php echo $url_imagen_perfil; ?>" alt="Perfil" style="width: 24px; height: 24px; border-radius: 50%;">
                    <?php echo $nombre_usuario; ?>
                    <?php echo do_shortcode('[mostrar_pinkys]'); ?>
                </a>
            </li>
        </ul> 

        <div class="tab-content inicio perfil">
            <div id="tab1" class="tab active">
                <?php echo do_shortcode('[social_post_form]') . do_shortcode('[mostrar_publicaciones_sociales filtro="no_bloqueado" tab_id="tab1-posts"]'); ?> 
            </div>
            <div id="tienda" class="tab">
                <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="venta" tab_id="tienda-posts"]'); ?>
            </div>
            <div id="tab3" class="tab">
                <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="solo_colab" tab_id="tab3-posts"]'); ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('perfil_pestanas', 'perfil_pestanas');





function inicio_pestanas() {
    ob_start();
    $user = wp_get_current_user();
    $nombre_usuario = $user->display_name; 
    $url_imagen_perfil = obtener_url_imagen_perfil_o_defecto($user->ID); 
    ?>
    <div class="tabs inicio">
        <ul class="tab-links">
            <li class="active"><a href="#tab1">Panel</a></li>
            <li><a href="#tab2">Feed</a></li>
            <li><a href="#tab3">Siguiendo</a></li>
        </ul>



            <div class="tab-content inicio">
            <div id="tab1" class="tab active" data-post-id="id1">
                <?php echo do_shortcode('[panel]'); ?>
            </div>

            <div id="tab2" class="tab" data-post-id="id2">
                <?php echo do_shortcode('[social_post_form]')  . do_shortcode('[mostrar_publicaciones_sociales filtro="no_bloqueado" tab_id="tab1-posts"]'); ?> 
            </div>

            <div id="tab3" class="tab" data-post-id="id3">
                <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="siguiendo" tab_id="tab2-posts"]'); ?>                 
            </div>

        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('inicio_pestanas', 'inicio_pestanas');


//PESTANAS DEL FRONT PARA USUARIOS NO LOGEADOS 

function inicio_pestanas_publica() {
    ob_start();
    ?>
    <div class="tabs inicio publica32">
        <ul class="tab-links" style="position: absolute;">
            <li class="active"><a href="#sample">Sample</a></li>
            <li><a href="#colabs">Colabs</a></li>
            </li>
        </ul>

        <div class="tab-content inicio" id="resultado-busqueda-1">
            <div id="sample" class="tab active" data-post-id="id1">
                <?php echo /* do_shortcode('[presentacion_inicio texto="¡Ya puedes compartir tus creaciones con el mundo! Nuestro sello discográfico  ahora está abierto para que subas tus rolas. Sube tu primera rola a todas las tiendas." imagen="https://2upra.com/wp-content/uploads/2024/04/GLSupZ2W8AAAKAg.jpeg" enlace="https://2upra.com/iniciar" clase="inicioan"]') . */ do_shortcode('[mostrar_publicaciones_sociales filtro="sample" tab_id"sample-posts"]'); ?> 
            </div>
            <div id="colabs" class="tab" data-post-id="id2">
                <?php echo /* do_shortcode('[presentacion_inicio texto="¡Desbloquea nuevas oportunidades creativas! Colabora con otros artistas y pública tu proyecto para llegar a una audiencia más amplia y recibir retroalimentación valiosa." imagen="https://2upra.com/wp-content/uploads/2024/04/GLVj7wuXkAAbkWK.jpeg" enlace="https://2upra.com/iniciar" clase="inicioan"]') . */  do_shortcode('[mostrar_publicaciones_sociales filtro="solo_colab" tab_id="colabs-posts"]'); ?> 
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('inicio_pestanas_publica', 'inicio_pestanas_publica');






function colabs_pestanas() {
    ob_start();
    $user = wp_get_current_user();
    $nombre_usuario = $user->display_name; 
    $url_imagen_perfil = obtener_url_imagen_perfil_o_defecto($user->ID); 
    ?>
    <div class="tabs inicio">
        <ul class="tab-links">
            <li class="active"><a href="#tab1">Mis Colabs</a></li>
            <li><a href="#tab2">Solicitudes</a></li>
            <li><a href="#tab3">Pausados</a></li>
            <li><a href="#tab4">Cancelados</a></li>
            <li><a href="https://2upra.com/bolsa" class="perfil-enlace">
                    Partner
                </a>
            </li>
            <li class="perfil-usuario">
                <a href="https://2upra.com/perfil" class="perfil-enlace">
                    <img src="<?php echo $url_imagen_perfil; ?>" alt="Perfil" style="width: 24px; height: 24px; border-radius: 50%;">
                    <?php echo $nombre_usuario; ?>
                    <?php echo do_shortcode('[mostrar_pinkys]'); ?>
                </a>
            </li>
        </ul>

        <div class="tab-content colabs">
            <div id="tab1" class="tab active" data-post-id="id1">
                <?php echo do_shortcode('[colabs2 filtro="en_progreso" tab_id="tab1-posts"]'); ?> 
            </div>
            <div id="tab2" class="tab" data-post-id="id2">
                <?php echo do_shortcode('[colabs2 filtro="pendiente" tab_id="tab2-posts"]'); ?>
            </div>
            <div id="tab3" class="tab" data-post-id="id3">
                <?php echo do_shortcode('[colabs2 filtro="pausado" tab_id="tab3-posts"]'); ?>
            </div>
            <div id="tab4" class="tab" data-post-id="id4">
                <?php echo do_shortcode('[colabs2 filtro="terminado" tab_id="tab4-posts"]'); ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('colabs_pestanas', 'colabs_pestanas');

//INICIO MUSIC M 

function music_m_pestanas() {
    ob_start();
    ?>
    <div class="tabs inicio" style="all: unset;">
        <ul class="tab-links" style="all: unset; display: none;">
            <li class="active" style="all: unset; display: none;"><a href="#tab1"></a></li>
            </li>
        </ul>

        <div class="tab-content" style="all: unset">
            <div id="tab1" class="tab active" data-post-id="id1">
                <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="inicio" tab_id="tab1-posts"]'); ?> 
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('music_m_pestanas', 'music_m_pestanas');

//INICIO MUSIC M LIKES

function music_m_pestanas1() {
    ob_start();
    ?>
    <div class="tabs inicio" style="all: unset;">
        <ul class="tab-links" style="all: unset; display: none;">
            <li class="active" style="all: unset; display: none;"><a href="#tab1"></a></li>
            </li>
        </ul>

        <div class="tab-content" style="all: unset">
            <div id="tab1" class="tab active" data-post-id="id1">
                <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="likes" tab_id="tab1-posts"]'); ?> 
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('music_m_pestanas1', 'music_m_pestanas1');




function registro_pestanas() {
    ob_start();
    ?>
    <div class="tabs inicio" style="all: unset;">
        <ul class="tab-links" style="all: unset; display: none;">
            <li class="active" style="all: unset; display: none;"><a href="#tab1">Mis Colabs</a></li>
            </li>
        </ul>
        <div class="tab-content" style="all: unset">
            <div id="tab1" class="tab active" data-post-id="id1">
                <?php echo do_shortcode('[registrar_usuario]'); ?> 
            </div>
            <div id="tab2" class="tab" data-post-id="id2">
                <?php echo do_shortcode('[iniciar_sesion]'); ?> 
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('registro_pestanas', 'registro_pestanas');
/*
function samples_pestanas() {
    ob_start();
    ?>
    <div class="tabs inicio" style="all: unset;">
        <ul class="tab-links" style="all: unset; display: none;">
            <li class="active" style="all: unset; display: none;"><a href="#tab1">Mis Colabs</a></li>
            </li>
        </ul>

        <div class="tab-content" style="all: unset">
            <div id="tab3" class="tab active" data-post-id="id3">
                <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="venta" tab_id="tab3-posts"]'); ?> 
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('samples_pestanas', 'samples_pestanas');
*/

