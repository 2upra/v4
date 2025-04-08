<?php
// Archivo creado para contener la función papelera() movida desde app/Pages/Temporal.php.
// Acción realizada: crear_archivo
// Propósito: Contendrá la función papelera() movida desde app/Pages/Temporal.php.

// Acción: Función papelera() movida desde app/Pages/Temporal.php
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
                    <?php echo $GLOBALS['Github']; ?> GitHub
                </button>
            </a>

            <a href="https://chat.whatsapp.com/JOduGKvWGR9KbYfBS9BWGL" class="no-ajax">
                <button class="DZYBQD" id="whatsapp-button">
                    <?php echo $GLOBALS['Whatsapp']; ?> WhatsApp
                </button>
            </a>

            <?php botonSponsor() ?>

        </div>

        <div class="CGUNVP" id="modalregistro">
            <?php echo registrar_usuario() ?>
        </div>
        <div class="EJRINA" id="modalsesion">
            <?php echo iniciar_sesion() ?>
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
<?php
    return ob_get_clean();
}

?>
