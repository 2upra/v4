<?




function musica()
{

    $user_id = get_current_user_id();
    saberSi($user_id);
    ob_start();
?>
    <div id="menuData" style="display:none;" pestanaActual="">
        <div data-tab="Music"></div>
    </div>

    <div class="tabs">
        <div class="tab-content">
            <div class="tab active ZYBVGE" id="Music" ajax="no">

                <div class="SAOEXP"
                    <div class="XZCZLA">
                        <p class="titulorolasenviadas">Últimas rolas</p>
                        <button class="TDMZDD"></button>
                    </div>
                    <? echo publicaciones(['filtro' => 'rola', 'tab_id' => 'Music', 'posts' => 12]); ?>
                </div>

            </div>

        </div>
    </div>

<?

    return ob_get_clean();
}
