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
    <script>
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const div = entry.target;
                        const src = div.getAttribute('data-src');
                        if (src) {
                            // Usa fetch para cargar el contenido del SVG
                            fetch(src)
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Error al cargar el SVG');
                                    }
                                    return response.text();
                                })
                                .then(svg => {
                                    div.innerHTML = svg; // Inserta el SVG en el div
                                    div.removeAttribute('data-src'); // Limpia el data-src
                                })
                                .catch(err => {});

                            observer.unobserve(div); // Deja de observar el elemento
                        } else {}
                    }
                });
            });

            // Seleccionamos todos los elementos con la clase 'lazy-svg'
            const lazySvgs = document.querySelectorAll('.lazy-svg');

            lazySvgs.forEach(div => {
                observer.observe(div);
            });
        } else {}
    </script>

    <div class="SOKDEOD" id="inicioDiv">
        <h2>I'M ASLEY DEVELOPER</h2>
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
                <div class="diva1 bloque svg-container">

                    <h3>BIO</h3>

                    <p>Web developer highly skilled in complex algorithm development, efficient architecture design, and database management within Linux environments.</p>

                    <p>Disciplined and focused on project planning and execution. Proven ability to create interactive web applications, from initial design to final implementation. Seeking a challenging role where I can contribute with innovative and high-impact solutions.</p>

                </div>
                <div class="diva2 bloque svg-container">
                    <div class="tinfo" style="display: none;">
                        <p class="ttpri" style="display: none;">test</p>
                        <p class="ttsec">La principal virtud del proyecto es el uso de inteligencia artificial y machine learning para reconocimiento de patrones, optimización, mejora de algoritmos y supervisión. 2UPRA aprende de los usuarios para mejorar continuamente.</p>
                    </div>
                    
                    <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logos.svg"></div>
                </div>

                <div class="diva3 bloque svg-container">
                    <div class="tinfo" style="display: none;">
                        <p class="ttpri" style="display: none;"></p>
                        <p class="ttsec">Enfoque social: plataforma optimizada para la comunicación entre productores, artistas y fans, ofreciendo una experiencia única que facilita los procesos creativos.</p>
                    </div>

                </div>

            </div>
        </div>

        <style>
            .diva1 {
                grid-area: 1 / 1 / 2 / 2;
            }

            .diva2 {
                grid-area: 1 / 2 / 2 / 3;
            }

            .diva3 {
                grid-area: 1 / 3 / 2 / 4;
            }
        </style>




    <?
    return ob_get_clean();
}
