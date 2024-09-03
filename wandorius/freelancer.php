<?php 

function freelancer_pestanas() {
    ob_start();
    $user = wp_get_current_user();
    $nombre_usuario = $user->display_name; 
    $url_imagen_perfil = obtener_url_imagen_perfil_o_defecto($user->ID); 
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

function html1() {
    ob_start();
    ?>

    <div class="C1">
		
		<div class="X1">
			<div class="X4">
		    	<p class="XX1">Asley Crespo.</p>
		    	<p class="XX5">Javascript Lover. <br> Web Developer. <br> Graphic Design.</p>
	        </div>
	        <p class="XX2"><img src="https://2upra.com/wp-content/uploads/2024/05/401040980_2573929656119046_4305007231577328267_n.jpg" alt="1ndoryü"></p>
	    </div>
	   
	    <div class="X2" style="margin-top: -20px;">
	   		<p class="XX2">¡Hola! Soy Asley Crespo, diseñadora y programadora especializada en PHP y JavaScript. Estoy emocionada de presentarte mi portafolio, donde podrás ver algunos de mis trabajos.<br> </p>

	    </div>
    

	    <div class="C2">
	    	<p class="XX1">Proyectos</p>
	 	</div>

		<div class="X3">
	    	<p class="XX3">1. 2upra</p>
	    	<p class="XX2">Sello Discografico</p>
	        <p class="XX4"><img src="https://2upra.com/wp-content/uploads/2024/05/mockup1.jpg" alt="1ndoryü"></p>
	        <p class="XX2">Combinando la flexibilidad de WordPress con la potencia de PHP, 2upra es una plataforma innovadora para artistas musicales. Este proyecto personal explora el desarrollo web para crear un espacio digital único, equipado con herramientas que fomentan la colaboración, la exposición y el crecimiento dentro de la comunidad musical. 2upra es un testimonio de cómo la tecnología puede impulsar el arte y la conexión entre artistas.<br></p>
	        <p class="XX4"><img src="https://2upra.com/wp-content/uploads/2024/05/2@2000x-100.jpg" alt="1ndoryü"></p>
	        <p class="XX4"><img src="https://2upra.com/wp-content/uploads/2024/05/3@2000x-100.jpg" alt="1ndoryü"></p>
	        <p class="XX4"><img src="https://2upra.com/wp-content/uploads/2024/05/33.png" alt="1ndoryü"></p>
	        <p class="XX4"><img src="https://2upra.com/wp-content/uploads/2024/05/55.png" alt="1ndoryü"></p>
	        <p class="XX4"><img src="https://2upra.com/wp-content/uploads/2024/05/5@2000x-100.jpg" alt="1ndoryü"></p>
	        <p class="XX4"><img src="https://2upra.com/wp-content/uploads/2024/05/6@2000x-100.jpg" alt="1ndoryü"></p>
	        <p class="XX4"><img src="https://2upra.com/wp-content/uploads/2024/05/7@2000x-100.jpg" alt="1ndoryü"></p>
	    </div>
    </div>

 <?php
    return ob_get_clean();
}
add_shortcode('html1', 'html1');

add_action( 'wp_enqueue_scripts', 'desactivar_scripts_en_asley' );
function desactivar_scripts_en_asley() {
  if ( is_page( 'asley' ) ) {
    global $wp_scripts;
    $wp_scripts->queue = array();
  }
}