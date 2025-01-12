<?

function asleyTab()
{

    ob_start();
?>

    <div id="menuData" style="display:none;" pestanaActual="">
        <div data-tab="Proyecto"></div>

    </div>

    <div class="tabs">
        <div class="tab-content">

            <div class="tab S4K7I3 asleyPorf" id="Proyecto">
                <? echo portafolio(); ?>
            </div>

        </div>
    </div>

<?
    return ob_get_clean();
}

function portafolio()
{

    ob_start();

?>


    <div class="SOKDEOD" id="inicioDiv">
        <h2>soy <img src="<? echo get_template_directory_uri(); ?>/assets/img/asleywandorius1.jpg" alt=""><span id="asley">Asley </span> developer </h2>
        <h2></h2>
        <h2 id="flst">Full Stack</h2>
        <div class="LXCJWW">
            <button class="borde">Descargar CV</button>
            <button class="borde">Contactar</button>
        </div>

    </div>

    <div class="BKXAFN">
        <div style="display: none">
            <div class="JMIOCI">
                <h1>Libertad música para almas libres</h1>
                <p>Biblioteca de recursos musicales con herramientas potenciadas para la colaboración e independencia artistica.</p>
            </div>
            <div class="BNZWJR" style="display: none;">
                <span class="MASYTN GFDFDN">Total recaudado $632</span>
                <span class="MASYTN WJTTLG">Meta final $1000</span>
            </div>
            <div class="LXCJWW">
                <button class="borde carta" aria-label="Carta" style="display: none;">Carta</button>
                <button class="botonprincipal<? if (!is_user_logged_in()) echo ' boton-sesion'; ?>" aria-label="Entrar">Iniciar sesión</button>
            </div>

        </div>
        <div class="OSFED" id="caracteristicas">
            <div class="ADEEDE">
                <div class="div1 bloque svg-container">
                    <div class="tinfo" style="display: none;">
                        <p class="ttpri" style="display: none;">test</p>
                        <p class="ttsec">2UPRA nace debido a la falta de centralización e innovación en apps de producción musical. Las herramientas actuales están dispersas y no optimizadas. Por eso nuestro enfoque es ofrecer una experiencia única en todas las etapas de producción musical.</p>
                    </div>
                    <div class="lazy-svg" data-src="https://2upra.com/wp-content/themes/2upra3v/assets/svgs/div-1.svg"></div>
                </div>
                <div class="div2 bloque svg-container">
                    <div class="tinfo" style="display: none;">
                        <p class="ttpri" style="display: none;">test</p>
                        <p class="ttsec">La principal virtud del proyecto es el uso de inteligencia artificial y machine learning para reconocimiento de patrones, optimización, mejora de algoritmos y supervisión. 2UPRA aprende de los usuarios para mejorar continuamente.</p>
                    </div>
                    <img src="https://2upra.com/wp-content/uploads/2024/11/Recurso-4@2x-1.png" alt="">
                </div>
                <div class="div3 bloque svg-container">
                    <div class="tinfo" style="display: none;">
                        <p class="ttpri" style="display: none;"></p>
                        <p class="ttsec">Enfoque social: plataforma optimizada para la comunicación entre productores, artistas y fans, ofreciendo una experiencia única que facilita los procesos creativos.</p>
                    </div>
                    <div class="lazy-svg" data-src="https://2upra.com/wp-content/themes/2upra3v/assets/svgs/div-3.svg"></div>
                </div>
                <div class="div4 bloque svg-container">
                    <div class="tinfo" style="display: none;">
                        <p class="ttpri" style="display: none;"></p>
                        <p class="ttsec">Sync 2UPRA: herramienta única que facilita la organización, recolección y uso de recursos. Olvídate de dónde guardar tus samples uno por uno, se sincronizarán y organizarán automáticamente de forma eficaz para que puedas acceder a ellos fácilmente cuando los necesites en tu pc.</p>
                    </div>
                    <div class="lazy-svg" data-src="https://2upra.com/wp-content/themes/2upra3v/assets/svgs/div-4.svg"></div>
                </div>
                <div class="div5 bloque svg-container">
                    <div class="tinfo" style="display: none;">
                        <p class="ttpri" style="display: none;"></p>
                        <p class="ttsec">Colecciones: organiza tus recursos musicales en colecciones personalizables y compártelas con la comunidad o de forma privada. Descubre nuevas colecciones y amplía tus horizontes musicales.</p>
                    </div>
                    <div class="lazy-svg" data-src="https://2upra.com/wp-content/themes/2upra3v/assets/svgs/div-5.svg"></div>
                </div>
                <div class="div6 bloque svg-container">
                    <div class="tinfo" style="display: none;">
                        <p class="ttpri" style="display: none;"></p>
                        <p class="ttsec">Algoritmos inteligentes de recomendación: 2UPRA entiende tus gustos, te ayuda a organizar ideas y recomienda recursos apropiados para ti y tus colecciones.</p>
                    </div>
                    <div class="lazy-svg" data-src="https://2upra.com/wp-content/themes/2upra3v/assets/svgs/div-6.svg"></div>
                </div>
                <div class="div7 bloque svg-container">
                    <div class="tinfo" style="display: none;">
                        <p class="ttpri" style="display: none;"></p>
                        <p class="ttsec">Mantente conectado como en cualquier red social, pero impulsando tu crecimiento artístico. Comparte tus creaciones, colabora con otros artistas, descubre oportunidades y sigue la trayectoria de tus ídolos.</p>
                    </div>
                    <div class="lazy-svg" data-src="https://2upra.com/wp-content/themes/2upra3v/assets/svgs/div-7.svg"></div>
                </div>
                <div class="div8 bloque svg-container">
                    <div class="tinfo" style="display: none;">
                        <p class="ttpri" style="display: none;"></p>
                        <p class="ttsec">Múltiples fuentes de ingresos: en 2UPRA usamos algoritmos inteligentes para compensar tu esfuerzo. Invita a tus fans a suscribirse o publica tus servicios, tu crecimiento artístico y tu bienestar van de la mano.</p>
                    </div>
                    <div class="lazy-svg" data-src="https://2upra.com/wp-content/themes/2upra3v/assets/svgs/div-8.svg"></div>
                </div>
                <div class="div9 bloque svg-container">
                    <div class="tinfo" style="display: none;">
                        <p class="ttpri" style="display: none;"></p>
                        <p class="ttsec">Exprésate de muchas formas: en 2UPRA comprendemos que el arte tiene múltiples expresiones. Comunica tus ideas, emociones y proyectos como desees, ya sea a través de música, videos, imágenes o texto, y acércate a otros artistas y fans de maneras diversas.</p>
                    </div>
                    <div class="lazy-svg" data-src="https://2upra.com/wp-content/themes/2upra3v/assets/svgs/div-9.svg"></div>
                </div>
                <div class="div10 bloquesvg-container" style="display: none">
                </div>
            </div>
        </div>

        <style>
            /* Contenedor principal */
            /* Posicionamiento personalizado de los bloques */
        </style>




    <?
    return ob_get_clean();
}
