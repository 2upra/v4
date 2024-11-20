<?

function papelera()
{
    ob_start();
?>
    <div class="UIKMYM" style="display: none;">

        <div class="WZEFLA">
            <p>Proyecto 2upra</p>
        </div>

        <div class="OIEODG mb-3">
            <p>2upra es una plataforma social que busca simplificar y mejorar la experiencia de los artistas en la producción musical. Nuestro objetivo es crear un espacio donde los músicos puedan acceder fácilmente a recursos como samples, plugins VST y herramientas de colaboración, todo en un solo lugar. Además, 2upra facilita la promoción y distribución de música, permitiendo a los artistas emergentes llegar a nuevas audiencias sin complicaciones.<br></p>

            <p>En 2upra, los artistas pueden conectarse entre sí, colaborar en proyectos y vender o comprar samples, creando una comunidad dinámica y autosuficiente. Los fans también juegan un papel importante en nuestra plataforma, ya que pueden suscribirse a sus artistas favoritos, recibir actualizaciones exclusivas y apoyar directamente a los creadores que admiran.<br></p>

            <p>Nuestro enfoque principal es apoyar a los artistas emergentes, brindándoles las herramientas necesarias para destacar en la industria musical. Creemos en democratizar el acceso a la producción y promoción musical, asegurando que cada talento tenga la oportunidad de ser escuchado.<br></p>

            <p>En un mundo dominado por algoritmos, donde el éxito depende de la suerte en la viralización y de cuántos post puedes crear para posicionarte en algún trend de TikTok, surge un problema grave: el arte ya no predomina por su significado ni por su valor, sino por su capacidad de viralización. 2upra busca establecer un sistema justo que alivie la necesidad de contenido basura, no luchando contra él, sino abriendo nuevas puertas para explorar el mundo artístico musical de nuevas formas.<br></p>

            <p>¿Como puedo apoyar el proyecto?<br></p>

            <p>Hemos abierto el código fuente de 2upra en GitHub para que cualquier programador pueda unirse al proyecto y colaborar. Estamos abiertos a recibir donaciones o patrocinio.<br></p>
        </div>

        <div class="JUJRQG">

            <a href="https://github.com/1ndoryu" class="no-ajax">
                <button class="DZYBQD" id="github-button">
                    <? echo $GLOBALS['Github']; ?> GitHub
                </button>
            </a>

            <a href="https://chat.whatsapp.com/JOduGKvWGR9KbYfBS9BWGL" class="no-ajax">
                <button class="DZYBQD" id="whatsapp-button">
                    <? echo $GLOBALS['Whatsapp']; ?> WhatsApp
                </button>
            </a>

            <? botonSponsor() ?>

        </div>

        <div class="CGUNVP" id="modalregistro">
            <? echo registrar_usuario() ?>
        </div>
        <div class="EJRINA" id="modalsesion">
            <? echo iniciar_sesion() ?>
        </div>

        <div class="QYGNPB YDFVMQ">
            <div class="XXDD EZDNZE THFJWV">
                <p class="MLZKPD">¿Que ofreceremos a los artistas?</p>
                <p class="XXD2"></p>
            </div>
        </div>

        <div class="QYGNPB ASDASB" id="containerflux">
            <div class="XXDD EZDNZE" id="stickyContainer">
                <p class="MLZKPD" id="textflux"></p>
                <p class="XXD2"></p>
            </div>
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
<?
}

