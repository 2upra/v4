<?php

function dev()
{
    ob_start();

?>

    <div class="tabs">
        <div class="tab-content">
            <div class="tab active GMXSUJ" id="inicio">
                <div class="UIKMYM">

                    <div class="WZEFLA">
                        <p>Proyecto 2upra</p>
                    </div>

                    <div class="OIEODG">
                        <p>2upra es un proyecto desarrollado en PHP y JavaScript. Consiste en una plataforma social que solucione problemas comunes en la producción musical y ofrezca herramientas a los artistas musicales.</p>
                    </div>

                    <div class="JUJRQG">
                        <a href="https://github.com/1ndoryu" class="no-ajax">
                            <button class="DZYBQD" id="github-button">
                                <?php echo $GLOBALS['Github']; ?> GitHub
                            </button>
                        </a>

                        <a href="https://chat.whatsapp.com/IGHrIfvifHS9Fwz4ha6Uis" class="no-ajax">
                            <button class="DZYBQD" id="whatsapp-button">
                                <?php echo $GLOBALS['Whatsapp']; ?> WhatsApp
                            </button>
                        </a>

                    </div>

                    <div class="CGUNVP" id="modalregistro">
                        <?php echo registrar_usuario() ?>
                    </div>
                    <div class="EJRINA" id="modalsesion">
                        <?php echo iniciar_sesion() ?>
                    </div>

                </div>

                <div class="QYGNPB YDFVMQ">
                    <div class="XXDD EZDNZE THFJWV">
                        <p class="MLZKPD">¿Que ofreceremos a los artistas?</p>
                        <p class="XXD2"></p>
                    </div>
                </div>


                <div class="XX1 XX2">
                    <?php
                    $images = [
                        [
                            'title' => '<strong>Herramientas gratuitas</strong> para artistas: samples, drumkits, VST y más',
                        ],
                        [
                            'title' => '<strong>Encontrar colaboraciones</strong> para impulsar la carrera musical de los artistas',
                        ],
                        [
                            'url' => 'https://2upra.com/wp-content/uploads/2024/05/asfsdf4.png',
                            'title' => '<strong>Monetización diversificada</strong> y apoyo integral para artistas a través de sus fans o contenidos propios',
                        ],
                        [
                            'title' => '<strong>Producción musical simplificada:</strong> acceso a playlists, distribución, alcance.',
                        ]
                    ];

                    foreach ($images as $index => $image):
                        $optimized_url = img($image['url'], 'medium', 50, 'all');
                    ?>
                        <div class="XXDD">
                            <div class="spaceimagen index-<?php echo $index; ?>">
                                <?php if ($index === 0): ?>
                                    <div class="KTEPUZ">
                                        <div class="WELODV">
                                            <img src="<?php echo img('https://2upra.com/wp-content/uploads/1107885577068943408_и.jpg', 40, 'all'); ?>">
                                            <p>Sample_pack_vol_1.winrar</p>
                                            <?php echo botonDescargaPrueba(); ?>
                                        </div>
                                        <div class="WELODV KESAYW">
                                            <img src="<?php echo img('https://2upra.com/wp-content/uploads/1107885577066304428_Magnetic-aura-subliminal.jpg', 40, 'all'); ?>">
                                            <p>ambient sound.wav</p>
                                            <?php echo botonDescargaPrueba(); ?>
                                        </div>
                                    </div>
                                <?php elseif ($index === 1): ?>
                                    <div class="KTEPUZ JOJLEZ">
                                        <div class="WELODV OQDGCR">
                                            <img src="<?php echo img('https://2upra.com/wp-content/uploads/2024/05/2.webp', 40, 'all'); ?>">
                                            <p>Wandorius</p>
                                        </div>
                                        <div class="HPDTIR">
                                            <?php echo $GLOBALS['present1']; ?>
                                        </div>
                                        <div class="WELODV KESAYW OQDGCR">
                                            <img src="<?php echo img('https://2upra.com/wp-content/uploads/2024/05/1.webp', 40, 'all'); ?>">
                                            <p>Billie Eilish</p>
                                        </div>
                                    </div>
                                <?php elseif ($index === 3): ?>
                                    <div class="KTEPUZ UEMOGY">
                                        <div class="WELODV HYEXIH">
                                            <img src="<?php echo img('https://2upra.com/wp-content/uploads/2024/05/3.jpg', 40, 'all'); ?>">
                                            <div class="UPYTYH">
                                                <p>Acceso gratuito a nuestra playlist de lo-fi por 1 mes</p>
                                                <button>Acceder</button>
                                            </div>
                                        </div>
                                        <div class="WELODV KESAYW HYEXIH">
                                            <img src="<?php echo img('https://2upra.com/wp-content/uploads/2024/05/4.jpg', 40, 'all'); ?>">
                                            <div class="UPYTYH">
                                                <p>ambient sound.wav</p>
                                                <button>Acceder</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <img src="<?php echo esc_url($optimized_url); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
                                <?php endif; ?>
                            </div>
                            <h3 class="XXD1"><?php echo wp_kses_post($image['title']); ?></h3>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="DAEOXT">

                    <div class="QYGNPB ASDASB" id="containerflux">
                        <div class="XXDD EZDNZE" id="stickyContainer">
                            <p class="MLZKPD" id="textflux"></p>
                            <p class="XXD2"></p>
                        </div>
                    </div>

                    <div class="TTVMWQ">
                        <div class="XXDD IUNRBL">
                            <h3 class="XXD1"><strong>Conviértete en patrocinador:</strong> Si te gusta el proyecto, puedes colaborar obteniendo participación creativa, acceso anticipado, contenido exclusivo, reconocimiento y acciones mensuales del proyecto.</h3>
                            <button class="DZYBQD<?php if (!is_user_logged_in()) echo ' boton-sesion'; ?>" id=""><?php echo $GLOBALS['iconoCorazon']; ?>Patrocinar</button>
                        </div>
                        <div class="XXDD IUNRBL">
                            <h3 class="XXD1"><strong>Colabora como desarrollador:</strong> Recibirás una compensación acorde a tu participación, que puede incluir reconocimiento, acciones del proyecto o la posibilidad de formar parte del equipo principal y beneficiarte de las ganancias futuras.</h3>
                            <button class="DZYBQD<?php if (!is_user_logged_in()) echo ' boton-sesion'; ?>" id=""><?php echo $GLOBALS['randomIcono']; ?>Unirte al proyecto</button>
                        </div>
                    </div>


                    <div class="CGUNVP" id="modalregistro">
                        <?php echo registrar_usuario() ?>
                    </div>
                    <div class="EJRINA" id="modalsesion">
                        <?php echo iniciar_sesion() ?>
                    </div>
                    <div id="fondonegro"></div>


                </div>
                <div class="MQGOCQ">
                    <div class="XX1 A1607241136 C2024715" id="contenedor1707">

                        <div class="X170724214 XX7 PROGRESO E17072412" id="ppp4">
                            <div class="XXDD XX9">
                                <div class="XX10">
                                    <h3 class="XXD11" id="startDate">05/01/2024</h3>
                                    <h3 class="XXD1">4. Rehacer y pulir </h3>
                                    <p class="XXD2">En esta etapa, muchas funcionalidades han sido refinadas, incluyendo el rediseño
                                        de la interfaz, mejoras en el rendimiento y una modernización significativa de las
                                        interfaces para artistas y seguidores. Lo más destacable de este periodo es la creación de
                                        funciones más claras y comprensibles para cada tipo de usuario.</p>

                                    <h3 class="XXD1 230624810">En progreso</h3>

                                    <div id="avancesContent" class="avances-content avancesContent">
                                        <ul>
                                            <li>+ Se han rediseñado las interfaces para mejorar la experiencia del usuario.</li>
                                            <li>+ Se ha realizado una separación de funcionalidades específicas para artistas y
                                                seguidores. </li>
                                            <li>+ Las interfaces ahora permiten un mejor entendimiento del propósito general de la
                                                plataforma.</li>
                                            <li>+ Se ha implementado un sistema óptimo para filtrar y encontrar recursos dirigidos a
                                                los artistas / mejora en como se muestra el contenido para seguidores. </li>
                                            <li>+ Se espera pulir las funcionalidades de interacción como las reacciones, chat,
                                                subida contenido y la gestión de este mismo, así como seguir facilitando el
                                                entendimiento para los proximos usuarios nuevos.</li>
                                        </ul>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="X170724214 XX7 PROGRESO E17072412" id="ppp3">
                            <div class="XXDD XX9">
                                <div class="XX10">
                                    <h3 class="XXD11" id="startDate">04/01/2024</h3>
                                    <h3 class="XXD1">3. Complejidad</h3>
                                    <p class="XXD2">En este punto, se comprende que realizar un trabajo de alta calidad requiere una
                                        gran inversión de tiempo, esfuerzo y dedicación. Desarrollar la base para un chat en tiempo
                                        real desde cero es un logro significativo, especialmente considerando que es el primer
                                        acercamiento a la programación. Aunque hacerlo desde la base es complejo, es necesario
                                        debido a la naturaleza del resultado final que se desea alcanzar.</p>

                                    <h3 class="XXD1 230624810">Avances principales</h3>
                                    <div id="avancesContent" class="avances-content avancesContent">
                                        <ul>
                                            <li>+ Se ha implementado un chat en tiempo real para los usuarios.</li>
                                            <li>+ Se han implementado algoritmos básicos para mejorar la visualización del contenido
                                                para los usuarios.</li>
                                            <li>+ Se ha desarrollado un sistema justo para la descarga de contenido y la motivación
                                                para publicar (monedas).</li>
                                            <li>+ Se han realizado mejoras considerables en el rendimiento y el tiempo de carga,
                                                incluyendo un sistema de caché para la música que permite cargar cada pista solo una
                                                vez.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="X170724214 XX7 PROGRESO E17072412" id="ppp2">
                            <div class="XXDD XX9">
                                <div class="XX10">
                                    <h3 class="XXD11" id="startDate">03/01/2024</h3>
                                    <h3 class="XXD1">2. Prueba y errores</h3>
                                    <p class="XXD2">Se han desarrollado diversas funciones desde cero. Debido a la complejidad y los
                                        requisitos del proyecto, todas las funcionalidades se están programando desde la base. A
                                        pesar de que muchas de las funcionalidades complejas ya operan correctamente, aún requieren
                                        mejoras en cuanto a calidad, experiencia visual y otros aspectos para alcanzar el nivel
                                        deseado.</p>

                                    <h3 class="XXD1 230624810">Avances principales</h3>

                                    <div id="avancesContent" class="avances-content avancesContent">
                                        <ul>
                                            <li>+ Se han implementado exitosamente todas las funcionalidades de interactividad,
                                                tales como "Me gusta", comentarios, notificaciones, la opción de seguir a otros
                                                usuarios, etc.</li>
                                            <li>+ Se han desarrollado las funcionalidades necesarias para la monetización de
                                                contenido, incluyendo la publicación de beats para la venta, suscripciones y
                                                compras.</li>
                                            <li>+ Se ha estructurado un modelo similar al de Spotify para la reproducción de música.
                                            </li>
                                            <li>+ La carga dinámica de páginas y todo tipo de contenido se realiza de manera
                                                eficiente.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="X170724214 XX7 PROGRESO E17072412" id="ppp1">
                            <div class="XXDD XX9">
                                <div class="XX10">
                                    <h3 class="XXD11" id="startDate">01/01/2024</h3>
                                    <h3 class="XXD1">1. Comienzo</h3>
                                    <p class="XXD2">El planteamiento es claro: desarrollar una plataforma con funcionalidades
                                        innovadoras, que incluya un conjunto de herramientas para artistas y un espacio dedicado
                                        para sus seguidores. Inicialmente, se estimó que la realización de este proyecto no tomaría
                                        más de dos meses; sin embargo, la complejidad del mismo fue subestimada desde el principio.
                                    </p>

                                    <h3 class="XXD1 230624810">Avances principales</h3>

                                    <div id="avancesContent" class="avances-content avancesContent">
                                        <ul>
                                            <li>+ Se plantea las funcionalidades principales.</li>
                                            <li>+ Se consigue inversiones necesarias y recurrentes para el proyecto.</li>
                                            <li>+ Se comienza a escribir las primeras lineas de codigo.</li>
                                        </ul>
                                    </div>
                                    <p id="timeAgo"></p>
                                </div>
                            </div>
                        </div>
                        <p class="textopeq">+</p>
                        <div id="barraProgreso1707"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>


<?php
    return ob_get_clean();
}


