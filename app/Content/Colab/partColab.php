<?php

// Refactor(Exec): Función contenidoColab() movida a app/View/Renderers/ColabRenderer.php

function participantesColab($var)
{
    $post_id = $var['post_id'];
    $colabColaboradorAvatar = $var['colabColaboradorAvatar'];
    $colabAutorAvatar = $var['colabAutorAvatar'];

    ob_start();
?>
    <div class="LIFVXC">
        <img src="<? echo esc_url($colabColaboradorAvatar); ?>">
        <img src="<? echo esc_url($colabAutorAvatar); ?>">
    </div>
<?php
    return ob_get_clean();
}


// Refactor(Exec): Función opcionesColab() movida a app/View/Helpers/ColabHelper.php
// Refactor(Exec): Función opcionesColabActivo() movida a app/View/Helpers/ColabHelper.php
