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

                <? if (get_user_meta($user_id, 'leGustaAlMenosUnaRola', true)) : ?>
                    <div class="SAOEXP">
                        <div class="XZCZLA">
                            <p class="titulorolasenviadas">Rolas que te gustan</p>
                            <button class="TDMZDD"></button>
                        </div>
                        <? echo publicaciones(['filtro' => 'likes', 'tab_id' => 'Samples', 'posts' => 12]); ?>
                    </div>
                <? endif; ?>

                <div class="SAOEXP">
                    <div class="XZCZLA">
                        <p class="titulorolasenviadas">Últimas rolas</p>
                        <button class="TDMZDD"></button>
                    </div>
                    <? echo publicaciones(['filtro' => 'rola', 'tab_id' => 'Music', 'posts' => 12]); ?>
                </div>

                <div class="LGEMLK">
                </div>

            </div>
        </div>
    </div>

<?

    return ob_get_clean();
}
