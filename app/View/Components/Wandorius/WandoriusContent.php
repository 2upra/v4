<?php
// Componente de vista para el contenido de la página Wandorius/Asley.

// Refactor(Org): Moved function html1() and its shortcode from app/Pages/Wandorius.php
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
?>