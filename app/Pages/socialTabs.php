<?

function socialTabs()
{
    /*
    <div class="K51M22">

    <div class="PODOVV">
    <? // echo momentosfijos() 
    ?>
    </div>
    </div>


    <div class="M0883I">
        <? echo // formRs(); ?>
    </div>

    <div class="tab S4K7I3" id="Proyecto">
        <? echo devlogin(); ?>
    </div>
    <div data-tab="Feed"></div
                <div class="tab INICIO S4K7I3" id="Feed">
                <div class="OXMGLZ">
                    <div class="OAXRVB">

                        <div class="FEDAG5">
                            <? echo publicaciones(['filtro' => 'sample', 'posts' => 12]); ?>
                        </div>
                    </div>
                </div>
            </div>
    */
    ob_start();
?>

    <div id="menuData" style="display:none;" pestanaActual="">
        >
        <div data-tab="Samples"></div>
        <div data-tab="Colecciones"></div>
    </div>

    <div class="tabs">
        <div class="tab-content">


            <div class="tab INICIO S4K7I3" id="Samples">
                <div class="BPLBDE">
                    <div class="DHRDTAG">
                        <? echo tagsPosts() ?>
                    </div>
                    <div class="FDGEDF">
                        <p id="resultadosPost-sampleList">Resultados: </p>
                        <div class="OPCDGED">

                            <button class="restablecerBusqueda" style="display: none;">Restablecer filtros</button>

                            <button class="filtrosboton"><? echo $GLOBALS['iconoflechaArriAba']; ?></button>
                            <?
                            $filtroTiempo = get_user_meta(get_current_user_id(), 'filtroTiempo', true);
                            ?>
                            <?
                            $user_id = get_current_user_id();
                            $filtro_tiempo = get_user_meta($user_id, 'filtroTiempo', true);
                            $filtro_tiempo = $filtro_tiempo === '' ? 0 : intval($filtro_tiempo);

                            // Array de nombres de filtros
                            $nombres_filtros = array(
                                0 => 'Feed',
                                1 => 'Reciente',
                                2 => 'Semanal',
                                3 => 'Mensual'
                            );

                            // Obtener el nombre del filtro actual
                            $nombre_filtro = isset($nombres_filtros[$filtro_tiempo]) ? $nombres_filtros[$filtro_tiempo] : 'Feed';
                            ?>

                            <button class="filtrosboton">
                                <?php echo $GLOBALS['iconoflechaArriAba'] . ' ' . $nombre_filtro; ?>
                            </button
                                <div class="A1806242">
                            <button class="filtroFeed <? echo ($filtroTiempo == 0 || $filtroTiempo === '') ? 'filtroSelec' : ''; ?>">Para mí</button>
                            <button class="filtroReciente <? echo ($filtroTiempo == 1) ? 'filtroSelec' : ''; ?>">Recientes</button>
                            <button class="filtroSemanal <? echo ($filtroTiempo == 2) ? 'filtroSelec' : ''; ?>">Top Semanal</button>
                            <button class="filtroMensual <? echo ($filtroTiempo == 3) ? 'filtroSelec' : ''; ?>">Top Mensual</button>
                        </div>
                    </div>
                    <button class="ORDENPOSTSL" id="ORDENPOSTSL">Opciones<? echo $GLOBALS['flechaAbajo']; ?></button>
                    <div class="opcionCheckBox modal" id="filtrosPost" style="display: none;">

                        <div class="opcionCheck">
                            <div>
                                <label>Ocultar ya descargados</label>
                                <p class="description">No se mostraran los samples que ya hayas descargado</p>
                            </div>


                            <label class="switch">
                                <input type="checkbox" name="ocultarDescargados" id="ocultarDescargados">
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="opcionCheck">
                            <div>
                                <label>Ocultar guardados en coleccion</label>
                                <p class="description">No se mostraran los samples que esten guardadas en algunas de colecciones</p>
                            </div>

                            <label class="switch">
                                <input type="checkbox" name="ocultarEnColeccion" id="ocultarEnColeccion">
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="opcionCheck">
                            <div>
                                <label>Mostrar solo con likes</label>
                                <p class="description">Solo se mostraran los samples con tu like marcado</p>
                            </div>

                            <label class="switch">
                                <input type="checkbox" name="mostrarMeGustan" id="mostrarMeGustan">
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="XJAAHB">
                            <button class="botonsecundario borde">Restablecer</button>
                            <button class="botonprincipal">Guardar</button>
                        </div>
                    </div>

                </div>
            </div>
            <div class="FOFDV5">
                <? echo publicaciones(['filtro' => 'sampleList', 'tab_id' => 'Samples', 'posts' => 12]); ?>
            </div>
        </div>
    </div>



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
