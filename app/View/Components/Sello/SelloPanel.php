<?php

// Funcion panel() movida desde app/Pages/Sello.php
function panel()
{
    ob_start();
?>

    <div class="FLXVTQ">
        <a href="https://2upra.com/">
            <p>Aquí podrás ver tus rolas enviadas a las plataformas de stream, pero aún estamos trabajando en esta funcionalidad.</p>
            <button class="borde">Volver</button>
        </a>
    </div>

<?php
    return ob_get_clean();
}
