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
                        <p id="resultadosPost">Resultados: </p>
                        <div class="OPCDGED">

                            <button class="filtrosboton">Orden<? echo $GLOBALS['tiempo']; ?></button>

                            <!--

                            hay que hacer un script y una funcion

                            Aqui el usuario tiene que poder elegir, guardar en la meta del usuario filtro tiempo

                            1 = Post Recientes, 2 = Top Semanal, 3 = TopMensual (por defecto 0 o ninguna = Feed normal (para mi))
                            al dar click al span aparece el submenu (esto ya esta programado lo de abir el submenu)

                            al dar click tiene que guardar en la meta del usuario filtroTiempo el valor correspondiente a la eleccion y al final despues de guardarse llamar window.reiniciarCargaDiferida()

                            usa enviarAjax /no puedes cambiar enviarAjax) para simplificar el script - entorno wordpress

                            async function enviarAjax(action, data = {}) {
                                try {
                                    const body = new URLSearchParams({
                                        action: action,
                                        ...data
                                    });
                                    const response = await fetch(ajaxUrl, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded'
                                        },
                                        body: body
                                    });
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
                                    }
                                    let responseData;
                                    const responseText = await response.text();
                                    try {
                                        responseData = JSON.parse(responseText);
                                    } catch (jsonError) {
                                        console.error('No se pudo interpretar la respuesta como JSON:', {
                                            error: jsonError,
                                            responseText: responseText,
                                            action: action,
                                            requestData: data
                                        });
                                        responseData = responseText;
                                    }
                                    return responseData;
                                } catch (error) {
                                    console.error('Error en la solicitud AJAX:', {
                                        error: error,
                                        action: action,
                                        requestData: data,
                                        ajaxUrl: ajaxUrl
                                    });
                                    return {success: false, message: error.message};
                                }
                            }

                            -->

                            <?
                            $filtroTiempo = get_user_meta(get_current_user_id(), 'filtroTiempo', true);
                            ?>
                            <div class="A1806241" id="filtrosMenu-default">
                                <div class="A1806242">
                                    <button class="filtroFeed <? echo ($filtroTiempo == 0 || $filtroTiempo === '') ? 'filtroSelec' : ''; ?>">Para mí</button>
                                    <button class="filtroReciente <? echo ($filtroTiempo == 1) ? 'filtroSelec' : ''; ?>">Recientes</button>
                                    <button class="filtroSemanal <? echo ($filtroTiempo == 2) ? 'filtroSelec' : ''; ?>">Top Semanal</button>
                                    <button class="filtroMensual <? echo ($filtroTiempo == 3) ? 'filtroSelec' : ''; ?>">Top Mensual</button>
                                </div>
                            </div>


                            <button class="ORDENPOSTSL" id="ORDENPOSTSL">Opciones<? echo $GLOBALS['flechaAbajo']; ?></button>

                            <!--

                            el usuario tendra una meta llamada filtroPost que sera un array que indique cuales filtros estan encendidos: 
                            [ocultarDescargados, ocultarEnColeccion, mostrarMeGustan]
                                                        
                            simplemente un array que coloque ese valor cuando es true, y cuando es false lo elimina
                            
                            falta hacer un js que envie al servidor la peticion para guardar la informacion en la meta del usuario y el codigo php que trabaja la solicitud

                            usa enviarAjax /no puedes cambiar enviarAjax) para simplificar el script - entorno wordpress

                            async function enviarAjax(action, data = {}) {
                                try {
                                    const body = new URLSearchParams({
                                        action: action,
                                        ...data
                                    });
                                    const response = await fetch(ajaxUrl, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded'
                                        },
                                        body: body
                                    });
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
                                    }
                                    let responseData;
                                    const responseText = await response.text();
                                    try {
                                        responseData = JSON.parse(responseText);
                                    } catch (jsonError) {
                                        console.error('No se pudo interpretar la respuesta como JSON:', {
                                            error: jsonError,
                                            responseText: responseText,
                                            action: action,
                                            requestData: data
                                        });
                                        responseData = responseText;
                                    }
                                    return responseData;
                                } catch (error) {
                                    console.error('Error en la solicitud AJAX:', {
                                        error: error,
                                        action: action,
                                        requestData: data,
                                        ajaxUrl: ajaxUrl
                                    });
                                    return {success: false, message: error.message};
                                }
                            }

                            para que tenga referncia asi se van a procesar los filtros para entenderlos y filtrar

                                // Obtener los filtros personalizados del usuario
                            $filtrosUsuario = get_user_meta($current_user_id, 'filtroPost', true);
                            
                            // Aplicar filtros según la configuración del usuario en 'FiltroPost'
                            if (!empty($filtrosUsuario)) {
                                // Filtrar publicaciones ya descargadas
                                if (in_array('ocultarDescargados', $filtrosUsuario)) {
                                    $descargasAnteriores = get_user_meta($current_user_id, 'descargas', true) ?: [];
                                    if (!empty($descargasAnteriores)) {
                                        $query_args['post__not_in'] = array_merge(
                                            $query_args['post__not_in'] ?? [], 
                                            array_keys($descargasAnteriores)
                                        );
                                    }
                                }

                                // Filtrar publicaciones guardadas en colección
                                if (in_array('ocultarEnColeccion', $filtrosUsuario)) {
                                y el resto lo omiti... ya lo tengo hecho
                            -->

                            <div class="opcionCheckBox modal" id="filtrosPost" style="display: none;">

                                <div class="opcionCheck">
                                    <div>
                                        <label>Ocultar ya descargadas</label>
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


                                <button class="botonsecundario borde left">Restablecer</button>
                                <button class="botonprincipal">Guardar</button>
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