function devlogin()
{
    $current_user = wp_get_current_user();
    $user_name = $current_user->display_name;
    $user_id = get_current_user_id();
    $acciones = get_user_meta($user_id, 'acciones', true);
    $pro = get_user_meta($user_id, 'user_pro', true);
    ob_start();
?>

    <div class="tabs">
        <div class="tab-content">
            <div class="tab active GMXSUJ LOGIN84" id="inicio">
                <?php if ($acciones > 1 || $pro) : ?>
                    <?php echo inversor(); ?>
                <?php else: ?>
                    <div class="UIKMYM">




                        <div class="WZEFLA">
                            <p>Hola <?php echo esc_html($user_name) ?></p>
                        </div>

                        <div class="OIEODG">
                            <p>Gracias por participar, estamos trabajando en mejorar la expriencia de entorno.</p>
                        </div>

                        <div class="JUJRQG">
                            <a href="https://github.com/1ndoryu" class="no-ajax">
                                <button class="DZYBQD" id="github-button">
                                    <?php echo $GLOBALS['Github']; ?> GitHub
                                </button>
                            </a>

                            <a href="https://chat.whatsapp.com/IGHrIfvifHS9Fwz4ha6Uis" class="no-ajax">
                                <button class="DZYBQD" id="whatsapp-button">
                                    <?php echo $GLOBALS['Whatsapp']; ?> WhatsApp
                                </button>
                            </a>

                        </div>
                    <?php endif; ?>

                    </div>

                    <?php if ($pro) : ?>

                    <?php else: ?>
                        <div class="DAEOXT">

                            <div class="TTVMWQ">
                                <div class="XXDD IUNRBL">
                                    <h3 class="XXD1"><strong>Conviértete en patrocinador:</strong> Si te gusta el proyecto, puedes colaborar obteniendo participación creativa, acceso anticipado, contenido exclusivo, reconocimiento y acciones mensuales del proyecto.</h3>
                                    <div class="DZYSQD DZYSQF">
                                        <button class="DZYBQD subpro<?php if (!is_user_logged_in()) echo ' boton-sesion'; ?>" id=""><?php echo $GLOBALS['iconoCorazon']; ?>Patrocinar</button>
                                        <button class="DZYBQD donar<?php if (!is_user_logged_in()) echo ' boton-sesion'; ?>" id="donarproyecto"><?php echo $GLOBALS['dolar']; ?>Donar</button>
                                    </div>
                                </div>
                                <div class="XXDD IUNRBL">
                                    <h3 class="XXD1"><strong>Colabora como desarrollador:</strong> Recibirás una compensación acorde a tu participación, que puede incluir reconocimiento, acciones del proyecto o la posibilidad de formar parte del equipo principal y beneficiarte de las ganancias futuras.</h3>
                                    <div class="DZYSQD DZYSQF">
                                        <button class="DZYBQD unirteproyecto<?php if (!is_user_logged_in()) echo ' boton-sesion'; ?>" id="unirteproyecto"><?php echo $GLOBALS['randomIcono']; ?>Unirte al proyecto</button>

                                    </div>

                                </div>
                            </div>

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

                            <div class="HMPGRM" id="modalinvertir">
                                <div id="contenidocomprar">
                                    <input type="text" id="cantidadCompra" placeholder="$">
                                    <input type="hidden" id="cantidadReal">
                                    <input type="hidden" id="userID" value="<?php echo get_current_user_id(); ?>">
                                    <p>"Al donar, una parte de tu contribución se convierte en acciones de nuestra empresa a través de nuestro fondo de inversión algorítmico. Este sistema innovador ajusta automáticamente el valor de la empresa basándose en ingresos, gastos y otros factores clave. Tu apoyo no solo impulsa el proyecto, sino que te convierte en parte de nuestro crecimiento. Si en el futuro decides vender tus acciones, podrías beneficiarte económicamente del incremento de valor de la empresa."</p>
                                    <div class="DZYSQD DZYSQF">
                                        <button class="DZYBQD" id="botonComprar">Donar</button>
                                        <button class="DZYBQD cerrardonar">Volver</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
            </div>
        </div>
    </div>


<?php
    return ob_get_clean();
}

