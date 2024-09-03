<?php

function versiones() {
    ob_start();
    ?>

    <div class="containerversiones">
        <div class="titulosversiones">BETA 1.0 </div> 
        <p class="parrafosversiones">
FIX : URL PERFIL: Se arreglo un problema que hacía que las url con espacios, no llevaran al perfil. <br>
FIX : MENSAJE COLAB: Se arreglo un problema que hacía que el mensaje de "Aqui no hay nada" en los colabs no apareciera. <br>
FIX : Se agregan monedas despues del registro. <br>
FIX : Boton de seguir en en las recomendaciones ya no redirije al perfil. <br>
FIX : Nombres de audios al descargar con la info del autor y contenido del post. <br>
FIX : Icono de notifaciones en movil funcional <br>
NEW : Doble click en las publicaciones agrega un like <br>
IMPROVE : MENU: Menu rehecho, transparencia, dinamico y responsive, botones funcionales y adaptable a los dispositivos moviles. <br>
NEW : Ya se puede distribuir y publicar rolas, ver el status de estas, gestionarlas, etc. <br>
NEW : Numero de seguidores y seguidos en el perfil. <br>
NEW : Agregar comentarios ya es posible. <br>
NEW : La tienda ya esta disponible, se puede publicar beats para la venta. <br>
NEW : Cache de audios por 30 días, para no tener que cargar los audios cada vez que se actualiza o cambia la pagina. <br>
         <p class="parrafosversiones">
Hay muchas cosas pendientes, mejorar la tienda, los botones de interactividad, agregar guías visuales de como usar la aplicación, lanzar la app, reforzar la seguridad y la privacidad. Han sido muchos días de trabajo sin descanso, estoy cansada pero con la actitud correcta para continuar, haré las mejoras con más calma a medida que la plataforma vaya creciendo.<br>

-1ndoryü
        </p>
        </p>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('versiones', 'versiones');
