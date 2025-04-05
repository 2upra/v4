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

// Refactor(Exec): Functions mostrarModalActualizacionApp() and generarEstilosModalActualizacion() moved to app/View/Modals/AppUpdateModal.php

// Refactor(Exec): Funcion formRs() movida a app/View/Components/Forms/RsForm.php

// Refactor(Exec): Funcion botonDescarga() movida a app/View/Helpers/DownloadHelper.php

// Refactor(Exec): Mover función botonColab() a ColabHelper.php

// Refactor(Org): Funcion botonColeccion() movida a app/View/Helpers/CollectionHelper.php

// Refactor(Exec): Funcion botonSincronizar() movida a app/View/Helpers/DownloadHelper.php

// Refactor(Exec): Funcion botonComentar() movida a app/View/Helpers/CommentHelper.php

// Refactor(Exec): Funcion modalColeccion() movida a app/View/Modals/CollectionModal.php
