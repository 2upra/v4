<?php
if (!is_user_logged_in()) {
} else {
    $usuario = wp_get_current_user();
    $user_id = get_current_user_id();
    $nombre_usuario = $usuario->display_name;
    $url_imagen_perfil = obtener_url_imagen_perfil_o_defecto($usuario->ID);
    if (function_exists('jetpack_photon_url')) {
        $url_imagen_perfil = jetpack_photon_url($url_imagen_perfil, array('quality' => 40, 'strip' => 'all'));
    }
}

if (!defined('ABSPATH')) {
    exit('Direct script access denied.');
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        #preloader {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: #000;
            /* Fondo negro */
            z-index: 99999;
            /* Asegúrate de que esté por encima de todo el contenido */
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .loader-content {
            text-align: center;
        }

        /* Ocultar el preloader cuando la página esté cargada */
        body.loaded #preloader {
            display: none;
        }

        /* Ocultar el overflow del body mientras carga */
        body:not(.loaded) {
            overflow: hidden;
        }
    </style>
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <div id="preloader">
        <div class="loader-content">
            <?php echo $GLOBALS['iconologo1']; ?>
        </div>
    </div>
    <header>

        <?php if (is_page('asley')) : ?>
            <style>
                #menu1 {
                    display: none;
                }
            </style>
        <?php else : ?>

            <?php if (is_user_logged_in()) : ?>

                <nav id="menu1" class="menu-container">
                    <div class="logomenu">
                        <?php echo $GLOBALS['iconologo']; ?>
                    </div>

                    <div class="centermenu">

                        <div class="menu-item">
                            <a href="https://2upra.com/">
                                <?php echo $GLOBALS['iconoinicio']; ?>
                            </a>
                        </div>

                        <div class="xaxa1 menu-item">
                            <a href="https://2upra.com/sello">
                                <?php echo $GLOBALS['icononube']; ?>
                            </a>
                        </div>

                        <div class="subiricono menu-item" id="subiricono">
                            <a>
                                <?php echo $GLOBALS['subiricono']; ?>
                            </a>
                        </div>

                        <div class="menu-item">
                            <a href="https://2upra.com/mu">
                                <?php echo $GLOBALS['iconomusic']; ?>
                            </a>
                        </div>

                        <div class="menu-item iconocolab">
                            <a href="https://2upra.com/colabs">
                                <?php echo $GLOBALS['iconocolab']; ?>
                            </a>
                        </div>

                        <div class="xaxa1 menu-item iconoperfil menu-imagen-perfil mipsubmenu">
                            <a>
                                <img src="<?php echo esc_url($url_imagen_perfil); ?>" alt="Perfil" style="border-radius: 50%;">
                            </a>
                        </div>


                    </div>

                    <div class="endmenu">

                        <div class="menu-item iconoconfig">
                            <a href="https://2upra.com/config">
                                <?php echo $GLOBALS['configicono']; ?>
                            </a>
                        </div>

                        <div class="xaxa1 menu-item">
                            <a>
                                <?php echo do_shortcode('[mostrar_notificaciones]'); ?>
                            </a>
                        </div>

                    </div>

                </nav>

                <nav id="menu2" class="menu-container menu2">
                    <ul class="tab-links" id="adaptableTabs">
                    </ul>

                    <div class="endmenu">


                        <div id="filtros">
                            <input type="text" id="identifier" placeholder="Busqueda">
                        </div>

                        <div class="xaxa1 menu-item iconoperfil prostatus0" id="btnpro">
                            <a>
                                <?php echo $GLOBALS['pro']; ?>
                            </a>
                        </div>

                        <div class="xaxa1 menu-item iconoperfil menu-imagen-perfil">
                            <a href="https://2upra.com/perfil">
                                <img src="<?php echo esc_url($url_imagen_perfil); ?>" alt="Perfil" style="border-radius: 50%;">
                            </a>
                        </div>

                    </div>
                </nav>
            <?php else : ?>
            <?php endif; ?>
        <?php endif; ?>


    </header>
    <main class="clearfix ">


        <div id="submenusyinfos">


            <!-- Fondo oscuro para los submenus -->
            <div id="modalBackground2" class="modal-background submenu modalBackground2" style="display: none;"></div>

            <!-- submenu de subir rola o sample -->
            <div class="A1806241" id="submenusubir-subiricono">
                <div class="A1806242">
                    <button id="subirrola"><a href="https://2upra.com/rola/">Subir rola</a></button>
                    <button id="subirsample"><a href="https://2upra.com/subirsample/">Subir Sample</a></button>
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
                    <button><a href="https://2upra.com/perfil/">Mi perfil</a></button>
                    <button><a href="https://2upra.com/colabs/">Mis colabs</a></button>
                </div>
            </div>

            <!-- Enviar mensaje de error -->
            <div id="formularioError" class="formularioError" style="display:none;">
                <textarea id="mensajeError" placeholder="Describe el error"></textarea>
                <button id="enviarError">Enviar</button>
            </div>

            <!-- Configuración -->


            <!-- Información usuario -->
            <?php
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


</body>