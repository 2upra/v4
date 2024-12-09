<?

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
                <div class="BPLBDE UP">
                    <div class="DHRDTAG">
                        <? echo tagsPosts(); ?>
                    </div>
                    <div class="FDGEDF">
                        <p id="resultadosPost-sampleList"></p>
                        <? echo renderFiltro(); ?>
                    </div>
                </div>

                <div class="tab INICIO S4K7I3" id="Samples">
                    <div class="BPLBDE">
                        <div class="FOFDV5">
                            <? echo publicaciones(['filtro' => 'sampleList', 'tab_id' => 'Samples', 'posts' => 12]); ?>
                        </div>
                    </div>
                </div>
            <? endif; ?>

            <? if ($usuarioTipo === 'Fan'): // Mostrar solo si el usuario es fan 
            ?>
                <div class="tab INICIO S4K7I3" id="Feed">
                    <div class="OXMGLZ">
                        <div class="OAXRVB">
                            <div class="FEDAG5">
                                <? echo publicaciones(['filtro' => 'sample', 'tab_id' => 'Feed', 'posts' => 12]); ?>
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

function socialTabsFEED()
{

    ob_start();

    $usuarioTipo = 'Fan';

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
                <div class="BPLBDE UP">
                    <div class="DHRDTAG">
                        <? echo tagsPosts(); ?>
                    </div>
                    <div class="FDGEDF">
                        <p id="resultadosPost-sampleList"></p>
                        <? echo renderFiltro(); ?>
                    </div>
                </div>

                <div class="tab INICIO S4K7I3" id="Samples">
                    <div class="BPLBDE">
                        <div class="FOFDV5">
                            <? echo publicaciones(['filtro' => 'sampleList', 'tab_id' => 'Samples', 'posts' => 12]); ?>
                        </div>
                    </div>
                </div>
            <? endif; ?>

            <? if ($usuarioTipo === 'Fan'): // Mostrar solo si el usuario es fan 
            ?>
                <div class="tab INICIO S4K7I3" id="Feed">
                    <div class="OXMGLZ">
                        <div class="OAXRVB">
                            <div class="FEDAG5">
                                <? echo publicaciones(['filtro' => 'sample', 'tab_id' => 'Feed', 'posts' => 12]); ?>
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

function socialTabsSAMPLE()
{

    ob_start();
    $usuarioTipo = 'Artista';

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
                <div class="BPLBDE UP">
                    <div class="DHRDTAG">
                        <? echo tagsPosts(); ?>
                    </div>
                    <div class="FDGEDF">
                        <p id="resultadosPost-sampleList"></p>
                        <? echo renderFiltro(); ?>
                    </div>
                </div>

                <div class="tab INICIO S4K7I3" id="Samples">
                    <div class="BPLBDE">
                        <div class="FOFDV5">
                            <? echo publicaciones(['filtro' => 'sampleList', 'tab_id' => 'Samples', 'posts' => 12]); ?>
                        </div>
                    </div>
                </div>
            <? endif; ?>

            <? if ($usuarioTipo === 'Fan'): // Mostrar solo si el usuario es fan 
            ?>
                <div class="tab INICIO S4K7I3" id="Feed">
                    <div class="OXMGLZ">
                        <div class="OAXRVB">
                            <div class="FEDAG5">
                                <? echo publicaciones(['filtro' => 'sample', 'tab_id' => 'Feed', 'posts' => 12]); ?>
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




function momentosfijos()
{
    ob_start();

    $imagenUno = "https://images.ctfassets.net/kftzwdyauwt9/2CPrXUZS0yLGo894hU24zv/b9e1759c6f213a8888e17852266c515b/apple-art-2a-3x4.jpg?w=640&q=90&fm=webp";
    $imagenDos = "https://images.ctfassets.net/kftzwdyauwt9/1ZTOGp7opuUflFmI2CsATh/df5da4be74f62c70d35e2f5518bf2660/ChatGPT_Carousel1.png?w=640&q=90&fm=webp";
    $imagenTres = "https://images.ctfassets.net/kftzwdyauwt9/3XDJfuQZLCKWAIOleFIFZn/14b93d23652347ee7706eca921e3a716/enterprise.png?w=640&q=90&fm=webp";

?>
    <div class="ZCOPHT" style="background-image: url('<? echo esc_url($imagenUno); ?>');" onclick="window.location.href='https://2upra.com/quehacer';">
        <p>Que hacer en 2upra</p>
    </div>
    <div class="ZCOPHT" style="background-image: url('<? echo esc_url($imagenDos); ?>');" onclick="window.location.href='https://2upra.com/descubrir2upra';">
        <p>Descubre el proyecto</p>
    </div>
    <div class="ZCOPHT" style="background-image: url('<? echo esc_url($imagenTres); ?>');" onclick="window.location.href='https://2upra.com/reglas';">
        <p>Normas y Políticas</p>
    </div>
<?

    $contenido = ob_get_clean();
    return $contenido;
}
