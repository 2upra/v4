<?php
// Funcion movida desde app/Pages/busquedaTabs.php

function busquedaTabs()
{
    ob_start();

?>
    <div id=\"menuData\" style=\"display:none;\" pestanaActual=\"\">
        <div data-tab=\"Busquedatab\"></div>
    </div>
    
    <div class=\"tabs\">
        <div class=\"tab-content\">

            <div class=\"tab INICIO S4K7I3\" id=\"Busquedatab\">
                <div class=\"GSDKRA DSOE4LS\">
                    <?php echo busqueda(); ?>
                </div>
            </div>

        </div>
    </div>

<?php

    return ob_get_clean();
}
?>