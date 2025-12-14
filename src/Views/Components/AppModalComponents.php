<?php

/**
 * Componentes de modales de la aplicación
 * 
 * Modales para descarga y actualización de la app
 *
 * @package Kamples\Views\Components
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

class AppModalComponents
{
    private const APP_VERSION = '24122024a';

    /**
     * Renderiza el modal de descarga de la app
     * 
     * @return string HTML del modal (vacío si no debe mostrarse)
     */
    public static function renderModalDescargaApp(): string
    {
        if (!self::debeMostrarModalDescarga()) {
            return '';
        }

        ob_start();
        echo self::getEstilosModalDescarga();
?>
        <div class="modal mensajeApp" id="modal-descarga-app" style="display: none;">
            <div class="imagenApp">
                <div class="contenidoAppModal">
                    <h2>Descarga nuestra app</h2>
                    <p style="font-size: 12px;">Y obtén 50 créditos por unirte en nuestra fase beta. Actualmente solo está disponible para Android.</p>
                    <div class="dosBotones">
                        <button class="botonSecundario botonAppDespues">Después</button>
                        <button class="botonPrincipal botonDescargar">Descargar</button>
                    </div>
                </div>
            </div>
        </div>
        <?= self::getScriptModalDescarga(); ?>
    <?php
        return ob_get_clean();
    }

    /**
     * Verifica si debe mostrarse el modal de descarga
     */
    private static function debeMostrarModalDescarga(): bool
    {
        /* No mostrar en entorno local */
        if (!defined('LOCAL') || (defined('LOCAL') && LOCAL === true)) {
            return false;
        }

        $currentUser = wp_get_current_user();
        $showModal = false;

        if (0 == $currentUser->ID) {
            $showModal = true;
        } else {
            $firebaseToken = get_user_meta($currentUser->ID, 'firebase_token', true);
            if (empty($firebaseToken)) {
                $showModal = true;
            }
        }

        /* Verificar cookies de estado del modal */
        if (isset($_COOKIE['appModalStatus']) && $showModal) {
            $modalStatus = json_decode(stripslashes($_COOKIE['appModalStatus']), true);

            if (isset($modalStatus['showCount']) && $modalStatus['showCount'] >= 5) {
                $showModal = false;
            }

            if (isset($modalStatus['lastHiddenDate'])) {
                $hiddenDate = new \DateTime($modalStatus['lastHiddenDate']);
                $now = new \DateTime();
                $interval = $now->diff($hiddenDate);

                if ($interval->days < 1) {
                    $showModal = false;
                }
            }
        }

        return $showModal;
    }

    /**
     * Obtiene los estilos CSS del modal de descarga
     */
    private static function getEstilosModalDescarga(): string
    {
        $templateUri = get_template_directory_uri();
        ob_start();
    ?>
        <style>
            .modal.mensajeApp {
                padding: 0;
                height: 450px;
                z-index: 1005;
            }

            .modal.mensajeApp .imagenApp {
                background-image: url("<?= esc_url($templateUri); ?>/assets/img/dfasdfasdfe.jpg");
                background-size: cover;
                background-repeat: no-repeat;
                background-position: center;
                border: none;
                border-radius: 10px;
                height: 450px;
            }

            .contenidoAppModal {
                bottom: 0;
                position: absolute;
                background: var(--fondo);
                width: 100%;
                height: auto;
                display: flex;
                padding: 15px;
                flex-direction: column;
            }

            .contenidoAppModal button {
                width: -webkit-fill-available;
                margin-top: 10px;
            }

            .dosBotones button {
                justify-content: center;
            }

            .dosBotones {
                display: flex;
                gap: 10px;
            }
        </style>
    <?php
        return ob_get_clean();
    }

    /**
     * Obtiene el script JavaScript del modal de descarga
     */
    private static function getScriptModalDescarga(): string
    {
        ob_start();
    ?>
        <script>
            if (!navigator.userAgent.includes('AppAndroid')) {
                window.createAppmodalBackground = function() {
                    let darkBackground = document.getElementById('backgroundModalApp');
                    if (!darkBackground) {
                        darkBackground = document.createElement('div');
                        darkBackground.id = 'backgroundModalApp';
                        darkBackground.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5);z-index:1003;display:none;pointer-events:none;opacity:0;transition:opacity 0.3s ease;';
                        document.body.appendChild(darkBackground);
                    }
                    darkBackground.style.display = 'block';
                    setTimeout(() => {
                        darkBackground.style.opacity = '1';
                    }, 10);
                    darkBackground.style.pointerEvents = 'auto';
                };

                window.quitCreateAppmodalBackground = function() {
                    const darkBackground = document.getElementById('backgroundModalApp');
                    if (darkBackground) {
                        darkBackground.style.opacity = '0';
                        setTimeout(() => {
                            darkBackground.style.display = 'none';
                            darkBackground.style.pointerEvents = 'none';
                        }, 300);
                    }
                };

                document.addEventListener('DOMContentLoaded', function() {
                    const modal = document.querySelector('.mensajeApp');
                    const botonDespues = document.querySelector('.botonAppDespues');
                    const botonDescargar = document.querySelector('.botonDescargar');
                    const storageKey = 'appModalStatus';

                    const modalStatus = JSON.parse(localStorage.getItem(storageKey)) || {
                        showCount: 0,
                        lastHiddenDate: null
                    };

                    function showModal() {
                        createAppmodalBackground();
                        modal.style.display = 'flex';
                        modalStatus.showCount++;
                        localStorage.setItem(storageKey, JSON.stringify(modalStatus));
                        document.cookie = 'appModalStatus=' + JSON.stringify(modalStatus) + ';path=/';
                    }

                    function hideModalForDay() {
                        quitCreateAppmodalBackground();
                        modal.style.display = 'none';
                        modalStatus.lastHiddenDate = new Date().toISOString();
                        localStorage.setItem(storageKey, JSON.stringify(modalStatus));
                        document.cookie = 'appModalStatus=' + JSON.stringify(modalStatus) + ';path=/';
                    }

                    showModal();

                    botonDespues.addEventListener('click', hideModalForDay);
                    botonDescargar.addEventListener('click', function() {
                        window.location.href = siteConfig.siteUrl + "/wp-content/uploads/2024/12/2upra24122024a.apk";
                        hideModalForDay();
                    });
                });
            }
        </script>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el modal de actualización de la app
     * 
     * @return string HTML del modal (vacío si no debe mostrarse)
     */
    public static function renderModalActualizacionApp(): string
    {
        $usuarioActual = wp_get_current_user();

        if (!$usuarioActual->ID) {
            return '';
        }

        $versionUsuario = get_user_meta($usuarioActual->ID, 'app_version_name', true);

        if (!$versionUsuario || $versionUsuario === self::APP_VERSION) {
            return '';
        }

        ob_start();
        echo self::getEstilosModalActualizacion();
    ?>
        <div class="modal modalActualizacionApp" id="modal-actualizacion-app" style="display: none;">
            <div class="contenidoActualizacionAppModal">
                <h2>Actualiza la app</h2>
                <p>Tu versión de la app está desactualizada. Por favor, actualiza a la última versión para disfrutar de todas las funciones.</p>
                <div class="botonesModalActualizacion">
                    <button class="botonSecundario botonActualizacionDespues">Después</button>
                    <button class="botonPrincipal botonActualizarAhora">Actualizar</button>
                </div>
            </div>
        </div>
        <?= self::getScriptModalActualizacion(); ?>
    <?php
        return ob_get_clean();
    }

    /**
     * Obtiene los estilos CSS del modal de actualización
     */
    private static function getEstilosModalActualizacion(): string
    {
        ob_start();
    ?>
        <style>
            .modal.modalActualizacionApp {
                padding: 0;
                height: auto;
                z-index: 1005;
                width: auto;
                max-width: 450px;
            }

            .contenidoActualizacionAppModal {
                background: var(--fondo);
                width: auto;
                display: flex;
                padding: 15px;
                flex-direction: column;
                border-radius: 10px;
            }

            .contenidoActualizacionAppModal button {
                width: -webkit-fill-available;
                margin-top: 10px;
            }

            .botonesModalActualizacion {
                display: flex;
                gap: 10px;
            }

            .botonesModalActualizacion button {
                justify-content: center;
            }
        </style>
    <?php
        return ob_get_clean();
    }

    /**
     * Obtiene el script JavaScript del modal de actualización
     */
    private static function getScriptModalActualizacion(): string
    {
        ob_start();
    ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const userAgent = navigator.userAgent;

                if (userAgent.includes('AppAndroid')) {
                    const modal = document.querySelector('.modalActualizacionApp');
                    const botonDespues = document.querySelector('.botonActualizacionDespues');
                    const botonActualizar = document.querySelector('.botonActualizarAhora');

                    function crearFondoModal() {
                        let fondoOscuro = document.getElementById('fondoModalApp');
                        if (!fondoOscuro) {
                            fondoOscuro = document.createElement('div');
                            fondoOscuro.id = 'fondoModalApp';
                            fondoOscuro.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5);z-index:1003;display:none;pointer-events:none;opacity:0;transition:opacity 0.3s ease;';
                            document.body.appendChild(fondoOscuro);
                        }
                        fondoOscuro.style.display = 'block';
                        setTimeout(() => {
                            fondoOscuro.style.opacity = '1';
                        }, 10);
                        fondoOscuro.style.pointerEvents = 'auto';
                    }

                    function quitarFondoModal() {
                        const fondoOscuro = document.getElementById('fondoModalApp');
                        if (fondoOscuro) {
                            fondoOscuro.style.opacity = '0';
                            setTimeout(() => {
                                fondoOscuro.style.display = 'none';
                                fondoOscuro.style.pointerEvents = 'none';
                            }, 300);
                        }
                    }

                    function mostrarModalActualizacion() {
                        crearFondoModal();
                        modal.style.display = 'flex';
                    }

                    function ocultarModalActualizacion() {
                        quitarFondoModal();
                        modal.style.display = 'none';
                    }

                    mostrarModalActualizacion();

                    botonDespues.addEventListener('click', ocultarModalActualizacion);
                    botonActualizar.addEventListener('click', function() {
                        window.location.href = siteConfig.siteUrl + "/wp-content/uploads/2024/12/2upra24122024a.apk";
                        ocultarModalActualizacion();
                    });
                }
            });
        </script>
<?php
        return ob_get_clean();
    }
}
