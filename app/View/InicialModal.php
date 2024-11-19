<?

function modalPreguntas()
{
    $userId = get_current_user_id();

    ob_start();
?>
    <div class="modal selectorModalUsuario">
        <h3>Eres...</h3>
        <div class="TIPEARTISTSF">
            <div class="selectorUsuario borde" id="productorDiv">
                <p>Fan</p>
            </div>
            <div class="selectorUsuario borde" id="artistaDiv">
                <p>Artista</p>
            </div>
        </div>
        <style>
            /* Fondo específico para el productor */
            #productorDiv::before {
                background-image: url('https://2upra.com/wp-content/uploads/2024/11/aUZjCl0WQ_mmLypLZNGGJA.webp');
            }

            /* Fondo específico para el artista */
            #artistaDiv::before {
                background-image: url('https://2upra.com/wp-content/uploads/2024/11/ODuY4qpIReS8uWqwSTAQDg.webp');
            }
        </style>
        <button class="botonprincipal" style="display: none;">Siguiente</button>
    </div>

    <div class="modal">
        <h3>Escoge los géneros que te gustan</h3>
        <div class="GNEROBDS">
            <div class="borde">
                <img src="" alt="">
                <p>Trap</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>R&B</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Pop</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Tech House</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>EDM</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Disco</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Soul</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Techno</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Cinematic</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Reggaeton</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Hip hop</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Drum and Bass</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Rock</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Jazz</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Classical</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Funk</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Blues</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Dubstep</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>House</p>
            </div>
            <div class="borde">
                <img src="" alt="">
                <p>Afrobeat</p>
            </div>
        </div>
        <button class="botonprincipal">Listo</button>
    </div>

<?
    return ob_get_clean();
}
