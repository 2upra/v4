<?php

function colab()
{
    ob_start();

?>

    <div class="tabs">
        <div class="tab-content">

            <div class="tab INICIO S4K7I3" id="Colab">
                <div class="GSDKRA">
                    <?php echo colab(); ?>
                </div>
            </div>

        </div>
    </div>

<?php

    return ob_get_clean();
}
