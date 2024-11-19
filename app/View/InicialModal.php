<?

function img($url, $quality = 40, $strip = 'all')
{
    if ($url === null || $url === '') {
        return '';
    }
    $parsed_url = parse_url($url);
    if (strpos($url, 'https://i0.wp.com/') === 0) {
        $cdn_url = $url;
    } else {
        $path = isset($parsed_url['host']) ? $parsed_url['host'] . $parsed_url['path'] : ltrim($parsed_url['path'], '/');
        $cdn_url = 'https://i0.wp.com/' . $path;
    }

    $query = [
        'quality' => $quality,
        'strip' => $strip,
    ];

    $final_url = add_query_arg($query, $cdn_url);
    return $final_url;
}

function modalPreguntas()
{
    $userId = get_current_user_id();
    $productorBg = img('https://2upra.com/wp-content/uploads/2024/11/aUZjCl0WQ_mmLypLZNGGJA.webp');
    $artistaBg = img('https://2upra.com/wp-content/uploads/2024/11/ODuY4qpIReS8uWqwSTAQDg.webp');
    ob_start();
?>
    <div class="modal selectorModalUsuario">
        <div class="TIPEARTISTSF">
            <div class="selectorUsuario borde" id="productorDiv">
                <p>Fan</p>
            </div>
            <div class="selectorUsuario borde" id="artistaDiv">
                <p>Artista</p>
            </div>
        </div>
        <style>
            #productorDiv::before {
                background-image: url('<? echo $productorBg; ?>');
            }

            #artistaDiv::before {
                background-image: url('<? echo $artistaBg; ?>');
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
