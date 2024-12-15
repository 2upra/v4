<?

function busquedaTabs()
{
    ob_start();

?>
    <div id="menuData" style="display:none;" pestanaActual="">
        <div data-tab="Busqueda"></div>
    </div>
    
    <div class="tabs">
        <div class="tab-content">

            <div class="tab INICIO S4K7I3" id="Busqueda">
                <div class="GSDKRA DSOE4LS">
                    <? echo busqueda(); ?>
                </div>
            </div>

        </div>
    </div>

<?

    return ob_get_clean();
}
