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



function estiloAppModal()
{
    $image_url = function_exists('get_template_directory_uri') ? get_template_directory_uri() . '/assets/img/dfasdfasdfe.jpg' : '';
    $escaped_image_url = esc_url($image_url);

    ob_start();
    ?>
    <style>
        .modal.mensajeApp {
            padding: 0;
            max-width: 400px;
            width: 90%;
            margin: auto;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1005;
            background-color: var(--fondo, #fff);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .modal.mensajeApp .imagenApp {
            background-image: url("<?php echo $escaped_image_url; ?>");
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            height: 250px;
            width: 100%;
        }

        .contenidoAppModal {
            background: var(--fondo, #fff);
            width: 100%;
            height: auto;
            display: flex;
            padding: 20px;
            flex-direction: column;
            box-sizing: border-box;
            text-align: center;
        }

        .contenidoAppModal h2 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.5em;
        }

        .contenidoAppModal p {
            margin-bottom: 20px;
            font-size: 1em;
            line-height: 1.4;
        }

        .contenidoAppModal button {
            width: 100%;
            margin-top: 10px;
            padding: 12px 20px;
            font-size: 1em;
            cursor: pointer;
            border-radius: 5px;
            border: none;
            transition: background-color 0.3s ease;
        }

        .dosBotones {
            display: flex;
            gap: 10px;
            width: 100%;
            margin-top: 10px;
        }

        .botonPrincipal {
            background-color: var(--color-principal, #007bff);
            color: white;
        }

        .botonPrincipal:hover {
            background-color: var(--color-principal-hover, #0056b3);
        }

        .botonSecundario {
            background-color: var(--color-secundario, #6c757d);
            color: white;
        }

        .botonSecundario:hover {
            background-color: var(--color-secundario-hover, #5a6268);
        }

        @media (max-width: 600px) {
            .modal.mensajeApp {
                width: 95%;
            }

            .contenidoAppModal h2 {
                font-size: 1.3em;
            }

            .contenidoAppModal p {
                font-size: 0.9em;
            }
        }
    </style>

    <?php
    return ob_get_clean();
}

// Refactor(Exec): Funcion like() movida a app/View/Helpers/LikeHelper.php


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

// Funcion formRs() movida desde app/Form/View/formRS.php
function formRs()
{
    ob_start();
    $user = wp_get_current_user();
    $nombreUsuario = $user->display_name;
    $urlImagenperfil = imagenPerfil($user->ID);

?>
    <style>
        div#multiplesAudios label {
            width: 100%;
            place-content: center;
            padding: 6px;
        }

        div#multiplesAudios {
            width: 100%;
            gap: 10px;
            place-content: center;
        }
    </style>
    <div class="bloque modal" id="formRs" style="display: none;">

        <div class="W8DK25">
            <img id="perfil-imagen" src="<? echo esc_url($urlImagenperfil); ?>" alt="Perfil"
                style="max-width: 50px; max-height: 50px; border-radius: 50%;">
            <p><? echo $nombreUsuario ?></p>
        </div>

        <div>
            <div class="postTags DABVYT" id="textoRs" contenteditable="true" data-placeholder="Agrega tags usando #, puedes agregar varios audios a la vez"></div>

            <input type="hidden" id="postTagsHidden" name="post_tags">

            <textarea id="postContent" name="post_content" rows="2" required placeholder="Escribe aquí" style="display: none;"></textarea>
        </div>



        <div class="previewsForm NGEESM RS ppp3" id="ppp3" style="display: none;">
            <div class="previewAreaArchivos" id="previewImagen" style="display: none;">
                <label></label>
            </div>
            <div class="previewAreaArchivos" id="previewAudio" style="display: none;">
                <label></label>
                <div class="flew-row" id="multiplesAudios" style="display: none;">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="individualPost" name="individualPost" value="1">
                        <span class="checkmark"></span>
                        Individual post
                    </label>
                    <label class="custom-checkbox">
                        <input type="checkbox" id="multiplePost" name="multiplePost" value="1">
                        <span class="checkmark"></span>
                        Multiples post
                    </label>
                </div>
            </div>
            <div class="previewAreaArchivos" id="previewArchivo" style="display: none;">
                <label>Archivo adicional para colab (flp, zip, rar, midi, etc)</label>
            </div>
        </div>

        <div class="DRHMDE" id="fanartistchecks">
            <label class="custom-checkbox">
                <input type="checkbox" id="fancheck" name="fancheck" value="1">
                <span class="checkmark">Area de fans</span>

            </label>
            <label class="custom-checkbox">
                <input type="checkbox" id="artistacheck" name="artistacheck" value="1">
                <span class="checkmark">Area de artistas</span>

            </label>
        </div>

        <input type="text" id="nombreLanzamiento" class="nombreLanzamiento" placeholder="Titulo de lanzamiento" style="background: none;
    border: var(--borde); display: none;">

        <div class="bloque flex-row" id="opciones" style="display: none">
            <p>Opciones de post</p>
            <div class="flex flex-row gap-2">
                <label class="custom-checkbox tooltip-element" data-tooltip="Permite las descargas en la publicación">
                    <input type="checkbox" id="descargacheck" name="descargacheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['descargaicono']; ?>
                </label>
                <label class="custom-checkbox tooltip-element" data-tooltip="Exclusividad: solo los usuarios suscritos verán el contenido de la publicación">
                    <input type="checkbox" id="exclusivocheck" name="exclusivocheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['estrella']; ?>
                </label>
                <label class="custom-checkbox tooltip-element" data-tooltip="Permite recibir solicitudes de colaboración">
                    <input type="checkbox" id="colabcheck" name="colabcheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['iconocolab']; ?>
                </label>
                <label class="custom-checkbox tooltip-element" data-tooltip="Publicar en formato stream y lanzar a tiendas musicales">
                    <input type="checkbox" id="musiccheck" name="musiccheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['iconomusic']; ?>
                </label>
                <label class="custom-checkbox tooltip-element" data-tooltip="Vender el contenido, beat o sample en la tienda de 2upra">
                    <input type="checkbox" id="tiendacheck" name="tiendacheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['dolar']; ?>
                </label>
                <label class="custom-checkbox tooltip-element" data-tooltip="Publicación Efimera">
                    <input type="checkbox" id="momentocheck" name="momentocheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['momentoIcon']; ?>
                </label>
            </div>
        </div>




        <div class="botonesForm R0A915">
            <button class="botonicono borde" id="botonAudio"><? echo $GLOBALS['subiraudio']; ?></button>

            <button class="botonicono borde" id="botonImagen"><? echo $GLOBALS['subirimagen']; ?></button>

            <button class="botonicono borde" id="botonArchivo"><? echo $GLOBALS['subirarchivo']; ?></button>

            <button class="borde" id="enviarRs">Publicar</button>
        </div>
    </div>

    <?
    return ob_get_clean();
}

// Refactor(Exec): Funcion botonDescarga() movida a app/View/Helpers/DownloadHelper.php

// Refactor(Exec): Mover función botonColab() a ColabHelper.php

// Refactor(Org): Funcion botonColeccion() movida a app/View/Helpers/CollectionHelper.php

// Refactor(Org): Mueve función botonSincronizar() desde app/Functions/descargas.php
function botonSincronizar($postId)
{
    ob_start();
    $paraDescarga = get_post_meta($postId, 'paraDescarga', true);
    $userId = get_current_user_id();

    if ($paraDescarga == '1') {
        if ($userId) {
            $descargasAnteriores = get_user_meta($userId, 'descargas', true);
            $yaDescargado = isset($descargasAnteriores[$postId]);
            $claseExtra = $yaDescargado ? 'yaDescargado' : '';
            $esColeccion = get_post_type($postId) === 'colecciones' ? 'true' : 'false';


    ?>
            <div class="ZAQIBB">
                <button class="icon-arrow-down <?php echo esc_attr($claseExtra); ?>"
                    data-post-id="<?php echo esc_attr($postId); ?>"
                    aria-label="Boton Descarga"
                    id="download-button-<?php echo esc_attr($postId); ?>"
                    onclick="return procesarDescarga('<?php echo esc_js($postId); ?>', '<?php echo esc_js($userId); ?>', '<?php echo $esColeccion; ?>')">
                    <?php echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
        <?php
        } else {
        ?>
            <div class="ZAQIBB">
                <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                    <?php echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
<?php
        }
    }
    return ob_get_clean();
}

// Refactor(Exec): Funcion botonComentar() movida a app/View/Helpers/CommentHelper.php
