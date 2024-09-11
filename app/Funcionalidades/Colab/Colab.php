<?php

function botonColab($post_id, $colab)
{
    ob_start();
?>

    <?php if ($colab): ?>
        <div class="XFFPOX">
            <button class="ZYSVVV" data-post-id="<?php echo $post_id; ?>">
                <?php echo $GLOBALS['iconocolab']; ?>
            </button>
        </div>

    <?php endif; ?>
<?php
    return ob_get_clean();
}

//En todas las publicaciones existe un boton, ese boton al dar click debe enviar una alerta al usuario preguntandole si quiere hacer un colab, entonces, esto debe crear un post 
