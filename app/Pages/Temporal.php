<?php

// Refactor(Org): Función papelera() movida a app/View/Components/Info/PapeleraContent.php

// Refactor(Org): Funciones y hooks para APK movidos a app/Setup/ThemeSetup.php

// Refactor(Org): Función dev() movida a app/View/Components/DevContent.php

// Refactor(Org): Función modalCarta() movida a app/View/Modals/CartaModal.php

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




function formularioProgramador()
{
    ob_start();
    ?>

        <div class="HMPGRM" id="modalproyecto">
            <form class="PVSHOT" method="post" data-action="proyectoForm" id="proyectoUnirte">

                <!-- Cambiar nombre de usuario -->
                <p class="ONDNYU">Completa el formulario para unirte</p>

                <!-- Cambiar nombre de usuario -->
                <div class="PTORKC">
                    <label for="usernameReal">Tu nombre real</label>
                    <input type="text" id="usernameReal" name="usernameReal" placeholder="Ingresa tu nombre" required>
                </div>

                <!-- Cambiar descripción -->
                <div class="PTORKC">
                    <label for="number">Numero de telefono</label>
                    <input type="tel" id="number" name="number" placeholder="Ingresa tu número de teléfono" required>
                </div>

                <!-- Cantidad de meses programando -->
                <div class="PTORKC">
                    <label for="programmingExperience">Cantidad de meses programando:</label>
                    <select id="programmingExperience" name="programmingExperience" required>
                        <option value="">Selecciona una opción</option>
                        <option value="lessThan1Year">Menos de 1 año</option>
                        <option value="1Year">1 año</option>
                        <option value="2Years">2 años</option>
                        <option value="moreThan3Years">Más de 3 años</option>
                    </select>
                </div>

                <!-- ¿Por qué te quieres unir al proyecto? -->
                <div class="PTORKC">
                    <label for="reasonToJoin">¿Por qué te quieres unir al proyecto?</label>
                    <textarea id="reasonToJoin" name="reasonToJoin" rows="2" placeholder="Explica tus motivos" required></textarea>
                </div>

                <!-- País -->
                <div class="PTORKC">
                    <label for="country">País:</label>
                    <input type="text" id="country" name="country" placeholder="Ingresa tu país" required>
                </div>

                <!-- Actitud respecto al proyecto -->
                <div class="PTORKC">
                    <label for="projectAttitude">¿Cual es tu actitud respecto al proyecto?</label>
                    <textarea id="projectAttitude" name="projectAttitude" rows="2" placeholder="Describe tu actitud" required></textarea>
                </div>

                <!-- Actitud respecto a WordPress -->
                <div class="PTORKC">
                    <label for="wordpressAttitude">¿Cual es tu actitud respecto a WordPress?</label>
                    <textarea id="wordpressAttitude" name="wordpressAttitude" rows="3" placeholder="Describe tu actitud" required></textarea>
                </div>

                <!-- Iniciativa para un proyecto así -->
                <div class="PTORKC">
                    <label for="projectInitiative">¿Cual es tu iniciativa para un proyecto así?:</label>
                    <select id="projectInitiative" name="projectInitiative" required>
                        <option value="">Selecciona una opción</option>
                        <option value="money">Dinero</option>
                        <option value="somethingSpecial">Hacer algo especial</option>
                        <option value="bePartOfSomething">Formar parte de algo que puede salir bien</option>
                        <option value="recognition">Reconocimiento</option>
                        <option value="jobSecurity">Un puesto de trabajo asegurado</option>
                        <option value="learn">Aprender</option>
                        <option value="portafolio">Para mi portafolio</option>
                        <option value="meGusta">Me gusta el proyecto simplemente</option>
                        <option value="meEsUtil">Me será util, me gusta la música</option>
                        <option value="other">Otra cosa</option>
                    </select>
                    <textarea id="projectInitiativeOther" name="projectInitiativeOther" rows="3" placeholder="Si seleccionaste 'Otra cosa', especifica aquí"></textarea>
                </div>

                <div class="DZYSQD">
                    <button class="DZYBQD DGFDRD" type="submit">Enviar</button>
                    <button type="button" class="DZYBQD DGFDRDC">Cerrar</button>
                </div>

            </form>
        </div>
    <?php return ob_get_clean();
}

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
