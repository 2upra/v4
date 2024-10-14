<?

function modalComprarAcciones()
{

    ob_start();
    ?>

        <div class="HMPGRM" id="modalinvertir">
            <div id="contenidocomprar">
                <input type="text" id="cantidadCompra" placeholder="$10">
                <input type="hidden" id="cantidadReal">
                <input type="hidden" id="userID" value="<? echo get_current_user_id(); ?>">
                <p>"Al donar, parte de tu contribución se convierte en acciones de nuestra empresa a través de un fondo de inversión algorítmico que ajusta su valor automáticamente. Tu apoyo impulsa el proyecto y te hace parte de nuestro crecimiento, con la posibilidad de beneficiarte de nuestro crecimiento futuro"</p>
                <div class="DZYSQD DZYSQF">
                    <button class="DZYBQD" id="botonComprar">Donar</button>
                    <button class="DZYBQD cerrardonar">Volver</button>
                </div>
            </div>
        </div>
    <? return ob_get_clean();
}