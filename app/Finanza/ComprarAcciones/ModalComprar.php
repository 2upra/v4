<?

function modalComprarAcciones()
{

    ob_start();
    ?>

        <div class="HMPGRM" id="modalinvertir">
            <div id="contenidocomprar">
                <p class="ETXLXB">Ingresa la cantidad a donar</p>
                <input type="text" id="cantidadCompra" placeholder="$20">
                <input type="hidden" id="cantidadReal">
                <input type="hidden" id="userID" value="<? echo get_current_user_id(); ?>">
                <p>"Al donar, parte de tu contribución se convierte en acciones de nuestra empresa a través de un fondo de inversión algorítmico que ajusta su valor automáticamente. Tu apoyo impulsa el proyecto y te hace parte de nuestro crecimiento, con la posibilidad de beneficiarte de nuestro crecimiento futuro"</p>
                <div class="DZYSQD DZYSQF">
                    <button class="DZYBQD cerrardonar">Volver</button>
                    <button class="DZYBQD botonprincipal" id="botonComprar">Donar</button>
                </div>
            </div>
        </div>
    <? return ob_get_clean();
}