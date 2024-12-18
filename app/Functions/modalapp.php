<?php

function modalApp()
{
    ob_start();

    $current_user = wp_get_current_user();
    $show_modal = false;

    if (0 == $current_user->ID) {
        $show_modal = true;
    } else {
        $firebase_token = get_user_meta($current_user->ID, 'firebase_token', true);
        if (empty($firebase_token)) {
            $show_modal = true;
        }
    }

    //Obtenemos la información del localStorage (este valor solo se ve al inicio, si el usuario esta logueado)
    if (isset($_COOKIE['appModalStatus']) && $show_modal) {
        $modal_status = json_decode(stripslashes($_COOKIE['appModalStatus']), true);
        if (isset($modal_status['showCount']) && $modal_status['showCount'] >= 5) {
            $show_modal = false;
        }
        if (isset($modal_status['lastHiddenDate'])) {
            $hidden_date = new DateTime($modal_status['lastHiddenDate']);
            $now = new DateTime();
            $interval = $now->diff($hidden_date);
            if ($interval->days < 1) {
                $show_modal = false;
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
            if (!userAgent.includes('AppAndroid')) {
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
                        darkBackground.style.zIndex = 1003;
                        darkBackground.style.display = 'none';
                        darkBackground.style.pointerEvents = 'none';
                        darkBackground.style.opacity = '0';
                        darkBackground.style.transition = 'opacity 0.3s ease';
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
                        createAppmodalBackground(); // Show the dark background
                        modal.style.display = 'flex';
                        modalStatus.showCount++;
                        localStorage.setItem(storageKey, JSON.stringify(modalStatus));

                        //Actualiza la cookie
                        document.cookie = 'appModalStatus=' + JSON.stringify(modalStatus) + ';path=/';
                    }

                    function hideModalForDay() {
                        quitCreateAppmodalBackground(); // Hide the dark background
                        modal.style.display = 'none';
                        modalStatus.lastHiddenDate = new Date().toISOString();
                        localStorage.setItem(storageKey, JSON.stringify(modalStatus));
                        //Actualiza la cookie
                        document.cookie = 'appModalStatus=' + JSON.stringify(modalStatus) + ';path=/';
                    }

                    showModal();

                    botonDespues.addEventListener('click', hideModalForDay);
                    botonDescargar.addEventListener('click', function() {
                        window.location.href = "https://2upra.com/wp-content/uploads/2024/12/2upra0.3.apk";
                        hideModalForDay();
                    });
                });
            }
        </script>
    <?php
    endif;
    return ob_get_clean();
}

function estiloAppModal()
{

    ob_start()
    ?>
    <style>
        .modal.mensajeApp {
            padding: 0;
            height: 450px;
            z-index: 1005;
        }

        .modal.mensajeApp .imagenApp {
            background-image: url("<?php echo get_template_directory_uri(); ?>/assets/img/dfasdfasdfe.jpg");
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
<?
    return ob_get_clean();
}
