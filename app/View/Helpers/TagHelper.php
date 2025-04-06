<?php

// Refactor(Org): Funcion tagsPosts() movida desde app/Utils/TagUtils.php
function tagsPosts() {
    $tagsFrecuentes = obtenerTagsFrecuentes();

    if (!empty($tagsFrecuentes)) {
        echo '<div class="tags-frecuentes">';
        foreach ($tagsFrecuentes as $tag) {
            echo '<span class="postTag">' . esc_html(ucwords($tag)) . '</span> ';
        }
        echo '</div>';
    } else {
        echo '<div class="tags-frecuentes">No tags available.</div>';
    }
}

?>
