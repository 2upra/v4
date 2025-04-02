<?php

if (!class_exists('UIHelper')) {
    class UIHelper {
        /**
         * Genera el HTML y CSS para la barra de carga superior.
         */
        public static function loadingBar() {
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

    // Registrar la función en el hook wp_head
    // Asegurarse de que la clase existe antes de añadir la acción
    // Comentado temporalmente ya que la clase UIHelper no está en un namespace y podría causar conflictos si se declara dos veces.
    // Revisar la lógica de carga de clases/helpers.
    // if (class_exists('UIHelper')) {
    //     add_action('wp_head', ['UIHelper', 'loadingBar']);
    // }
}

// Funciones movidas desde app/Functions/modalapp.php
function modalApp()
{
    ob_start();
    // No mostrar modal en entorno local para facilitar desarrollo
    if (defined('LOCAL') && LOCAL === true) {
         return ob_get_clean(); // Limpiar y devolver vacío si estamos en local
    }
    $current_user = wp_get_current_user();
    $show_modal = false;

    if (0 == $current_user->ID) {
        // Usuario no logueado
        $show_modal = true;
    } else {
        // Usuario logueado, verificar si tiene token firebase
        $firebase_token = get_user_meta($current_user->ID, 'firebase_token', true);
        if (empty($firebase_token)) {
            $show_modal = true;
        }
    }

    // Verificar estado del modal desde la cookie (si aplica y si se debe mostrar)
    if ($show_modal && isset($_COOKIE['appModalStatus'])) {
        $modal_status = json_decode(stripslashes($_COOKIE['appModalStatus']), true);

        // No mostrar si ya se mostró 5 veces
        if (isset($modal_status['showCount']) && $modal_status['showCount'] >= 5) {
            $show_modal = false;
        }

        // No mostrar si se ocultó hace menos de 1 día
        if ($show_modal && isset($modal_status['lastHiddenDate'])) { // Verificar $show_modal de nuevo
            try {
                $hidden_date = new DateTime($modal_status['lastHiddenDate']);
                $now = new DateTime();
                $interval = $now->diff($hidden_date);
                if ($interval->days < 1) {
                    $show_modal = false;
                }
            } catch (Exception $e) {
                // Manejar posible error al parsear la fecha
                error_log("Error parsing date from appModalStatus cookie: " . $e->getMessage());
                // Decidir si mostrar o no en caso de error, por seguridad no mostrar
                // $show_modal = false;
            }
        }
    }


    if ($show_modal) :
?>
        <?php echo estiloAppModal(); ?>
        <div class="modal mensajeApp" style="display: none;">
            <div class="imagenApp">
                <div class="contenidoAppModal ">
                    <h2>Descarga nuestra app</h2>
                    <p style="font-size: 12px;">Y obtén 50 créditos por unirte en nuestra fase beta. Actualmente solo está disponible para Android.</p>
                    <div class="dosBotones">
                        <button class="botonSecundario botonAppDespues">Después</button>
                        <button class="botonPrincipal botonDescargar">Descargar</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            // Asegurarse que userAgent está definido globalmente o pasarlo como parámetro
            // Asumiendo que userAgent está definido en algún script global
            if (typeof userAgent !== 'undefined' && !userAgent.includes('AppAndroid')) {
                window.createAppmodalBackground = function() {
                    let darkBackground = document.getElementById('backgroundModalApp');
                    if (!darkBackground) {
                        darkBackground = document.createElement('div');
                        darkBackground.id = 'backgroundModalApp';
                        darkBackground.style.position = 'fixed';
                        darkBackground.style.top = 0;
                        darkBackground.style.left = 0;
                        darkBackground.style.width = '100%';
                        darkBackground.style.height = '100%';
                        darkBackground.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
                        darkBackground.style.zIndex = 1003; // Asegurar que esté debajo del modal
                        darkBackground.style.display = 'none';
                        darkBackground.style.pointerEvents = 'none';
                        darkBackground.style.opacity = '0';
                        darkBackground.style.transition = 'opacity 0.3s ease';
                        document.body.appendChild(darkBackground);
                    }

                    darkBackground.style.display = 'block';
                    // Forzar reflow para asegurar que la transición se aplique
                    void darkBackground.offsetWidth;
                    darkBackground.style.opacity = '1';
                    darkBackground.style.pointerEvents = 'auto'; // Permitir clicks si es necesario (aunque usualmente no)
                };

                window.quitCreateAppmodalBackground = function() {
                    const darkBackground = document.getElementById('backgroundModalApp');
                    if (darkBackground) {
                        darkBackground.style.opacity = '0';
                        // Esperar que termine la transición antes de ocultar y deshabilitar eventos
                        setTimeout(() => {
                            darkBackground.style.display = 'none';
                            darkBackground.style.pointerEvents = 'none';
                        }, 300); // Coincidir con la duración de la transición
                    }
                };

                document.addEventListener('DOMContentLoaded', function() {
                    const modal = document.querySelector('.mensajeApp');
                    // Verificar si el modal existe antes de continuar
                    if (!modal) return;

                    const botonDespues = modal.querySelector('.botonAppDespues');
                    const botonDescargar = modal.querySelector('.botonDescargar');

                    // Verificar si los botones existen
                    if (!botonDespues || !botonDescargar) return;

                    const storageKey = 'appModalStatus';
                    let modalStatus = {};

                    // Usar try-catch para localStorage por si está deshabilitado o lleno
                    try {
                         modalStatus = JSON.parse(localStorage.getItem(storageKey)) || {
                            showCount: 0,
                            lastHiddenDate: null
                        };
                    } catch (e) {
                        console.error("Error reading localStorage for appModalStatus:", e);
                        modalStatus = { showCount: 0, lastHiddenDate: null }; // Estado por defecto en caso de error
                    }


                    function showModal() {
                        // Verificar de nuevo el estado desde localStorage antes de mostrar
                        let currentStatus = {};
                        try {
                            currentStatus = JSON.parse(localStorage.getItem(storageKey)) || { showCount: 0, lastHiddenDate: null };
                        } catch (e) {
                            console.error("Error reading localStorage for showModal check:", e);
                            currentStatus = { showCount: 0, lastHiddenDate: null };
                        }

                        let shouldShow = true;

                        if (currentStatus.showCount >= 5) {
                            shouldShow = false;
                        }
                        if (shouldShow && currentStatus.lastHiddenDate) {
                            try {
                                const hidden_date = new Date(currentStatus.lastHiddenDate);
                                const now = new Date();
                                const diffTime = Math.abs(now - hidden_date);
                                const diffDays = diffTime / (1000 * 60 * 60 * 24); // Diferencia en días
                                if (diffDays < 1) { // Menos de 1 día completo
                                    shouldShow = false;
                                }
                            } catch (e) {
                                console.error("Error parsing date from localStorage:", e);
                                // Decidir si mostrar o no en caso de error, por seguridad no mostrar
                                // shouldShow = false;
                            }
                        }

                        // Solo mostrar si las condiciones de localStorage se cumplen Y si el PHP decidió mostrarlo (implícito por estar aquí)
                        if (shouldShow) {
                            createAppmodalBackground(); // Mostrar fondo oscuro
                            modal.style.display = 'flex'; // Mostrar modal
                            // Incrementar contador solo si se muestra
                            modalStatus.showCount = (currentStatus.showCount || 0) + 1;
                            try {
                                localStorage.setItem(storageKey, JSON.stringify(modalStatus));
                                // Actualizar la cookie también (con expiración)
                                document.cookie = storageKey + '=' + JSON.stringify(modalStatus) + ';path=/;max-age=' + (60*60*24*365); // Cookie por 1 año
                            } catch (e) {
                                console.error("Error writing localStorage for appModalStatus:", e);
                            }
                        } else {
                           // Si no se debe mostrar según localStorage, asegurarse que esté oculto
                           modal.style.display = 'none';
                           quitCreateAppmodalBackground();
                        }
                    }

                    function hideModalForDay() {
                        quitCreateAppmodalBackground(); // Ocultar fondo oscuro
                        modal.style.display = 'none'; // Ocultar modal
                        // Leer el estado actual antes de modificarlo
                        let currentStatus = {};
                         try {
                            currentStatus = JSON.parse(localStorage.getItem(storageKey)) || { showCount: 0, lastHiddenDate: null };
                        } catch (e) {
                            console.error("Error reading localStorage before hiding:", e);
                            currentStatus = { showCount: 0, lastHiddenDate: null };
                        }
                        currentStatus.lastHiddenDate = new Date().toISOString(); // Guardar fecha actual
                         try {
                            localStorage.setItem(storageKey, JSON.stringify(currentStatus));
                             // Actualizar la cookie también
                            document.cookie = storageKey + '=' + JSON.stringify(currentStatus) + ';path=/;max-age=' + (60*60*24*365); // Cookie por 1 año
                        } catch (e) {
                            console.error("Error writing localStorage for appModalStatus:", e);
                        }
                    }

                    // Llamar a showModal para evaluar si se debe mostrar al cargar la página
                    // Se ejecuta después de que PHP haya decidido si $show_modal es true
                    showModal();

                    botonDespues.addEventListener('click', hideModalForDay);
                    botonDescargar.addEventListener('click', function() {
                        // Idealmente, la URL debería ser configurable o una constante
                        window.location.href = "https://2upra.com/wp-content/uploads/2024/12/2upra24122024a.apk";
                        hideModalForDay(); // Ocultar también después de descargar
                    });

                    // Opcional: Cerrar modal si se hace clic en el fondo oscuro
                    const darkBackground = document.getElementById('backgroundModalApp');
                    if (darkBackground) {
                        // Asegurarse de que el listener se añade solo una vez
                        darkBackground.removeEventListener('click', hideModalForDay); // Quitar listener previo si existe
                        darkBackground.addEventListener('click', hideModalForDay);
                    }
                });
            } else if (typeof userAgent === 'undefined') {
                 console.warn("Variable 'userAgent' no definida. El modal de la app no funcionará correctamente.");
            }
        </script>
    <?php
    endif;
    return ob_get_clean();
}

function estiloAppModal()
{
    // Usar get_template_directory_uri() de forma segura
    $image_url = function_exists('get_template_directory_uri') ? get_template_directory_uri() . '/assets/img/dfasdfasdfe.jpg' : '';
    // Escapar la URL para el atributo style
    $escaped_image_url = esc_url($image_url);

    ob_start();
    ?>
    <style>
        .modal.mensajeApp {
            padding: 0;
            /* Evitar altura fija si el contenido puede variar */
            /* height: 450px; */
            max-width: 400px; /* Limitar ancho en pantallas grandes */
            width: 90%; /* Hacerlo responsivo */
            margin: auto; /* Centrar horizontalmente */
            border-radius: 10px; /* Aplicar borde redondeado al contenedor principal */
            overflow: hidden; /* Asegurar que el contenido no se salga */
            display: flex; /* Usar flex para estructura interna */
            flex-direction: column; /* Apilar imagen y contenido */
            position: fixed; /* Posición fija para que flote */
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1005; /* Asegurar que esté sobre el fondo oscuro */
            background-color: var(--fondo, #fff); /* Fondo por defecto */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Sombra para destacar */
        }

        .modal.mensajeApp .imagenApp {
            background-image: url("<?php echo $escaped_image_url; ?>");
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            /* Quitar borde y border-radius si el contenedor principal ya los tiene */
            /* border: none; */
            /* border-radius: 10px; */
            /* La altura debe ser flexible o calculada, evitar fija si es posible */
            height: 250px; /* Altura ejemplo para la imagen */
            width: 100%; /* Ocupar todo el ancho del modal */
        }

        .contenidoAppModal {
            /* No necesita ser absoluto si el modal es flex column */
            /* bottom: 0; */
            /* position: absolute; */
            background: var(--fondo, #fff); /* Fondo consistente */
            width: 100%;
            height: auto;
            display: flex;
            padding: 20px; /* Más padding */
            flex-direction: column;
            box-sizing: border-box; /* Incluir padding en el ancho/alto */
            text-align: center; /* Centrar texto */
        }

         .contenidoAppModal h2 {
            margin-top: 0; /* Quitar margen superior del h2 */
            margin-bottom: 10px; /* Espacio debajo del título */
            font-size: 1.5em; /* Tamaño de fuente título */
         }

         .contenidoAppModal p {
             margin-bottom: 20px; /* Espacio debajo del párrafo */
             font-size: 1em; /* Tamaño de fuente normalizado */
             line-height: 1.4; /* Mejorar legibilidad */
         }

        .contenidoAppModal button {
            /* width: -webkit-fill-available; */ /* Evitar prefijos específicos */
            width: 100%; /* Ocupar ancho completo dentro de su contenedor flex */
            margin-top: 10px; /* Espacio sobre los botones si están uno sobre otro */
            padding: 12px 20px; /* Padding botones */
            font-size: 1em;
            cursor: pointer;
            border-radius: 5px; /* Bordes redondeados botones */
            border: none; /* Quitar borde por defecto */
            transition: background-color 0.3s ease; /* Transición suave */
        }

        .dosBotones {
            display: flex;
            gap: 10px; /* Espacio entre botones */
            width: 100%; /* Ocupar ancho del contenedor */
            margin-top: 10px; /* Espacio sobre el grupo de botones */
        }

        /* Estilos específicos para botones (asumiendo clases existentes) */
        .botonPrincipal {
             background-color: var(--color-principal, #007bff); /* Usar variables CSS si existen */
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

        /* Media query para ajustar en pantallas pequeñas si es necesario */
        @media (max-width: 600px) {
            .modal.mensajeApp {
                width: 95%;
                /* Podría necesitar ajustar altura o padding */
            }
            .contenidoAppModal h2 {
                font-size: 1.3em;
            }
             .contenidoAppModal p {
                font-size: 0.9em;
            }
            .dosBotones {
                /* Podría apilar los botones en pantallas muy pequeñas */
                /* flex-direction: column; */
            }
        }

    </style>
<?php
    return ob_get_clean();
}
