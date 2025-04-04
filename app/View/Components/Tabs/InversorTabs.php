<?
// Refactor(Org): Moved function inversorTab from app/Pages/inversorTabs.php
function inversorTab()
{

    ob_start();
?>

    <div id=\"menuData\" style=\"display:none;\" pestanaActual=\"\">\n        <div data-tab=\"Proyecto\"></div>

    </div>

    <div class=\"tabs\">\n        <div class=\"tab-content\">\n
            <div class=\"tab S4K7I3\" id=\"Proyecto\">\n                <? echo inversorSector(); ?>\n            </div>

        </div>
    </div>

<?
    return ob_get_clean();
}
