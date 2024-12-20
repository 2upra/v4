<?
if (!is_user_logged_in()) {
} else {
    $usuario = wp_get_current_user();
    $user_id = get_current_user_id();
    $nombre_usuario = $usuario->display_name;
    $url_imagen_perfil = imagenPerfil($usuario->ID);
    $usuarioTipo = get_user_meta(get_current_user_id(), 'tipoUsuario', true);
    if (function_exists('jetpack_photon_url')) {
        $url_imagen_perfil = jetpack_photon_url($url_imagen_perfil, array('quality' => 40, 'strip' => 'all'));
    }
}
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
<html <?php language_attributes(); ?>>

<body <?php body_class(); ?>>

    <header id="header">
        <?php
        if (LOCAL) {
        ?>
            <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>"> <?php
                                                                            } else {
                                                                            }
                                                                                ?>
        <style>
            <?php

            if (LOCAL) {
                $font_path_gothic = get_template_directory_uri() . '/assets/Fonts/Gothic60-Regular.otf';
                $font_path_source_sans_3_regular = get_template_directory_uri() . '/assets/Fonts/SourceSans3-Regular.woff2';
                $font_path_source_sans_3_semibold = get_template_directory_uri() . '/assets/Fonts/SourceSans3-SemiBold.woff2';
                $font_path_source_sans_3_bold = get_template_directory_uri() . '/assets/Fonts/SourceSans3-Bold.woff2';
            } else {
                $font_path_gothic = 'https://2upra.com/wp-content/themes/2upra3v/assets/Fonts/Gothic60-Regular.otf';
                $font_path_source_sans_3_regular = 'https://2upra.com/wp-content/themes/2upra3v/assets/Fonts/SourceSans3-Regular.woff2';
                $font_path_source_sans_3_semibold = 'https://2upra.com/wp-content/themes/2upra3v/assets/Fonts/SourceSans3-SemiBold.woff2';
                $font_path_source_sans_3_bold = 'https://2upra.com/wp-content/themes/2upra3v/assets/Fonts/SourceSans3-Bold.woff2';
            }
            ?>@font-face {
                font-family: 'Gothic №60';
                src: url('<?php echo $font_path_gothic; ?>') format('opentype');
                font-weight: 400;
                font-style: normal;
                font-display: swap;
            }

            @font-face {
                font-family: 'Source Sans 3';
                src: url('<?php echo $font_path_source_sans_3_regular; ?>') format('woff2');
                font-weight: 400;
                font-style: normal;
                font-display: swap;
            }

            @font-face {
                font-family: 'Source Sans 3';
                src: url('<?php echo $font_path_source_sans_3_semibold; ?>') format('woff2');
                font-weight: 500;
                font-style: normal;
                font-display: swap;
            }

            @font-face {
                font-family: 'Source Sans 3';
                src: url('<?php echo $font_path_source_sans_3_bold; ?>') format('woff2');
                font-weight: 700;
                font-style: normal;
                font-display: swap;
            }
        </style>

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
                            <a href="<?php echo home_url('/'); ?>">
                                <? echo $GLOBALS['iconoinicio'];
                                ?>
                            </a>
                        </div>
                        <!--
                        <div class="xaxa1 menu-item">
                            <a href="<?php echo home_url('/sello'); ?>">
                                <? // echo $GLOBALS['icononube']; 
                                ?>
                            </a>
                        </div>
                        -->

                        <div class="menu-item">
                            <a href="<?php echo home_url('/mu'); ?>">
                                <? echo $GLOBALS['iconomusic']; ?>
                            </a>
                        </div>

                        <div class="menu-item iconocolab" style="display: none;">
                            <a href="<?php echo home_url('/colabs'); ?>">
                                <? echo $GLOBALS['iconocolab']; ?>
                            </a>
                        </div>
                        <? if ($usuarioTipo === 'Fan'):  ?>
                            <div class="menu-item iconoFeedSample">
                                <a href="<?php echo home_url('/feedSample'); ?>">
                                    <? echo $GLOBALS['iconRandom']; ?>
                                </a>
                            </div>
                        <? endif; ?>
                        <? if ($usuarioTipo === 'Artista'):  ?>
                            <div class="menu-item iconoFeedSocial">
                                <a href="<?php echo home_url('/feedSocial'); ?>">
                                    <? echo $GLOBALS['iconSocial']; ?>
                                </a>
                            </div>
                        <? endif; ?>
                        <div class="menu-item iconoColec">
                            <a href="<?php echo home_url('/packs'); ?>">
                                <? echo $GLOBALS['iconoColec']; ?>
                            </a>
                        </div>

                        <div class="menu-item iconoInver">
                            <a href="<?php echo home_url('/inversion'); ?>">
                                <? echo $GLOBALS['iconoInver']; ?>
                            </a>
                        </div>

                        <div class="menuColabs">
                            <? // echo colabsResumen() 
                            ?>
                        </div>

                        <div class="xaxa1 menu-item iconoperfil menu-imagen-perfil mipsubmenu">
                            <a>
                                <img src="<? echo esc_url($url_imagen_perfil); ?>" alt="Perfil" style="border-radius: 50%;">
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
                                <? echo iconoNotificaciones() ?>
                            </a>
                        </div>

                    </div>

                </nav>

                <nav id="menu2" class="menu-container menu2">
                    <ul class="tab-links" id="adaptableTabs">
                    </ul>

                    <div class="endmenu MENUDGE">

                        <div class="search-container tooltip-element" id="filtros" data-tooltip="agrega terminos negativos con '-' ejemplo 'Hip hop drum -break-', y eso omitirá resultados que contengan break">
                            <input type="text" id="identifier" placeholder="Busqueda">
                            <button id="clearSearch" class="clear-search" style="display: none;">
                                <? echo $GLOBALS['flechaAtras']; ?>
                            </button>
                            <button></button>
                            <div class="resultadosBusqueda modal" id="resultadoBusqueda" style="display: none;">
                            </div>
                        </div>

                        <div class="menuArribaLogin">

                            <div class="prostatus0" id="btnpro">

                                <? echo $GLOBALS['pro']; ?>
                                <?
                                $user_id = get_current_user_id();
                                $pinkys = (int) get_user_meta($user_id, 'pinky', true);
                                echo ($pinkys > 100) ? '99+' : $pinkys;
                                ?>
                            </div>

                            <div class="iconobusqueda" id="iconobusqueda">
                                <a href="<?php echo home_url('/busqueda'); ?>">
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
                                        <? echo iconoNotificaciones() ?>
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
                                <img src="<?php echo esc_url($url_imagen_perfil); ?>" alt="Perfil" style="border-radius: 50%;">
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
                        <button><a href="<?php echo home_url('/'); ?>">Inicio</a></button>
                        <button><a>Caracteristicas</a></button>
                        <button><a>Comparativa</a></button>
                        <button><a>Precio</a></button>

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

    <main class="clearfix ">

        <? if (is_user_logged_in()) : ?>
            <div id="submenusyinfos">

                <? //echo publicaciones(['post_type' => 'colab', 'filtro' => 'colab', 'posts' => 3]); 
                ?>

                <!-- Fondo oscuro para los submenus -->
                <div id="modalBackground2" class="modal-background submenu modalBackground2" style="display: none;"></div>

                <div class="modalInicial">
                    <? echo modalTipoUsuario() ?>
                    <? echo modalGeneros() ?>
                </div>

                <? echo modalApp() ?>

                <div class="comentariosPost modal no-refresh" style="display: none" id="comentariosPost">
                    <div class="listComentarios no-refresh" id="listComentarios" style="display: none;">

                    </div>
                    <? echo comentariosForm() ?>
                </div>

                <div class="bloquesChatTest">
                    <div class="bloqueChatReiniciar">
                        <? echo conversacionesUsuario($user_id) ?>
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
                        <button><a href="<?php echo home_url('/perfil/'); ?>">Perfil</a></button>
                        <button class="reporte">Reportar un error</button>
                        <button class="no-ajax"><a href="https://2upra.com/wp-content/uploads/2024/12/2upra-gloria.apk">App Android</a></button>
                        <button class="no-ajax"><a href="https://github.com/1ndoryu/sync2upra/releases/download/v1.0.2/Sync-2upra-Setup-1.0.2.exe">Sync Windows</a></button>

                        <button class="no-ajax"><a class="no-ajax" href="<?php echo wp_logout_url(home_url()); ?>">Cerrar sesión</a></button>
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
                        <button><a href="<?php echo home_url('/perfil/'); ?>">Mi perfil</a></button>
                        <button class="botonConfig">Editar perfil</button>
                        <button class="reporte">Reportar un error</button>
                        <button><a href="<?php echo home_url('/colabs/'); ?>">Mis colabs</a></button>
                        <button class="no-ajax"><a class="no-ajax" href="<?php echo wp_logout_url(home_url()); ?>">Cerrar sesión</a></button>
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
                $current_user = wp_get_current_user();
                $is_admin = current_user_can('administrator') ? 'true' : 'false';
                $user_email = $current_user->user_email;
                $user_name = $current_user->display_name;
                $user_id = $current_user->ID;
                $descripcion = get_user_meta($user_id, 'profile_description', true);

                echo '<input type="hidden" id="user_is_admin" value="' . esc_attr($is_admin) . '">';
                echo '<input type="hidden" id="user_email" value="' . esc_attr($user_email) . '">';
                echo '<input type="hidden" id="user_name" value="' . esc_attr($user_name) . '">';
                echo '<input type="hidden" id="user_id" value="' . esc_attr($user_id) . '">';
                echo '<input type="hidden" id="descripcionUser" value="' . esc_attr($descripcion) . '">';

                ?>



            </div>
        <? else : ?>
            <? echo modalCarta() ?>
            <div class="CGUNVP" id="modalregistro" data-nosnippet>
                <? echo registrar_usuario() ?>
            </div>
            <div class="EJRINA" id="modalsesion" data-nosnippet>
                <? echo iniciar_sesion() ?>
            </div>
            <div id="fondonegro"></div data-nosnippet>

        <? endif; ?>


</body>