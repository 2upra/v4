<?

function colecTabs()
{
    ob_start();
?>

    <div id="menuData" style="display:none;" pestanaActual="">
        <div data-tab="Colecciones"></div>
        <!--<div data-tab="Proyecto"></div> -->
    </div>

    <div class="tabs">
        <div class="tab-content">

            <div class="tab INICIO S4K7I3" id="Colecciones">
                <div class="OXMGLZ">

                </div>
            </div>

        </div>
    </div>

<?
    return ob_get_clean();
}

