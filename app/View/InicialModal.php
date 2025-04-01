<?

function modalTipoUsuario()
{
    $userId = get_current_user_id();
    $tipoUsuario = get_user_meta($userId, 'tipoUsuario', true);

    if (!empty($tipoUsuario)) {
        return '';
    }

    $fanDiv = img('https://2upra.com/wp-content/uploads/2024/11/aUZjCl0WQ_mmLypLZNGGJA.webp');
    $artistaBg = img('https://2upra.com/wp-content/uploads/2024/11/ODuY4qpIReS8uWqwSTAQDg.webp');
    ob_start();
?>
    <div class="modal selectorModalUsuario" style="display: none;">
        <h3>Elige un tipo de usuario...</h3>
        <div class="TIPEARTISTSF">
            <div class="selectorUsuario borde" id="fanDiv">
                <p>Fan</p>
            </div>
            <div class="selectorUsuario borde" id="artistaDiv">
                <p>Artista</p>
            </div>
        </div>
        <style>
            #fanDiv::before {
                background-image: url('<? echo $fanDiv; ?>');
            }

            #artistaDiv::before {
                background-image: url('<? echo $artistaBg; ?>');
            }
        </style>
        <button class="botonsecundario" style="display: none;">Siguiente</button>
    </div>
<?
    return ob_get_clean();
}

function modalGeneros()
{
    $userId = get_current_user_id();
    $usuarioPreferencias = get_user_meta($userId, 'usuarioPreferencias', true);

    // Si ya existen preferencias, no mostramos nada
    if (!empty($usuarioPreferencias)) {
        return '';
    }

    ob_start();
?>

    <div class="modal selectorGeneros" style="display: none;">
        <h3>Elige los generos que te gustan...</h3>
        <div class="GNEROBDS">
            <div class="borde">
                <p>Trap</p>
            </div>
            <div class="borde">
                <p>R&B</p>
            </div>
            <div class="borde">
                <p>Pop</p>
            </div>
            <div class="borde">
                <p>EDM</p>
            </div>
            <div class="borde">
                <p>Disco</p>
            </div>
            <div class="borde">
                <p>Soul</p>
            </div>
            <div class="borde">
                <p>Techno</p>
            </div>
            <div class="borde">
                <p>Cinematic</p>
            </div>
            <div class="borde">
                <p>Reggaeton</p>
            </div>
            <div class="borde">
                <p>Hip hop</p>
            </div>
            <div class="borde">
                <p>Drum and Bass</p>
            </div>
            <div class="borde">
                <p>Rock</p>
            </div>
            <div class="borde">
                <p>Jazz</p>
            </div>
            <div class="borde">
                <p>Classical</p>
            </div>
            <div class="borde">
                <p>Funk</p>
            </div>
            <div class="borde">
                <p>Blues</p>
            </div>
            <div class="borde">
                <p>Dubstep</p>
            </div>
            <div class="borde">
                <p>House</p>
            </div>
            <div class="borde">
                <p>Afrobeat</p>
            </div>
            <div class="borde">
                <p>Phonk</p>
            </div>
            <div class="borde">
                <p>Rap</p>
            </div>
            <div class="borde">
                <p>Lo-fi</p>
            </div>
            <div class="borde">
                <p>Chill Out</p>
            </div>
            <div class="borde">
                <p>Electronic</p>
            </div>
        </div>
        <button class="botonsecundario">Listo</button>
    </div>

<?
    return ob_get_clean();
}


function guardarGenerosUsuario()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión para realizar esta acción.');
    }
    $generos = isset($_POST['generos']) ? explode(',', $_POST['generos']) : array();

    if (empty($generos) || !is_array($generos)) {
        wp_send_json_error('No se recibieron géneros seleccionados.');
    }

    $generos_sanitizados = array_map('sanitize_text_field', $generos);
    $userId = get_current_user_id();
    update_user_meta($userId, 'usuarioPreferencias', $generos_sanitizados);
    wp_send_json_success('Los géneros han sido guardados.');
}

add_action('wp_ajax_guardarGenerosUsuario', 'guardarGenerosUsuario');
