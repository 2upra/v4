<?php
// Refactor(Org): Moved user data retrieval to UserHelper::obtenerDatosUsuarioCabecera()
$datosCabecera = obtenerDatosUsuarioCabecera();

if (!defined('ABSPATH')) {
    exit('Direct script access denied.');
}

/*

    <div id="preloader">
        <div class="loader-content">
            <? echo $GLOBALS['iconologo1']; ?>
        </div>
    </div>

    <style>
        #preloader {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: #000;
            z-index: 99999;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .loader-content {
            text-align: center;
        }

        body.loaded #preloader {
            display: none;
        }

        body:not(.loaded) {
            overflow: hidden;
        }
    </style>
*/
?>
<!DOCTYPE html>
<html <? language_attributes(); ?>>

<body <? body_class(); ?>>

    <header id="header">
        <?
        if (LOCAL) {
        ?>
            <link rel="stylesheet" href="<? echo get_stylesheet_uri(); ?>"> <?
                                                                            } else {
                                                                            }
                                                                                ?>
        <?php
        // Refactor(Org): Removed inline @font-face rules. Moved to assets/css/fonts.css and enqueued via ScriptSetup.php
        ?>

        <div id="overlay"></div>
        <? if (is_page('asley')) : ?>
            <style>
                #menu1 {
                    display: none;
                }
            </style>
        <? else : ?>
            <? if (is_user_logged_in()) : ?>
                <nav id="menu1" class="menu-container">
                    <div class="logomenu">
                        <? echo $GLOBALS['iconologo']; ?>
                    </div>

                    <div class="centermenu">

                        <div class="menu-item botoniniciomenu">
                            <a href="<? echo home_url('/'); ?>">
                                <? echo $GLOBALS['iconoinicio'];
                                ?>
                            </a>
                        </div>
                        <!--
                        <div class="xaxa1 menu-item">
                            <a href="<? echo home_url('/sello'); ?>">
                                <? // echo $GLOBALS['icononube']; 
                                ?>
                            </a>
                        </div>
                        -->

                        <div class="menu-item">
                            <a href="<? echo home_url('/mu'); ?>">
                                <? echo $GLOBALS['iconomusic']; ?>
                            </a>
                        </div>

                        <div class="menu-item iconocolab" style="display: none;">
                            <a href="<? echo home_url('/colabs'); ?>">
                                <? echo $GLOBALS['iconocolab']; ?>
                            </a>
                        </div>
                        <? if ($datosCabecera['usuarioTipo'] === 'Fan'):  ?>
                            <div class="menu-item iconoFeedSample">
                                <a href="<? echo home_url('/feedSample'); ?>">
                                    <? echo $GLOBALS['iconRandom']; ?>
                                </a>
                            </div>
                            <div class="menu-item iconoBiblioteca">
                                <a href="<? echo home_url('/biblioteca'); ?>">
                                    <? echo $GLOBALS['biblioteca']; ?>
                                </a>
                            </div>
                        <? endif; ?>
                        <? if ($datosCabecera['usuarioTipo'] === 'Artista'):  ?>
                            <div class="menu-item iconoFeedSocial">
                                <a href="<? echo home_url('/feedSocial'); ?>">
                                    <? echo $GLOBALS['iconSocial']; ?>
                                </a>
                            </div>
                            <div class="menu-item iconoColec">
                                <a href="<? echo home_url('/packs'); ?>">
                                    <? echo $GLOBALS['iconoColec']; ?>
                                </a>
                            </div>
                            <div class="menu-item iconoBiblioteca iconoBibliotecaArt">
                                <a href="<? echo home_url('/biblioteca'); ?>">
                                    <? echo $GLOBALS['biblioteca']; ?>
                                </a>
                            </div>
                        <? endif; ?>


                        <div class="menu-item iconoInver">
                            <a href="<? echo home_url('/inversion'); ?>">
                                <? echo $GLOBALS['iconoInver']; ?>
                                <div class="textoAyuda">
                                    2upra necesita tu ayuda
                                </div>
                            </a>
                        </div>

                        <div class="menu-item iconoTareas">
                            <a href="<? echo home_url('/tareas'); ?>">
                                <? echo $GLOBALS['objetivo']; ?>
                            </a>
                        </div>



                        <div class="menuColabs">
                            <? // echo colabsResumen() 
                            ?>
                        </div>

                        <div class="xaxa1 menu-item iconoperfil menu-imagen-perfil mipsubmenu">
                            <a>
                                <img src="<? echo esc_url($datosCabecera['url_imagen_perfil']); ?>" alt="Perfil" style="border-radius: 50%;">
                            </a>
                        </div>




                    </div>

                    <div class="endmenu endmenuflow">

                        <div class="menu-item botonConfig">
                            <a>
                                <? echo $GLOBALS['configicono']; ?>
                            </a>
                        </div>

                        <div class="xaxa1 menu-item">
                            <a>
                                <? // Refactor(Exec): Funcion iconoNotificaciones movida a app/View/Helpers/NotificationHelper.php
                                echo iconoNotificaciones() ?>
                            </a>
                        </div>

                    </div>

                </nav>

                <nav id="menu2" class="menu-container menu2">
                    <ul class="tab-links" id="adaptableTabs">
                    </ul>

                    <div class="endmenu MENUDGE">
                        <? if (! wp_is_mobile()) : ?>
                            <div class="search-container " id="filtros">
                                <input type="text" id="identifier" class="inputBusquedaRs" placeholder="Busqueda">
                                <button id="clearSearch" class="clear-search" style="display: none;">
                                    <? echo $GLOBALS['flechaAtras']; ?>
                                </button>
                                <button id="estrellitasTooltip" class="tooltip-element" data-tooltip="Para excluir palabras de tu búsqueda, usa el signo menos (-) antes del término o encierra frases con ello. Ejemplo: 'Hip hop drum -break drum-' no mostrará resultados que contengan 'break brum'.">
                                    <? echo $GLOBALS['iconoestrellitas']; ?>
                                </button>
                                <div class="resultadosBusqueda modal" id="resultadoBusqueda" style="display: none;">
                                </div>
                            </div>
                        <? endif; ?>

                        <div class="menuArribaLogin">

                            <div class="prostatus0" id="btnpro">

                                <? echo $GLOBALS['pro']; ?>
                                <?
                                $user_id_local = get_current_user_id(); // Use a local variable to avoid conflict if needed later
                                $pinkys = (int) get_user_meta($user_id_local, 'pinky', true);
                                echo ($pinkys > 100) ? '99+' : $pinkys;
                                ?>
                            </div>

                            <div class="iconobusqueda" id="iconobusqueda" style="display: none;">
                                <a href="<? echo home_url('/busqueda'); ?>">
                                    <? echo $GLOBALS['iconobusqueda'];
                                    ?>
                                </a>
                            </div>

                            <div class="subiricono" id="subiricono">
                                <? echo $GLOBALS['subiricono'];
                                ?>
                            </div>

                            <div class="chatIcono" id="chatIcono">
                                <a>
                                    <? echo $GLOBALS['chatIcono']; ?>
                                </a>
                            </div>

                            <div class="menuarribamovil">

                                <div class="xaxa1 menu-item">
                                    <a>
                                        <? // Refactor(Exec): Funcion iconoNotificaciones movida a app/View/Helpers/NotificationHelper.php
                                        echo iconoNotificaciones() ?>
                                    </a>
                                </div>

                                <div class="menu-item botonConfig" style="display: none;">
                                    <a>
                                        <? echo $GLOBALS['configicono']; ?>
                                    </a>
                                </div>

                            </div>



                        </div>

                        <div class="xaxa1 menu-item iconoperfil menu-imagen-perfil fotoperfilsub" id="fotoperfilsub">
                            <a>
                                <img src="<? echo esc_url($datosCabecera['url_imagen_perfil']); ?>" alt="Perfil" style="border-radius: 50%;">
                            </a>
                        </div>

                    </div>
                </nav>
            <? else : ?>
                <nav id="menu2" class="menu-container menu2 nologin">
                    <div class="logomenu">
                        <? echo $GLOBALS['iconologo']; ?>
                    </div>
                    <div class="nologinbotones">
                        <button><a onclick="scrollToSection('inicioDiv')">Inicio</a></button>
                        <button><a onclick="scrollToSection('caracteristicas')">Características</a></button>
                        <button><a onclick="scrollToSection('comparativa')">Comparativa</a></button>
                        <button style="display: none;"><a>Precio</a></button>

                    </div>
                    <div class="iconRedSocial">
                        <a class="no-ajax" href="https://www.threads.net/@wandorius">
                            <? echo $GLOBALS['threads']; ?>
                        </a>
                    </div>
                    <div class="nologinboton">
                        <button class="botonprincipal<? if (!is_user_logged_in()) echo ' boton-sesion'; ?>">Iniciar sesión</button>
                    </div>

                </nav>
            <? endif; ?>
        <? endif; ?>
    </header>

    <?php
    // Refactor(Org): Incluir el archivo del modal de tipo de usuario si el usuario está logueado
    if (is_user_logged_in() && function_exists('get_current_user_id')) {
        $modal_tipo_usuario_path = get_template_directory() . '/app/View/Modals/TipoUsuarioModal.php';
        if (file_exists($modal_tipo_usuario_path)) {
            require_once $modal_tipo_usuario_path;
        }
    }
    ?>

    <main class="clearfix ">

        <? if (is_user_logged_in()) : ?>
            <div id="submenusyinfos">

                <? //echo publicaciones(['post_type' => 'colab', 'filtro' => 'colab', 'posts' => 3]); 
                ?>

                <!-- Fondo oscuro para los submenus -->
                <div id="modalBackground2" class="modal-background submenu modalBackground2" style="display: none;"></div>

                <div class="modalInicial">
                    <?php
                    // Llamar a la función del modal de tipo de usuario (ahora incluida desde su propio archivo)
                    if (function_exists('modalTipoUsuario')) {
                        echo modalTipoUsuario();
                    }
                    // Llamar a la función del modal de géneros (asumimos que se carga desde functions.php o similar)
                    if (function_exists('modalGeneros')) {
                         echo modalGeneros();
                    }
                    ?>
                </div>

                <? echo modalApp() ?>

                <div class="comentariosPost modal no-refresh" style="display: none" id="comentariosPost">
                    <div class="listComentarios no-refresh" id="listComentarios" style="display: none;">

                    </div>
                    <? echo comentariosForm() ?>
                </div>

                <div class="bloquesChatTest">
                    <div class="bloqueChatReiniciar">
                        <? echo conversacionesUsuario($datosCabecera['user_id']) // Use user_id from helper data ?>
                    </div>
                    <? echo renderChat() ?>
                </div>
                <div class="notificaciones-lista modal" id="notificacionesModal" style="display: none">
                    <? echo listarNotificaciones() ?>
                </div>
                <!-- Modal para editar titulo coleccion -->
                <div id="cambiarTitulo" class="cambiarTituloModal modal" style="display: none;">
                    <textarea id="mensajeEditTitulo"></textarea>
                    <button id="enviarEditTitulo" class="borde">Editar</button>
                </div>

                <!-- Modal para editar post -->
                <div id="editarPost" class="editarPostModal modal" style="display: none;">
                    <textarea id="mensajeEdit"></textarea>
                    <button id="enviarEdit" class="borde">Editar</button>
                </div>

                <!-- Modal para editar tags -->
                <div id="corregirTags" class="editarPostModal modal" style="display: none;">
                    <textarea id="corregirEdit" placeholder="Explica a la IA concretamente que cosas debe corregir de los tags."></textarea>
                    <button id="enviarCorregir" class="borde">Corregir</button>
                </div>

                <? echo config() ?>
                <? echo formRs() ?>
                <? echo mostrarModalActualizacionApp() ?>

                <!-- Enviar mensaje de error -->
                <div id="formularioError" class="formularioError" style="display:none;">
                    <textarea id="mensajeError" placeholder="Describe el error"></textarea>
                    <button id="enviarError">Enviar</button>
                </div>

                <!-- Modal de detalles -->
                <div id="modalDetallesIA" class="DetallesIA modal" style="display: none; z-index: 1000; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                    <div class="modalContent">
                        <p id="modalDetallesContent"></p>
                    </div>
                </div>
                <div id="backgroundDetallesIA" class="modalBackground" style="display: none; z-index: 999; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);"></div>

                <div class="A1806241" id="fotoperfilsub-fotoperfilsub">
                    <div class="A1806242">
                        <button><a href="<? echo home_url('/perfil/'); ?>">Perfil</a></button>
                        <button class="reporte">Reportar un error</button>
                        <button class="no-ajax"><a href="https://2upra.com/wp-content/uploads/2024/12/2upra24122024a.apk">App Android</a></button>
                        <button class="no-ajax"><a href="https://github.com/1ndoryu/sync2upra/releases/download/v1.0.2/Sync-2upra-Setup-1.0.2.exe">Sync Windows</a></button>

                        <button class="no-ajax"><a class="no-ajax" href="<? echo wp_logout_url(home_url()); ?>">Cerrar sesión</a></button>
                    </div>
                </div>

                <!-- Modal formulario subir rola comprobación -->
                <div id="a84J76WY" class="a84J76WY" style="display:none;">
                    <div class="I41B2TM">
                        <div class="previewAreaArchivos" id="0I18J19">Aún no has subido una portada
                            <label></label>
                        </div>
                        <div id="0I18J20"></div>
                    </div>
                    <div class="zJRLSY">
                        <button id="MkzIeq">Seguir editando</button>
                        <button id="externalSubmitBtn" type="button">Enviar</button>
                    </div>
                </div>

                <!-- submenu al dar foto de perfil movil -->
                <div class="A1806241" id="submenuperfil-default">
                    <div class="A1806242">
                        <button><a href="<? echo home_url('/perfil/'); ?>">Mi perfil</a></button>
                        <button class="botonConfig">Editar perfil</button>
                        <button class="reporte">Reportar un error</button>
                        <button><a href="<? echo home_url('/colabs/'); ?>">Mis colabs</a></button>
                        <button class="no-ajax"><a class="no-ajax" href="<? echo wp_logout_url(home_url()); ?>">Cerrar sesión</a></button>
                    </div>
                </div>

                <? echo modalColeccion() ?>
                <? echo modalCreacionColeccion() ?>

                <!-- colab modal -->
                <div id="modalcolab" class="modal gap-4" style="display: none;">
                    <textarea id="textareaColab" placeholder="Escribe un mensaje para tu solicitud de colaboración. Debes esperar que la solicitud sea aceptada." rows="2"></textarea>
                    <div class="previewAreaArchivos" id="previewColab" style="display: block;">Puedes enviar un archivo audio para la colaboración
                        <label></label>
                    </div>
                    <input type="file" id="postArchivoColab" name="postArchivoColab" style="display:none;">
                    <div class="flex gap-3 justify-end">
                        <button class="botonsecundario" type="button">Cancelar</button>
                        <button id="empezarColab" class="botonprincipal">Enviar</button>
                    </div>
                </div>

                <!-- Configuración -->

                <!-- Información usuario -->
                <?
                // This block fetches data again, potentially redundant but kept as per decision scope
                $current_user = wp_get_current_user();
                $is_admin = current_user_can('administrator') ? 'true' : 'false';
                $user_email = $current_user->user_email;
                $user_name = $current_user->display_name; // This is available in $datosCabecera['nombre_usuario']
                $user_id = $current_user->ID; // This is available in $datosCabecera['user_id']
                $descripcion = get_user_meta($user_id, 'profile_description', true);

                echo '<input type="hidden" id="user_is_admin" value="' . esc_attr($is_admin) . '">';
                echo '<input type="hidden" id="user_email" value="' . esc_attr($user_email) . '">';
                echo '<input type="hidden" id="user_name" value="' . esc_attr($user_name) . '">'; // Using locally fetched name
                echo '<input type="hidden" id="user_id" value="' . esc_attr($user_id) . '">'; // Using locally fetched ID
                echo '<input type="hidden" id="descripcionUser" value="' . esc_attr($descripcion) . '">';

                ?>



            </div>
        <? else : ?>
            <?php
            // Refactor(Org): Incluir el modal de la carta desde su archivo dedicado
            $carta_modal_path = get_template_directory() . '/app/View/Modals/CartaModal.php';
            if (file_exists($carta_modal_path)) {
                require_once $carta_modal_path;
            }
            echo modalCarta();
            ?>
            <div class="CGUNVP" id="modalregistro" data-nosnippet>
                <? echo registrar_usuario() ?>
            </div>
            <div class="EJRINA" id="modalsesion" data-nosnippet>
                <? echo iniciar_sesion() ?>
            </div>
            <div id="fondonegro"></div data-nosnippet>

        <? endif; ?>


</body>
