<?php
// Componente para renderizar las pestañas de la sección Freelancer.

// Refactor(Org): Moved function freelancer_pestanas() and its shortcode from app/Pages/Wandorius.php
function freelancer_pestanas() {
    ob_start();
    // Assuming UserHelper.php (containing imagenPerfil) is included globally or autoloaded.
    $user = wp_get_current_user();
    $nombre_usuario = $user->display_name;
    $url_imagen_perfil = imagenPerfil($user->ID); // Dependency on imagenPerfil()
    ?>
    <div class="tabs inicio">
        <ul class="tab-links freelancer">
            <li class="active"><a href="#sobremi">Sobre Mi</a></li>
            <li><a href="#proyectos">Proyecto</a></li>
            <li><a href="#servicios">Servicios</a></li>
        </ul>

        <div class="tab-content inicio freelancer" id="full">
            <div id="tab1" class="tab active" data-post-id="id1">
                <?php echo do_shortcode('[html1]')?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('freelancer_pestanas', 'freelancer_pestanas');
?>