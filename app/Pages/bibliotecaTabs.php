<?

function bibliotecaTabs()
{
    ob_start();

?>
    <div id="menuData" style="display:none;" pestanaActual="">
        <div data-tab="Biblioteca"></div>
    </div>

    <div class="tabs">
        <div class="tab-content">

            <div class="tab INICIO S4K7I3" id="Biblioteca">
                <div class="IIDJEND">
                    <? // echo biblioteca(); 
                    ?>
                    <? echo publicaciones(['filtro' => 'rolaListLike', 'tab_id' => 'Biblioteca', 'posts' => 12, 'tipoUsuario' => 'Fan']); ?>
                </div>
            </div>

        </div>
    </div>

<?

    return ob_get_clean();
}
