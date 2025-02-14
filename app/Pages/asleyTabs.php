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
            agregarBotonExpandir();
            efctAparSuaveBio();
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

        //solo un pequeño detalle, esto funciona muy bien, pero cuando regreso al anterior, el div flotante con los botones no vuelve aparecer
        function iniciarPestanasPf() {
            const btnsPest = document.querySelectorAll('.botonesProyectos span[pest]');
            const itemsPf = document.querySelectorAll('.itemPortafolio');
            let pestAct = null;

            function mostrarPest(pestNom) {
                itemsPf.forEach(item => item.style.display = 'none');
                const itemAct = document.getElementById(pestNom);
                if (itemAct) {
                    itemAct.style.display = 'grid';
                    pestAct = itemAct;
                }
            }

            function activarBtnPest(pestNom) {
                btnsPest.forEach(b => b.classList.remove('pestActivo'));
                const btnAct = Array.from(btnsPest).find(b => b.getAttribute('pest') === pestNom);
                if (btnAct) btnAct.classList.add('pestActivo');
            }

            if (btnsPest.length > 0) {
                btnsPest[0].classList.add('pestActivo');
                mostrarPest(btnsPest[0].getAttribute('pest'));
            }

            btnsPest.forEach(btn => {
                btn.addEventListener('click', function() {
                    const pest = this.getAttribute('pest');
                    activarBtnPest(pest);
                    mostrarPest(pest);
                    const itemAct = document.getElementById(pest);
                    actualizarNavFlot(itemAct);
                    navFlot.style.display = 'flex';
                });
            });

            const obs = new IntersectionObserver((entradas) => {
                entradas.forEach(entrada => {
                    if (entrada.isIntersecting && entrada.intersectionRatio > 0) {
                        const itemVis = entrada.target;
                        const itemRect = itemVis.getBoundingClientRect();
                        if (itemRect.top <= window.innerHeight && itemRect.bottom > 0) {
                            const pestNom = itemVis.id;
                            activarBtnPest(pestNom);
                            actualizarNavFlot(itemVis);
                            navFlot.style.display = 'flex';
                        } else {
                            navFlot.style.display = 'none';
                        }
                    } else {
                        navFlot.style.display = 'none';
                    }
                });
            }, {
                threshold: 0,
                rootMargin: '0px 0px -99% 0px'
            });

            itemsPf.forEach(item => obs.observe(item));

            const navFlot = document.createElement('div');
            navFlot.className = 'floatingNav';
            navFlot.style.position = 'fixed';
            navFlot.style.bottom = '20px';
            navFlot.style.left = '50%';
            navFlot.style.transform = 'translateX(-50%)';
            navFlot.style.background = 'rgba(0,0,0,0.7)';
            navFlot.style.color = '#fff';
            navFlot.style.padding = '5px 20px';
            navFlot.style.borderRadius = '100px';
            navFlot.style.display = 'none';
            navFlot.style.zIndex = '1000';
            navFlot.style.alignItems = 'center';
            navFlot.style.gap = '10px';

            const btnAnt = document.createElement('button');
            btnAnt.className = 'prevBtn';
            btnAnt.style.cursor = 'pointer';

            const lblAct = document.createElement('span');
            lblAct.className = 'currentProject';

            const btnSig = document.createElement('button');
            btnSig.className = 'nextBtn';
            btnSig.style.cursor = 'pointer';

            navFlot.appendChild(btnAnt);
            navFlot.appendChild(lblAct);
            navFlot.appendChild(btnSig);
            document.body.appendChild(navFlot);

            function actualizarNavFlot(itemAct) {
                const itemsArr = Array.from(itemsPf);
                const indexAct = itemsArr.indexOf(itemAct);

                const btnAct = Array.from(btnsPest).find(b => b.getAttribute('pest') === itemAct.id);
                const nomAct = btnAct ? btnAct.textContent : itemAct.id;
                lblAct.textContent = nomAct;

                btnAnt.style.display = 'none'; // Ocultar por defecto
                if (indexAct > 0) {
                    const itemAnt = itemsArr[indexAct - 1];
                    const btnAntItem = Array.from(btnsPest).find(b => b.getAttribute('pest') === itemAnt.id);
                    if (btnAntItem) { // Verificar si existe el botón anterior
                        const nomAnt = btnAntItem.textContent;
                        btnAnt.textContent = nomAnt;
                        btnAnt.style.display = 'inline-block'; // Mostrar solo si hay un proyecto anterior
                        btnAnt.onclick = function() {
                            activarBtnPest(itemAnt.id);
                            mostrarPest(itemAnt.id);
                            itemAnt.scrollIntoView({
                                behavior: 'smooth'
                            });
                        };
                    }
                }

                btnSig.style.display = 'none'; // Ocultar por defecto
                if (indexAct < itemsArr.length - 1) {
                    const itemSig = itemsArr[indexAct + 1];
                    const btnSigItem = Array.from(btnsPest).find(b => b.getAttribute('pest') === itemSig.id);
                    if (btnSigItem) { // Verificar si existe el botón siguiente
                        const nomSig = btnSigItem.textContent;
                        btnSig.textContent = nomSig;
                        btnSig.style.display = 'inline-block'; // Mostrar solo si hay un proyecto siguiente
                        btnSig.onclick = function() {
                            activarBtnPest(itemSig.id);
                            mostrarPest(itemSig.id);
                            itemSig.scrollIntoView({
                                behavior: 'smooth'
                            });
                        };
                    }
                }
            }
        }
        window.reiniciarLazySvg = reiniciarLazySvg;

        document.addEventListener('DOMContentLoaded', iniciarLazySvg);

        function agregarBotonExpandir() {
            let contenedores = document.querySelectorAll('.svg-container');
            contenedores.forEach(contenedor => {
                if (!contenedor.classList.contains('noExpandir')) {
                    let boton = document.createElement('button');
                    boton.innerHTML = `
            <svg width="100%" height="100%" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M14 10L21 3M21 3H15M21 3V9M10 14L3 21M3 21H9M3 21L3 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        `;
                    boton.classList.add('boton-expandir-svg');
                    contenedor.appendChild(boton);

                    contenedor.addEventListener('mouseenter', () => {
                        boton.style.opacity = '1';
                    });

                    contenedor.addEventListener('mouseleave', () => {
                        boton.style.opacity = '0';
                    });

                    boton.addEventListener('click', (evento) => {
                        evento.stopPropagation();
                        contenedor.classList.toggle('svg-expandido');
                        if (contenedor.classList.contains('svg-expandido')) {
                            document.body.classList.add('svg-expandido-activo');
                        } else {
                            document.body.classList.remove('svg-expandido-activo');
                        }
                    });
                }
            });

            document.addEventListener('click', function(evento) {
                if (document.body.classList.contains('svg-expandido-activo')) {
                    let contenedorExpandido = document.querySelector('.svg-container.svg-expandido');
                    if (contenedorExpandido) {
                        const svgLazy = contenedorExpandido.querySelector('.lazy-svg');
                        let svgInterno = null;
                        if (svgLazy) {
                            svgInterno = svgLazy.querySelector('svg');
                        }
                        if (svgInterno && svgInterno.contains(evento.target)) {
                            return;
                        }
                        contenedorExpandido.classList.remove('svg-expandido');
                        document.body.classList.remove('svg-expandido-activo');
                    }
                }
            });

            document.addEventListener('keydown', function(evento) {
                if (evento.key === 'Escape' || evento.key === 'Esc') {
                    if (document.body.classList.contains('svg-expandido-activo')) {
                        let contenedorExpandido = document.querySelector('.svg-container.svg-expandido');
                        if (contenedorExpandido) {
                            contenedorExpandido.classList.remove('svg-expandido');
                            document.body.classList.remove('svg-expandido-activo');
                        }
                    }
                }
            });
        }

        function efctAparSuaveBio() {
            let elm = document.getElementById("textoBio");
            let h3 = elm.querySelector("h3");
            let txts = elm.querySelectorAll("p");
            let vlc = 15; // Velocidad de la transición (ajusta según prefieras)
            let pActual = 0;
            let elmP;

            elm.innerHTML = "";
            if (h3) {
                elm.appendChild(h3);
            }

            function mostrarParrafo() {
                if (pActual < txts.length) {
                    elmP = document.createElement("p");
                    elm.appendChild(elmP);
                    let palabras = txts[pActual].textContent.split(" ");
                    let indxPalabra = 0;

                    function mostrarPalabra() {
                        if (indxPalabra < palabras.length) {
                            let span = document.createElement("span");
                            span.textContent = palabras[indxPalabra] + " ";
                            span.style.opacity = 0;
                            span.style.transition = `opacity ${vlc/50}s ease-in-out`;
                            elmP.appendChild(span);

                            // Forzamos un reflow para que el navegador registre el estado inicial.
                            span.offsetHeight;

                            requestAnimationFrame(() => {
                                span.style.opacity = 1;
                            });

                            indxPalabra++;
                            setTimeout(mostrarPalabra, vlc * 3);
                        } else {
                            pActual++;
                            setTimeout(mostrarParrafo, vlc * 5);
                        }
                    }


                    mostrarPalabra();
                }
            }

            mostrarParrafo();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const logosContainer = document.querySelector('.logosSvg')
            Sortable.create(logosContainer, {
                animation: 150, // Animación suave en milisegundos
                ghostClass: 'sortable-ghost', // Clase para el elemento fantasma
            })
        })
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <style>
        /* Aplica opacidad al elemento mientras se arrastra */
        .sortable-ghost {
            opacity: 0.4;
        }
    </style>

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
                <div class="diva1 bloque svg-container noExpandir" id="textoBio">

                    <h3>BIO</h3>
                    <p>Highly skilled web developer proficient in complex algorithm development, efficient architecture design, and database management within Linux environments.</p>
                    <p>I possess a strong foundation in the entire software development lifecycle, from initial concept and project planning to final implementation and delivery.</p>
                    <p>My disciplined and meticulous approach allows me to ensure quality and efficiency at every stage.</p>
                    <p>I have a proven ability to create interactive and robust web applications, always seeking innovative and high-impact solutions that exceed expectations. I am seeking a challenging role where I can apply my knowledge and contribute significantly to the organization's success. </p>

                </div>
                <div class="diva2 bloque svg-container noExpandir">
                    <h3>Favorite tools</h3>

                    <div class="logosSvg">
                        <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logos/1.svg"></div>
                        <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logos/2.svg"></div>
                        <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logos/3.svg"></div>
                        <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logos/4.svg"></div>
                        <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logos/5.svg"></div>
                        <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logos/6.svg"></div>
                        <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logos/7.svg"></div>
                        <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logos/8.svg"></div>
                        <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logos/9a.svg"></div>
                        <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logos/10.svg"></div>
                        <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logos/11.svg"></div>
                        <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logos/12.svg"></div>
                        <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logos/13.svg"></div>
                    </div>
                </div>

                <div class="diva3 bloque svg-container noExpandir">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/0505.jpg" alt="asley wandorius">
                </div>

                <div class="diva4 bloque svg-container noExpandir">
                    <h3>ABOUT</h3>
                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 90px; opacity: 0.6;">Name</h3>
                        <p>Asley Navarro</p>
                    </div>

                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 90px; opacity: 0.6;">Date of Birth</h3>
                        <p>November 17, 1999</p>
                    </div>

                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 90px; opacity: 0.6;">Country</h3>
                        <p>Venezuela</p>
                    </div>

                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 90px; opacity: 0.6;">City</h3>
                        <p>Puerto Ordaz</p>
                    </div>

                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 90px; opacity: 0.6;">Languages</h3>
                        <p>Spanish</p>
                        <p>English</p>
                    </div>

                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 90px; opacity: 0.6;">Degree</h3>
                        <p>Graphic Designer</p>
                    </div>

                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 90px; opacity: 0.6;">Interests</h3>
                        <p>Music, Algorithms, Books</p>
                    </div>

                </div>

                <div class="diva5 bloque svg-container noExpandir">

                    <h3>EXPERIENCIE</h3>
                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 120px; opacity: 0.6; line-height: 24px; margin-bottom: auto;">2017</h3>
                        <div class="detailsAsley">
                            <p>Century 21</p>
                            <p style="font-size: 10px; margin-top: 2px; opacity: 0.8;">Created visual marketing materials, including brochures and advertisements, for real estate promotions.</p>
                        </div>
                    </div>

                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 120px; opacity: 0.6; line-height: 24px; margin-bottom: auto;">2018 - 2019</h3>
                        <div class="detailsAsley">
                            <p>MN Real Estate</p>
                            <p style="font-size: 10px; margin-top: 2px; opacity: 0.8;">Developed a wide range of marketing materials to enhance brand visibility, including print and digital media.</p>
                        </div>
                    </div>

                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 120px; opacity: 0.6; line-height: 24px; margin-bottom: auto;">2019</h3>
                        <div class="detailsAsley">
                            <p>Premed</p>
                            <p style="font-size: 10px; margin-top: 2px; opacity: 0.8;">Executed marketing strategies and designed content for both digital and print platforms, ensuring user-friendly websites.</p>
                        </div>
                    </div>

                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 120px; opacity: 0.6; line-height: 24px; margin-bottom: auto;">2020 - 2023</h3>
                        <div class="detailsAsley">
                            <p>Bounce Creative</p>
                            <p style="font-size: 10px; margin-top: 2px; opacity: 0.8;">Founded and managed a web design agency, providing web development, design, and client management services.</p>
                        </div>
                    </div>

                </div>

                <div class="diva6 bloque svg-container noExpandir">
                    <h3>SKILLS</h3>

                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 180px; opacity: 0.6;">Programming</h3>
                        <div class="detailsAsley">
                            <span>JavaScript</span>
                            <span>PHP</span>
                            <span>Python</span>
                            <span>SQL</span>
                            <span>Rust</span>
                        </div>
                    </div>

                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 180px; opacity: 0.6;">Frameworks</h3>
                        <div class="detailsAsley">
                            <span>React</span>
                            <span>Laravel</span>
                            <span>Node.js</span>
                            <span>Next.js</span>
                        </div>
                    </div>

                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 180px; opacity: 0.6;">Design</h3>
                        <div class="detailsAsley">
                            <span>UI/UX Design</span>
                            <span>Graphic Design</span>
                            <span>Visual Identity</span>
                            <span>Logo Design</span>
                            <span>Stationery Design</span>
                        </div>
                    </div>

                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 180px; opacity: 0.6;">Databases</h3>
                        <div class="detailsAsley">
                            <span>MySQL</span>
                            <span>SQLite</span>
                            <span>PostgreSQL</span>
                        </div>
                    </div>

                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 180px; opacity: 0.6;">API & Cloud</h3>
                        <div class="detailsAsley">
                            <span>API Management</span>
                            <span>OpenAI</span>
                            <span>Claude</span>
                            <span>Google Gemini</span>
                            <span>AWS</span>
                            <span>Microsoft Azure</span>
                        </div>
                    </div>
                    <div class="infoAsley">
                        <h3 style="font-size: 11px; width: 180px; opacity: 0.6;">Other</h3>
                        <div class="detailsAsley">
                            <span>Linux Server Admin</span>
                            <span>Problem Solving</span>
                            <span>Project Management</span>
                            <span>Client Acquisition</span>
                        </div>
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

            .diva4 {
                grid-area: 2 / 1 / 2 / 2;
            }

            .diva5 {
                grid-area: 2 / 2 / 2 / 3;
            }

            .diva6 {
                grid-area: 2 / 3 / 2 / 4;
            }
        </style>
    </div>

    <div class="BKXAFN">
        <h3>Proyectos</h3>
        <div class="botonesProyectos">
            <span pest="2upraPest">2upra</span>
            <span pest="gallePest">Galle</span>
            <span pest="proyecto3">proyecto3</span>
            <span pest="proyecto4">proyecto4</span>
        </div>
        <div class="OSFED tabProjects">
            <div class="ADEEDE itemPortafolio" id="2upraPest">

                <div class="divb1 bloque svg-container">
                    <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/inicio2upra.svg"></div>
                </div>

                <div class="divb2 bloque svg-container" style="padding: 20px;">

                    <?
                    $svg_file_path = get_template_directory() . '/assets/svgs/phone/phonew.svg';
                    $svg_content = file_get_contents($svg_file_path);
                    $image_base_url = get_template_directory_uri() . '/assets/svgs/phone/';

                    for ($i = 1; $i <= 23; $i++) {
                        $image_filename = 'phonew' . $i . '.png';
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

                <div class="divb4 bloque svg-container noExpandir">
                    <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/2upraapp.svg"></div>
                </div>

                <div class="divb5 bloque svg-container" style="padding: 0;">
                    <div class="lazy-svg" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/main1.svg"></div>
                </div>

                <div class="divb6 bloque svg-container noExpandir">
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
                    <!-- mirad, hay muchos lazy-svg dentro de svg-container, lo que vamos a hacer que cuando el usuario ponga el cursor sobre un svg-container, aparezca un boton, cuyo boton lo que haremos es que al hacer click, el svg-container se expanda en toda la pantalla, para que el usuario pueda ver todo el svg mas grande, el boton tendra este icono, agrega una clase para yo ponerle diseño
                     
                    <svg width="100%" height="100%" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14 10L21 3M21 3H15M21 3V9M10 14L3 21M3 21H9M3 21L3 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    
                    
                    -->
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

                <div class="divc5 bloque svg-container noExpandir">
                    <div class="lazy-svg h200h" data-src="<?php echo get_template_directory_uri(); ?>/assets/svgs/logoGalle.svg"></div>
                </div>
                <div class="infoProyecto1">
                    <h3>Galle</h3>
                    <p>
                        A lightweight and secure real-time messaging application, engineered for seamless integration with the 2upra music production platform. Designed with a minimalist interface for ease of use, Galle prioritizes utility for music producers. Facilitating private and encrypted communication, it enables instant collaboration, direct sharing of musical content and posts, and efficient service requests within the 2upra ecosystem, all while maintaining a focused and uncluttered user experience.
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
