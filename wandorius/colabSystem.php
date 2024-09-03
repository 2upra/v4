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
