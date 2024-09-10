<?php

function modalComprarAcciones()
{

    ob_start();
    ?>

        <div class="HMPGRM" id="modalinvertir">
            <div id="contenidocomprar">
                <input type="text" id="cantidadCompra" placeholder="$">
                <input type="hidden" id="cantidadReal">
                <input type="hidden" id="userID" value="<?php echo get_current_user_id(); ?>">
                <p>"Al donar, una parte de tu contribución se convierte en acciones de nuestra empresa a través de nuestro fondo de inversión algorítmico. Este sistema innovador ajusta automáticamente el valor de la empresa basándose en ingresos, gastos y otros factores clave. Tu apoyo no solo impulsa el proyecto, sino que te convierte en parte de nuestro crecimiento. Si en el futuro decides vender tus acciones, podrías beneficiarte económicamente del incremento de valor de la empresa."</p>
                <div class="DZYSQD DZYSQF">
                    <button class="DZYBQD" id="botonComprar">Donar</button>
                    <button class="DZYBQD cerrardonar">Volver</button>
                </div>
            </div>
        </div>
    <?php return ob_get_clean();
}