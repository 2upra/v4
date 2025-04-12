<?php

// Refactor(Org): Función papelera() movida a app/View/Components/Info/PapeleraContent.php

// Refactor(Org): Funciones y hooks para APK movidos a app/Setup/ThemeSetup.php

// Refactor(Org): Función dev() movida a app/View/Components/DevContent.php

// Refactor(Org): Función modalCarta() movida a app/View/Modals/CartaModal.php

// Refactor(Org): Función formularioProgramador() movida a app/View/Components/Forms/ProgrammerForm.php

/*

                <div class="XX1 XX2">
                    <div class="XXDD IUNRBL">
                        <h3 class="XXD1"><strong>Conviértete en patrocinador:</strong> Si te gusta el proyecto, puedes colaborar obteniendo participación creativa, acceso anticipado, contenido exclusivo, reconocimiento y acciones mensuales del proyecto.</h3>
                        
                    </div>
                    <div class="XXDD IUNRBL">
                        <h3 class="XXD1"><strong>Colabora como desarrollador:</strong> Recibirás una compensación acorde a tu participación, que puede incluir reconocimiento, acciones del proyecto o la posibilidad de formar parte del equipo principal y beneficiarte de las ganancias futuras.</h3>
                    </div>


                </div>

*/




/*
function redirect_non_admin_users()
{
    // Verifica si el usuario está logueado y no es administrador
    if (is_user_logged_in() && !current_user_can('administrator')) {
        // Obtiene la URL actual
        $current_url = $_SERVER['REQUEST_URI'];

        // Verifica si la URL actual NO es 'https://2upra.com/' y NO es 'https://2upra.com/config'
        if ($current_url !== '/' && !is_page('2upra') && !is_page('config')) {
            // Redirige a la página específica
            wp_redirect(home_url('/'));  // home_url('/') genera la URL raíz del sitio (https://2upra.com/)
            exit; // Detiene la ejecución para evitar que se cargue el resto de la página
        }
    }
}

// Hook para ejecutar la función en todas las páginas
add_action('template_redirect', 'redirect_non_admin_users');
*/