function proyectoForm_callback() {
    guardar_log(print_r($_POST, true));

    $usernameReal = sanitize_text_field($_POST['usernameReal']);
    $number = sanitize_text_field($_POST['number']);
    $programmingExperience = sanitize_text_field($_POST['programmingExperience']);
    $reasonToJoin = sanitize_textarea_field($_POST['reasonToJoin']);
    $country = sanitize_text_field($_POST['country']);
    $projectAttitude = sanitize_textarea_field($_POST['projectAttitude']);
    $wordpressAttitude = sanitize_textarea_field($_POST['wordpressAttitude']);
    $projectInitiative = sanitize_text_field($_POST['projectInitiative']);
    $projectInitiativeOther = sanitize_textarea_field($_POST['projectInitiativeOther']);
    $user_id = get_current_user_id();

    update_user_meta($user_id, 'usernameReal', $usernameReal);
    update_user_meta($user_id, 'number', $number);
    update_user_meta($user_id, 'programmingExperience', $programmingExperience);
    update_user_meta($user_id, 'reasonToJoin', $reasonToJoin);
    update_user_meta($user_id, 'country', $country);
    update_user_meta($user_id, 'projectAttitude', $projectAttitude);
    update_user_meta($user_id, 'wordpressAttitude', $wordpressAttitude);
    update_user_meta($user_id, 'projectInitiative', $projectInitiative);
    update_user_meta($user_id, 'projectInitiativeOther', $projectInitiativeOther);

    $log_message = "Formulario procesado para el usuario ID: $user_id. Nombre Real: $usernameReal.";
    guardar_log($log_message);

    wp_send_json_success('Datos guardados correctamente.');
    wp_die();
}
add_action('wp_ajax_proyectoForm', 'proyectoForm_callback');

