<?php
// Refactor(Org): Moved function socialTabs from app/Pages/socialTabs.php

function socialTabs()
{

    ob_start();

    // Obtener el tipo de usuario actual (por ejemplo, desde una función o meta del usuario).
    $usuarioTipo = get_user_meta(get_current_user_id(), 'tipoUsuario', true);

?>

    <div id="menuData" style="display:none;" pestanaActual="">
        <? if ($usuarioTipo === 'Artista'):
        ?>
            <div data-tab="Samples"></div>
        <? endif; ?>

        <? if ($usuarioTipo === 'Fan'):
        ?>
            <div data-tab="Feed"></div>
        <? endif; ?>
    </div>
    <div class="tabs">
        <div class="tab-content">
            <? if ($usuarioTipo === 'Artista'):
            ?>
                <div class="divmomento artista">
                    <? echo momentos(); ?>
                </div>
                <div class="BPLBDE UP">
                    <div class="DHRDTAG">
                        <? echo tagsPosts(); ?>
                    </div>
                    <div class="FDGEDF">
                        <p id="resultadosPost-sampleList"></p>
                        <? echo renderFiltroSampleList(); ?>
                    </div>
                    <? if (wp_is_mobile()) : ?>
                        <div class="search-container SSmovil" id="filtros" style="display: flex;">
                            <input type="text" id="identifier" class="inputBusquedaRs" placeholder="Busqueda" style="display: flex;">
                            <button id="clearSearch" class="clear-search" style="display: none;">
                                <? echo $GLOBALS['flechaAtras']; ?>
                            </button>
                            <button id="estrellitasTooltip" class="tooltip-element" data-tooltip="Para excluir palabras de tu búsqueda, usa el signo menos (-) antes del término o encierra frases con ello. Ejemplo: 'Hip hop drum -break drum-' no mostrará resultados que contengan 'break brum'.">
                                <? echo $GLOBALS['iconoestrellitas']; ?>
                            </button>
                            <div class="resultadosBusqueda modal" id="resultadoBusqueda" style="display: none;">
                            </div>
                        </div>
                    <? else : ?>
                    <? endif; ?>
                </div>

                <div class="tab INICIO S4K7I3" id="Samples">
                    <div class="BPLBDE">
                        <div class="FOFDV5">
                            <? echo publicaciones(['filtro' => 'sampleList', 'tab_id' => 'Samples', 'posts' => 12, 'tipoUsuario' => 'Artista']); ?>
                        </div>
                    </div>
                </div>
            <? endif; ?>

            <? if ($usuarioTipo === 'Fan'): // Mostrar solo si el usuario es fan 
            ?>
                <div class="divmomento fan">
                    <? echo momentos(); ?>
                </div>
                <div class="tab INICIO S4K7I3" id="Feed">
                    <div class="OXMGLZ">
                        <div class="OAXRVB">
                            <div class="FEDAG5">
                                <? echo publicaciones(['filtro' => 'sample', 'tab_id' => 'Feed', 'posts' => 12, 'tipoUsuario' => 'Fan']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <? endif; ?>
        </div>
    </div>

<?
    return ob_get_clean();
}
?>