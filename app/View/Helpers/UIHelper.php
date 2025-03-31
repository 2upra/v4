<?php

namespace App\View\Helpers;

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
    if (class_exists('App\View\Helpers\UIHelper')) {
        add_action('wp_head', ['App\View\Helpers\UIHelper', 'loadingBar']);
    }
}