function dev()
{
    ob_start();
    //      
?>

    <div class="tabs">
        <div class="tab-content">
            <div class="tab active GMXSUJ" id="inicio">

                <div class="BKXAFN">

                    <div class="JMIOCI">
                        <h1>Un entorno libre para almas libres</h1>
                        <p>Biblioteca de recursos musicales con herramientas potenciadas para la colaboración e independencia artistica.</p>
                    </div>
                    <div class="BNZWJR" style="display: none;">
                        <span class="MASYTN GFDFDN">Total recaudado $632</span>
                        <span class="MASYTN WJTTLG">Meta final $1000</span>
                    </div>
                    <div class="LXCJWW">
                        <button class="borde carta" aria-label="Carta">Carta</button>
                        <button class="botonprincipal<? if (!is_user_logged_in()) echo ' boton-sesion'; ?>" aria-label="Entrar">Iniciar sesión</button>
                    </div>

                </div>



                <div class="XX1 XX2">
                    <?
                    $images = [
                        [
                            'title' => '<strong>Recursos gratis</strong> para tu música: descarga samples, drumkits, VST y mucho más.',
                        ],
                        [
                            'title' => '<strong>Conecta y colabora</strong> con otros artistas para impulsar juntos tu carrera musical y crear proyectos increíbles.',
                        ],
                        [
                            'url' => 'https://2upra.com/wp-content/uploads/2024/05/asfsdf4.png',
                            'title' => '<strong>Gana con tu arte</strong>: diversifica tus ingresos y recibe el apoyo de tus fans a través de contenido exclusivo.',
                            'alt' => 'Gana con tu arte - imagen de ingresos diversificados',
                        ],
                        [
                            'title' => '<strong>Producción más sencilla</strong>: Accede a playlists, distribuye tu música y aumenta tu alcance sin complicaciones.',
                        ],
                        [
                            'url' => 'https://2upra.com/wp-content/uploads/2024/10/fdsfasfhgt.png',
                            'title' => '<strong>Comparte tus proyectos</strong>: Únete a nuestra comunidad abierta y comparte tus trabajos libremente con otros artistas y fans.',
                            'alt' => 'Comparte tus proyectos - imagen de comunidad artística',
                        ],
                        [
                            'url' => 'https://2upra.com/wp-content/uploads/2024/10/Recurso-1.png',
                            'title' => '<strong>Biblioteca de samples inteligente</strong>: impulsada por IA para organizar y encontrar samples que se adapten a tus gustos.',
                            'alt' => 'Biblioteca de samples inteligente impulsada por IA',
                        ],
                    ];

                    foreach ($images as $index => $image):
                        $optimized_url = isset($image['url']) ? img($image['url'], 'medium', 50, 'all') : '';
                    ?>
                        <div class="XXDD">
                            <div class="spaceimagen index-<? echo $index; ?>">
                                <? if ($index === 0): ?>
                                    <div class="KTEPUZ">
                                        <div class="WELODV">
                                            <img src="<? echo img('https://2upra.com/wp-content/uploads/1107885577068943408_и.jpg', 40, 'all'); ?>" alt="Sample pack vol 1 winrar">
                                            <p>Sample_pack_vol_1.winrar</p>
                                            <? echo botonDescargaPrueba(); ?>
                                        </div>
                                        <div class="WELODV KESAYW">
                                            <img src="<? echo img('https://2upra.com/wp-content/uploads/1107885577066304428_Magnetic-aura-subliminal.jpg', 40, 'all'); ?>" alt="Ambient sound wav">
                                            <p>ambient sound.wav</p>
                                            <? echo botonDescargaPrueba(); ?>
                                        </div>
                                    </div>
                                <? elseif ($index === 1): ?>
                                    <div class="KTEPUZ JOJLEZ">
                                        <div class="WELODV OQDGCR">
                                            <img src="<? echo img('https://2upra.com/wp-content/uploads/2024/05/2.webp', 40, 'all'); ?>" alt="Wandorius artista colaborador">
                                            <p>Wandorius</p>
                                        </div>
                                        <div class="HPDTIR">
                                            <? echo $GLOBALS['present1']; ?>
                                        </div>
                                        <div class="WELODV KESAYW OQDGCR">
                                            <img src="<? echo img('https://2upra.com/wp-content/uploads/2024/05/1.webp', 40, 'all'); ?>" alt="Billie Eilish colaboradora">
                                            <p>Billie Eilish</p>
                                        </div>
                                    </div>
                                <? elseif ($index === 3): ?>
                                    <div class="KTEPUZ UEMOGY">
                                        <div class="WELODV HYEXIH">
                                            <img src="<? echo img('https://2upra.com/wp-content/uploads/2024/10/e285720ece097bcf54447cad123c92a6.jpg', 40, 'all'); ?>" alt="Playlist de Hip Hop Lofi">
                                            <div class="UPYTYH">
                                                <p>Playlist de Hip Hop Lofi </p>
                                                <button>Acceder</button>
                                            </div>
                                        </div>
                                        <div class="WELODV KESAYW HYEXIH">
                                            <img src="<? echo img('https://2upra.com/wp-content/uploads/2024/05/4.jpg', 40, 'all'); ?>" alt="Ambient sound wav">
                                            <div class="UPYTYH">
                                                <p>ambient sound.wav</p>
                                                <button>Acceder</button>
                                            </div>
                                        </div>
                                    </div>
                                <? else: ?>
                                    <img src="<? echo esc_url($optimized_url); ?>" alt="<? echo isset($image['alt']) ? esc_attr($image['alt']) : 'Imagen sin descripción'; ?>">
                                <? endif; ?>
                            </div>
                            <h3 class="XXD1"><? echo wp_kses_post($image['title']); ?></h3>
                        </div>
                    <? endforeach; ?>
                </div>

            </div>
        </div>
    </div>


<?
    return ob_get_clean();
}

