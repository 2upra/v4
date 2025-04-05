<?php

// Refactor(Org): Función modalCarta() movida desde app/Pages/Temporal.php
function modalCarta()
{
    ob_start();
    ?>
        <div class="TAZFZZ" id="modalCarta" style="display: none;" data-nosnippet>
            <div class="XCTQCK">
                <div class="WMPVLD"> <? echo $GLOBALS['iconologo']; ?></div>
                <div class="JMEPEB">
                    <p>2 de octubre 2024,</p>

                    <p>No voy a hablar del proyecto ni a decir que es el próximo gran hito en aplicaciones web. Hoy quiero contarte un poco sobre mí, quién soy y por qué esto es tan importante para mí.</p>

                    <p>Este no es mi primer proyecto. Algunas cosas me han salido bien en el pasado, y otras no tanto. Sin embargo, la experiencia me ha brindado las herramientas necesarias para construir cosas cada vez más complejas.</p>

                    <p>En el proceso, la música se ha convertido en una parte fundamental de mi vida. Cuando comienzas una carrera musical, tienes ciertas expectativas, y podría parecer que la falta de experiencia o esfuerzo serían los mayores obstáculos. ¡Ja! Si solo dependiera de eso, sería genial. Claro, el talento y hacer las cosas bien son lo más importante, pero me he dado cuenta de que hay muchos problemas en la producción musical que nadie parece dispuesto a resolver, sobre todo para las personas que no podemos costear los cientos de servicios necesarios. Las herramientas actuales están dispersas o te obligan a pagar suscripciones de $20 al mes cada una para usarlas, pffff.</p>

                    <p>Los productores necesitamos algo más accesible y práctico. Queremos poder separar pistas fácilmente, tener kits de batería frescos todos los días, un espacio donde encontrar colaboraciones, organizar nuestros samples, o tener una lista de sonidos favoritos para el próximo proyecto. Queremos subir el WAV a todas las malditas plataformas y no tener que esperar un mes para se publiquen o un año si queremos borrarla porque ya no nos gusta en nuestro catalago, y que buena idea sería un lugar donde los artistas pequeños puedan recibir apoyo directo de sus fans, y que esten todas su música junto a cualquier contenido que deseen compartir, fuah.</p>

                    <p>Lo bueno es que estoy dispuesta a construir cada una de estas soluciones, porque la independencia y resolver problemas le dan sentido a mi vida. He aprendido todo lo necesario para empezar, y estoy dispuesta a seguir aprendiendo lo que haga falta para lograrlo. También he aprendido que quizás no pueda hacerlo sola, y que no está mal aceptar que necesito un poco de ayuda, y la verdad, la necesito esta vez, cualquier pequeño apoyo hará la diferencia.</p>

                </div>
                <div class="WMPVLV">
                    <img src="<? echo img('https://2upra.com/wp-content/uploads/2024/10/afsafad.png') ?>" alt="">
                    <p>Wandorius</p>
                </div>
                <button class="borde cerrarCarta">Volver</button>
            </div>
        </div>
    <?
    return ob_get_clean();
}
