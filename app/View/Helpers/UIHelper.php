<?php

//ESTE ARCHIVO ESTA VOLVIENDOSE MUY GRANDE POR LA REFACTORIZACIÓN POR FAVOR; ORNDENA MEJOR CADA FUNCION EN ARCHIVO MAS PEQUEÑOS 

if (!class_exists('UIHelper')) {
    class UIHelper
    {
        /**
         * Genera el HTML y CSS para la barra de carga superior.
         */
        public static function loadingBar()
        {
            echo '<style>
                #loadingBar {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 0%;
                    height: 4px;
                    background-color: white; /* Color de la barra */
                    transition: width 0.4s ease;
                    z-index: 999999999999999;
                }
            </style>';

            echo '<div id="loadingBar"></div>';
        }
    }
}

add_action('wp_head', ['UIHelper', 'loadingBar']);



function mostrarModalActualizacionApp()
{
    $version_actual = '24122024a';
    $usuario_actual = wp_get_current_user();
    $mostrar_modal = false;

    // Introducir un retraso de 5 segundos
    $version_usuario = get_user_meta($usuario_actual->ID, 'app_version_name', true);

    if ($version_usuario && $version_usuario !== $version_actual) {
        $mostrar_modal = true;
    }
    if ($mostrar_modal) :
        echo generarEstilosModalActualizacion();
    ?>
        <div class="modal modalActualizacionApp" style="display: none;">
            <div class="contenidoActualizacionAppModal">
                <h2>Actualiza la app</h2>
                <p>Tu versión de la app está desactualizada. Por favor, actualiza a la última versión para disfrutar de todas las funciones.</p>
                <div class="botonesModalActualizacion">
                    <button class="botonSecundario botonActualizacionDespues">Después</button>
                    <button class="botonPrincipal botonActualizarAhora">Actualizar</button>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Obtener el userAgent correctamente
                const userAgent = navigator.userAgent;

                if (userAgent.includes('AppAndroid')) {
                    const modal = document.querySelector('.modalActualizacionApp');
                    const botonDespues = document.querySelector('.botonActualizacionDespues');
                    const botonActualizar = document.querySelector('.botonActualizarAhora');

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
                        window.location.href = "https://2upra.com/wp-content/uploads/2024/12/2upra24122024a.apk";
                        ocultarModalActualizacion();
                    });
                }

                function crearFondoModal() {
                    let fondoOscuro = document.getElementById('fondoModalApp');
                    if (!fondoOscuro) {
                        fondoOscuro = document.createElement('div');
                        fondoOscuro.id = 'fondoModalApp';
                        fondoOscuro.style.cssText = `
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background-color: rgba(0, 0, 0, 0.5);
                            z-index: 1003;
                            display: none;
                            pointer-events: none;
                            opacity: 0;
                            transition: opacity 0.3s ease;
                        `;
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
            });
        </script>
    <?
    endif;
}

function generarEstilosModalActualizacion()
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
<?
    return ob_get_clean();
}

// Refactor(Exec): Funcion formRs() movida a app/View/Components/Forms/RsForm.php

// Refactor(Exec): Funcion botonDescarga() movida a app/View/Helpers/DownloadHelper.php

// Refactor(Exec): Mover función botonColab() a ColabHelper.php

// Refactor(Org): Funcion botonColeccion() movida a app/View/Helpers/CollectionHelper.php

// Refactor(Exec): Funcion botonSincronizar() movida a app/View/Helpers/DownloadHelper.php

// Refactor(Exec): Funcion botonComentar() movida a app/View/Helpers/CommentHelper.php

// Refactor(Exec): Funcion modalColeccion() movida a app/View/Modals/CollectionModal.php