function modalCarta()
{
    ob_start();
?>
    <div class="TAZFZZ" id="modalCarta" style="display: none;" data-nosnippet>
        <div class="XCTQCK">
            <div class="WMPVLD"> <? echo $GLOBALS['iconologo']; ?></div>
            <div class="JMEPEB">
                <p>2 de octubre 2024,</p>

                <p>No voy a hablar del proyecto ni a decir que es el próximo gran hito en aplicaciones web. Hoy quiero contarte un poco sobre mí, quién soy y por qué esto es tan importante para mí.</p>

                <p>Este no es mi primer proyecto. Algunas cosas me han salido bien en el pasado, y otras no tanto. Sin embargo, la experiencia me ha brindado las herramientas necesarias para construir cosas cada vez más complejas.</p>

                <p>En el proceso, la música se ha convertido en una parte fundamental de mi vida. Cuando comienzas una carrera musical, tienes ciertas expectativas, y podría parecer que la falta de experiencia o esfuerzo serían los mayores obstáculos. ¡Ja! Si solo dependiera de eso, sería genial. Claro, el talento y hacer las cosas bien son lo más importante, pero me he dado cuenta de que hay muchos problemas en la producción musical que nadie parece dispuesto a resolver, sobre todo para las personas que no podemos costear los cientos de servicios necesarios. Las herramientas actuales están dispersas o te obligan a pagar suscripciones de $20 al mes cada una para usarlas, pffff.</p>

                <p>Los productores necesitamos algo más accesible y práctico. Queremos poder separar pistas fácilmente, tener kits de batería frescos todos los días, un espacio donde encontrar colaboraciones, organizar nuestros samples, o tener una lista de sonidos favoritos para el próximo proyecto. Queremos subir el WAV a todas las malditas plataformas y no tener que esperar un mes para se publiquen o un año si queremos borrarla porque ya no nos gusta en nuestro catalago, y que buena idea sería un lugar donde los artistas pequeños puedan recibir apoyo directo de sus fans, y que esten todas su música junto a cualquier contenido que deseen compartir, fuah.</p>

                <p>Lo bueno es que estoy dispuesta a construir cada una de estas soluciones, porque la independencia y resolver problemas le dan sentido a mi vida. He aprendido todo lo necesario para empezar, y estoy dispuesta a seguir aprendiendo lo que haga falta para lograrlo. También he aprendido que quizás no pueda hacerlo sola, y que no está mal aceptar que necesito un poco de ayuda, y la verdad, la necesito esta vez, cualquier pequeño apoyo hará la diferencia.</p>

            </div>
            <div class="WMPVLV">
                <img src="<? echo img('https://2upra.com/wp-content/uploads/2024/10/afsafad.png') ?>" alt="">
                <p>Wandorius</p>
            </div>
            <button class="borde cerrarCarta">Volver</button>
        </div>
    </div>
<?
    return ob_get_clean();
}

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

