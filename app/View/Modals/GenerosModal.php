<?php
// Refactor(Org): Funcion modalGeneros() movida desde app/View/InicialModal.php

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

<?php
    return ob_get_clean();
}
