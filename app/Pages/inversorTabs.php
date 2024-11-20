<?

function inversorTab()
{

    ob_start();
?>

    <div id="menuData" style="display:none;" pestanaActual="">
        <div data-tab="Proyecto"></div>

    </div>

    <div class="tabs">
        <div class="tab-content">

            <div class="tab S4K7I3" id="Proyecto">
                <? echo devlogin(); ?>
            </div>

        </div>
    </div>

<?
    return ob_get_clean();
}