function devlogin()
{
    $current_user = wp_get_current_user();
    $user_name = $current_user->display_name;
    $user_id = get_current_user_id();
    $acciones = get_user_meta($user_id, 'acciones', true);
    $pro = get_user_meta($user_id, 'user_pro', true);
    $resultados = calc_ing();
    $valEmp = "$" . number_format($resultados['valEmp'], 2, '.', '.');
    $valAcc = "$" . number_format($resultados['valAcc'], 2, '.', '.');
    $user = wp_get_current_user();
    $acc = get_user_meta($user->ID, 'acciones', true);
    $valD = $acc * $resultados['valAcc'];
    $name = ($user->display_name);
    ob_start();
?>



    <? // if ($acciones > 1 || $pro) : 
    ?>
    <? // echo panelInversor(); 
    ?>

    <div class="UIKMYM">

        <div class="WZEFLA">
            <p>Hola <? echo esc_html($user_name) ?></p>
        </div>

        <div class="OIEODG">
            <p>Gracias por participar, estamos trabajando en mejorar la experiencia de entorno.</p>
        </div>

        <div class="JUJRQG">
            <a href="https://github.com/1ndoryu" class="no-ajax">
                <button class="DZYBQD" id="github-button">
                    <? echo $GLOBALS['Github']; ?> GitHub
                </button>
            </a>

            <a href="https://chat.whatsapp.com/G8hH7Gytfn5D2uYPibZT7N" class="no-ajax">
                <button class="DZYBQD" id="whatsapp-button">
                    <? echo $GLOBALS['Whatsapp']; ?> WhatsApp
                </button>
            </a>

        </div>

    </div>

    <? // if ($pro) : 
    ?>

    <? // else: 
    ?>
    <div class="DAEOXT">

        <div class="TTVMWQ">
            <div class="XXDD IUNRBL">
                <h3 class="XXD1"><strong>Conviértete en patrocinador:</strong> Puedes colaborar obteniendo participación creativa, acceso anticipado, contenido exclusivo, reconocimiento y acciones mensuales del proyecto.</h3>

                <div class="DZYSQD DZYSQF">
                    <? echo botonSponsor() ?>
                    <? echo botonComprarAcciones() ?>
                </div>

            </div>
            <div class="XXDD IUNRBL">
                <h3 class="XXD1"><strong>Colabora como desarrollador:</strong> Recibirás una compensación acorde a tu participación, que puede incluir reconocimiento, acciones del proyecto o la posibilidad de formar parte del equipo principal y beneficiarte de las ganancias futuras.</h3>
                <a href="https://chat.whatsapp.com/JOduGKvWGR9KbYfBS9BWGL" class="no-ajax">
                    <div class="DZYSQD DZYSQF">
                        <button class="DZYBQD unirteproyecto<? if (!is_user_logged_in()) echo ' boton-sesion'; ?>"><? echo $GLOBALS['randomIcono']; ?>Unirte al proyecto</button>
                    </div>
                </a>

            </div>
        </div>

        <div class="XFBZWO MLJOFR">
            <div class="flex">

                <div class="QSBVLN">
                    <p class="ZTHAWI">Total recaudado</p>
                    <p class="BFUUUL">722$</p>
                </div>

                <div class="MDOKUH">
                    <p class="ZTHAWI">Meta</p>
                    <p class="BFUUUL">5000$</p>
                </div>

            </div>

            <div class="progress-containerA1">
                <div class="progress-barA1"></div>
            </div>

            <div class="GTVVIG">

                <div class="XFBZWO">
                    <div class="flex justify-between items-center">
                        <p class="ZTHAWI">Tu valor actual</p>
                    </div>
                    <p class="BFUUUL">$<? echo number_format($valD, 2, '.', '.'); ?></p>
                    <div class="GraficoCapital">
                        <? echo graficoHistorialAcciones() ?>
                    </div>
                </div>

                <div class="XFBZWO">
                    <p class="ZTHAWI">Valor 2upra</p>
                    <p class="BFUUUL"><? echo $valEmp ?></p>
                    <div class="GraficoCapital">
                        <? echo capitalValores() ?>
                    </div>
                </div>

                <div class="XFBZWO">
                    <p class="ZTHAWI">Valor Acción</p>
                    <p class="BFUUUL"><? echo $valAcc ?></p>
                    <div class="GraficoCapital">
                        <? echo bolsavalores() ?>
                    </div>
                </div>

            </div>

        </div>

        <div class="WLOZDD">
            <p>Muchas gracias</p>
            <? echo calcularAccionPorUsuario() ?>
        </div>

        <? if (current_user_can('administrator')) : ?>
            <div class="YXJWYY flex ">
                <div class="XFBZWO">
                    <? echo formCompraAcciones() ?>
                </div>
            </div>
        <? endif; ?>



        <? echo modalComprarAcciones() ?>

    </div>
    <? //endif; 
    ?>



<?
    return ob_get_clean();
}



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
<? return ob_get_clean();
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