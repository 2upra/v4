<?

function colabTabs()
{
    ob_start();

?>
    <div id="menuData" style="display:none;" pestanaActual="">
        <div data-tab="Colabs"></div>
    </div>
    
    <div class="tabs">
        <div class="tab-content">

            <div class="tab INICIO S4K7I3" id="Colabs">
                <div class="GSDKRA">
                    <? echo colab(); ?>
                </div>
            </div>

        </div>
    </div>

<?

    return ob_get_clean();
}
