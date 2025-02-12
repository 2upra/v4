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
        function iniciarLazySvg() {
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const div = entry.target;
                            const src = div.getAttribute('data-src');
                            if (src) {
                                fetch(src)
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error('Error al cargar el SVG');
                                        }
                                        return response.text();
                                    })
                                    .then(svg => {
                                        div.innerHTML = svg;
                                        div.removeAttribute('data-src');
                                    })
                                    .catch(err => {
                                        throw new Error('Error al procesar el SVG');
                                    });

                                observer.unobserve(div);
                            }
                        }
                    });
                }, {
                    rootMargin: '100px 0px',
                });

                const lazySvgs = document.querySelectorAll('.lazy-svg');

                lazySvgs.forEach(div => {
                    observer.observe(div);
                });
            }
            iniciarPestanasPf();
        }

        function reiniciarLazySvg() {
            document.querySelectorAll('.lazy-svg').forEach(div => {
                if (!div.querySelector('svg') && div.dataset.src) {
                    const src = div.dataset.src;
                    div.innerHTML = '';
                    fetch(src)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Error al cargar el SVG');
                            }
                            return response.text();
                        })
                        .then(svg => {
                            div.innerHTML = svg;
                        })
                        .catch(err => {
                            throw new Error('Error al reiniciar el SVG');
                        });
                }
            });
        }

        //cuando llega al final de un itemPortafolio, debería mostrar la siguiente pestañana pero no lo hace
        function iniciarPestanasPf() {
            const botonesPestana = document.querySelectorAll('.botonesProyectos span[pest]');
            const itemsPortafolio = document.querySelectorAll('.itemPortafolio');
            let pestanaActiva = null;

            function mostrarPestana(pestanaNombre) {
                itemsPortafolio.forEach(item => {
                    item.style.display = 'none';
                });
                const itemActivo = document.getElementById(pestanaNombre);
                if (itemActivo) {
                    itemActivo.style.display = 'grid';
                    pestanaActiva = itemActivo;
                }
            }

            function activarPestanaBoton(pestanaNombre) {
                botonesPestana.forEach(b => b.classList.remove('pestActivo'));
                const botonActivo = Array.from(botonesPestana).find(boton => boton.getAttribute('pest') === pestanaNombre);
                if (botonActivo) {
                    botonActivo.classList.add('pestActivo');
                }
            }

            if (botonesPestana.length > 0) {
                botonesPestana[0].classList.add('pestActivo');
                mostrarPestana(botonesPestana[0].getAttribute('pest'));
            }

            botonesPestana.forEach(boton => {
                boton.addEventListener('click', function() {
                    activarPestanaBoton(this.getAttribute('pest'));
                    mostrarPestana(this.getAttribute('pest'));
                });
            });

            const observador = new IntersectionObserver((entradas, observador) => {
                entradas.forEach(entrada => {
                    if (entrada.isIntersecting) {
                        if (entrada.intersectionRatio > 0) { // Item is at least partially visible
                            const itemVisible = entrada.target;
                            const itemRect = itemVisible.getBoundingClientRect();

                            // Check if the top of the item is at or above the top of the viewport
                            if (itemRect.top <= window.innerHeight && itemRect.bottom > 0) {
                                const pestanaNombre = itemVisible.id;
                                activarPestanaBoton(pestanaNombre);
                                // No need to change display here, tabs handle display, and scroll handles continuation
                            }
                        }
                    }
                });
            }, {
                threshold: 0, // Trigger even when a small part is visible
                rootMargin: '0px 0px -99% 0px' // Trigger when the top edge enters the viewport
            });

            itemsPortafolio.forEach(item => {
                observador.observe(item);
            });
        }
        window.reiniciarLazySvg = reiniciarLazySvg;

        document.addEventListener('DOMContentLoaded', iniciarLazySvg);
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
        <div class="OSFED" id="caracteristicas">
            <div class="ADEEDE">
                <div class="diva1 bloque svg-container">

                    <h3>BIO</h3>

                    <p>Highly skilled web developer proficient in complex algorithm development, efficient architecture design, and database management within Linux environments.</p>
                    <p>I possess a strong foundation in the entire software development lifecycle, from initial concept and project planning to final implementation and delivery.</p>
                    <p>My disciplined and meticulous approach allows me to ensure quality and efficiency at every stage.</p>
                    <p>I have a proven ability to create interactive and robust web applications, always seeking innovative and high-impact solutions that exceed expectations. I am seeking a challenging role where I can apply my knowledge and contribute significantly to the organization's success. </p>

                </div>
                <div class="diva2 bloque svg-container">
                    <h3>Favorite tools</h3>

                    <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/experiencietools.svg"></div>
                </div>

                <div class="diva3 bloque svg-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/0505.jpg" alt="asley wandorius">
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
    </div>

    <div class="BKXAFN">
        <h3>Proyectos</h3>
        <div class="botonesProyectos">
            <span pest="2upraPest">2upra</span>
            <span pest="gallePest">Galle</span>
        </div>
        <div class="OSFED tabProjects">
            <div class="ADEEDE itemPortafolio" id="2upraPest">

                <div class="divb1 bloque svg-container">
                    <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/inicio2upra.svg"></div>
                </div>

                <div class="divb2 bloque svg-container">

                    <?
                    $svg_file_path = get_template_directory() . '/assets/svgs/phone/phone_1.svg';
                    $svg_content = file_get_contents($svg_file_path);
                    $image_base_url = get_template_directory_uri() . '/assets/svgs/phone/';

                    for ($i = 11; $i <= 125; $i++) {
                        $image_filename = 'phone_' . $i . '.png';
                        $original_string = 'xlink:href="' . $image_filename . '"';
                        $replacement_string = 'xlink:href="' . $image_base_url . $image_filename . '"';
                        $svg_content = str_replace($original_string, $replacement_string, $svg_content);
                    }

                    ?>
                    <div class="lazy-svg"><?php echo $svg_content; ?></div>

                </div>

                <div class="divb3 bloque svg-container">
                    <?
                    $svg_file_path1 = get_template_directory() . '/assets/svgs/main/main.svg';
                    $svg_content1 = file_get_contents($svg_file_path1);
                    $image_base_url1 = get_template_directory_uri() . '/assets/svgs/main/';

                    for ($i = 1; $i <= 17; $i++) {
                        $image_filename1 = 'main' . $i . '.png';
                        $original_string1 = 'xlink:href="' . $image_filename1 . '"';
                        $replacement_string1 = 'xlink:href="' . $image_base_url1 . $image_filename1 . '"';
                        $svg_content1 = str_replace($original_string1, $replacement_string1, $svg_content1);
                    }

                    ?>
                    <div class="lazy-svg"><?php echo $svg_content1; ?></div>
                </div>

                <div class="divb4 bloque svg-container">
                    <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/2upraapp.svg"></div>
                </div>

                <div class="divb5 bloque svg-container" style="padding: 0;">
                    <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/main1.svg"></div>
                </div>

                <div class="divb6 bloque svg-container">
                    <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logo2upra.svg"></div>
                </div>

                <div class="infoProyecto1">
                    <h3>2upra</h3>
                    <p>
                        2upra is a social network focused on music production, featuring a sample catalog with intelligent algorithms, a collection system, and a user system. It also offers chat and social interaction features. The goal is to surpass Splice in functionality and features.
                    </p>
                </div>
            </div>

            <!-- Galle -->

            <div class="ADEEDE itemPortafolio" id="gallePest">

                <div class="divc1 bloque svg-container ">
                    <div class="lazy-svg h400h" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/chat.svg"></div>
                </div>
                <div class="divc2 bloque svg-container">
                    <div class="lazy-svg h400h" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/chat614.svg"></div>
                </div>

                <div class="divc3 bloque svg-container">
                    <div class="lazy-svg h400h" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/chat437.svg"></div>
                </div>

                <div class="divc4 bloque svg-container">
                    <div class="lazy-svg h400h" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/chat621.svg"></div>
                </div>

                <div class="divc5 bloque svg-container">
                    <div class="lazy-svg h200h" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logoGalle.svg"></div>
                </div>
                <div class="infoProyecto1">
                    <h3>2upra</h3>
                    <p>
                        2upra is a social network focused on music production, featuring a sample catalog with intelligent algorithms, a collection system, and a user system. It also offers chat and social interaction features. The goal is to surpass Splice in functionality and features.
                    </p>
                </div>

            </div>

            <style>
                .divb1 {
                    grid-area: 1 / 1 / 2 / 3;
                }

                .divb2 {
                    grid-area: 1 / 3 / 2 / 4;
                }

                .divb3 {
                    grid-area: 2 / 2 / 3 / 4;
                }

                .divb4 {
                    grid-area: 2 / 1 / 3 / 2;
                }

                .divb5 {
                    grid-area: 3 / 1 / 4 / 3;
                }

                .divb6 {
                    grid-area: 3 / 3 / 4 / 4;
                }

                .infoProyecto1 {
                    grid-area: 4 / 1 / 5 / 4;
                }

                .divc1 {
                    grid-area: 1 / 1 / 2 / 2;
                }

                .divc2 {
                    grid-area: 1 / 2 / 2 / 3;
                }

                .divc3 {
                    grid-area: 1 / 3 / 2 / 4;
                }

                .divc4 {
                    grid-area: 2 / 1 / 3 / 3;
                }

                .divc5 {
                    grid-area: 2 / 3 / 3 / 4;
                }
            </style>
        </div>




    <?
    return ob_get_clean();
}