function obtener_resumen_formulario_usuario($user_id) {
    $usernameReal = get_user_meta($user_id, 'usernameReal', true);
    $number = get_user_meta($user_id, 'number', true);
    $programmingExperience = get_user_meta($user_id, 'programmingExperience', true);
    $reasonToJoin = get_user_meta($user_id, 'reasonToJoin', true);
    $country = get_user_meta($user_id, 'country', true);
    $projectAttitude = get_user_meta($user_id, 'projectAttitude', true);
    $wordpressAttitude = get_user_meta($user_id, 'wordpressAttitude', true);
    $projectInitiative = get_user_meta($user_id, 'projectInitiative', true);
    $projectInitiativeOther = get_user_meta($user_id, 'projectInitiativeOther', true);

    // Agrupar todos los datos en un array
    $resumen = [
        'Nombre Real' => $usernameReal,
        'Número' => $number,
        'Experiencia en Programación' => $programmingExperience,
        'Razón para Unirse' => $reasonToJoin,
        'País' => $country,
        'Actitud hacia el Proyecto' => $projectAttitude,
        'Actitud hacia WordPress' => $wordpressAttitude,
        'Iniciativa de Proyecto' => $projectInitiative,
        'Otra Iniciativa de Proyecto' => $projectInitiativeOther,
    ];

    // Registrar el log de la operación
    guardar_log("Resumen de formulario generado para el usuario ID: $user_id.");

    return $resumen;
}