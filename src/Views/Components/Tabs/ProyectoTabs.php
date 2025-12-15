<?php

/**
 * Tabs especiales del proyecto (portafolio, sello, inversores).
 * 
 * Contiene componentes para páginas específicas que dependen de funciones
 * financieras legacy (calc_ing, botonSponsor, etc.) que aún no han sido migradas.
 *
 * @package Kamples\Views\Components\Tabs
 * @since 1.0.0
 */

namespace Kamples\Views\Components\Tabs;

class ProyectoTabs
{
    /**
     * Renderiza el panel de rolas enviadas/eliminadas/rechazadas.
     * Versión simplificada ya que la funcionalidad completa está comentada.
     *
     * @return string HTML del panel.
     */
    public static function renderPanel(): string
    {
        ob_start();
?>
        <div class="FLXVTQ">
            <a href="<?php echo home_url('/'); ?>">
                <p>Aquí podrás ver tus rolas enviadas a las plataformas de stream, pero aún estamos trabajando en esta funcionalidad.</p>
                <button class="borde">Volver</button>
            </a>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el tab del portafolio de Asley.
     *
     * @return string HTML del portafolio.
     */
    public static function renderAsleyTab(): string
    {
        ob_start();
    ?>
        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="Proyecto"></div>
        </div>

        <div class="tabs">
            <div class="tab-content">
                <div class="tab S4K7I3 asleyPorf" id="Proyecto">
                    <?php echo self::renderPortafolio(); ?>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el portafolio completo con bio, skills, experiencia y proyectos.
     *
     * @return string HTML del portafolio.
     */
    public static function renderPortafolio(): string
    {
        $templateDir = get_template_directory_uri();
        $templatePath = get_template_directory();

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
                                            if (!response.ok) throw new Error('Error al cargar el SVG');
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
                        rootMargin: '100px 0px'
                    });

                    const lazySvgs = document.querySelectorAll('.lazy-svg');
                    lazySvgs.forEach(div => observer.observe(div));
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
                                if (!response.ok) throw new Error('Error al cargar el SVG');
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
                navFlot.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.7);color:#fff;padding:5px 20px;border-radius:100px;display:none;z-index:1000;align-items:center;gap:10px;';

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

                    const btnActivo = Array.from(btnsPest).find(b => b.getAttribute('pest') === itemAct.id);
                    const nomAct = btnActivo ? btnActivo.textContent : itemAct.id;
                    lblAct.textContent = nomAct;

                    btnAnt.style.display = 'none';
                    if (indexAct > 0) {
                        const itemAnt = itemsArr[indexAct - 1];
                        const btnAntItem = Array.from(btnsPest).find(b => b.getAttribute('pest') === itemAnt.id);
                        if (btnAntItem) {
                            btnAnt.textContent = btnAntItem.textContent;
                            btnAnt.style.display = 'inline-block';
                            btnAnt.onclick = function() {
                                activarBtnPest(itemAnt.id);
                                mostrarPest(itemAnt.id);
                                itemAnt.scrollIntoView({
                                    behavior: 'smooth'
                                });
                            };
                        }
                    }

                    btnSig.style.display = 'none';
                    if (indexAct < itemsArr.length - 1) {
                        const itemSig = itemsArr[indexAct + 1];
                        const btnSigItem = Array.from(btnsPest).find(b => b.getAttribute('pest') === itemSig.id);
                        if (btnSigItem) {
                            btnSig.textContent = btnSigItem.textContent;
                            btnSig.style.display = 'inline-block';
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
                        boton.innerHTML = '<svg width="100%" height="100%" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 10L21 3M21 3H15M21 3V9M10 14L3 21M3 21H9M3 21L3 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
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
                            document.body.classList.toggle('svg-expandido-activo', contenedor.classList.contains('svg-expandido'));
                        });
                    }
                });

                document.addEventListener('click', function(evento) {
                    if (document.body.classList.contains('svg-expandido-activo')) {
                        let contenedorExpandido = document.querySelector('.svg-container.svg-expandido');
                        if (contenedorExpandido) {
                            const svgLazy = contenedorExpandido.querySelector('.lazy-svg');
                            let svgInterno = svgLazy ? svgLazy.querySelector('svg') : null;
                            if (svgInterno && svgInterno.contains(evento.target)) return;
                            contenedorExpandido.classList.remove('svg-expandido');
                            document.body.classList.remove('svg-expandido-activo');
                        }
                    }
                });

                document.addEventListener('keydown', function(evento) {
                    if ((evento.key === 'Escape' || evento.key === 'Esc') && document.body.classList.contains('svg-expandido-activo')) {
                        let contenedorExpandido = document.querySelector('.svg-container.svg-expandido');
                        if (contenedorExpandido) {
                            contenedorExpandido.classList.remove('svg-expandido');
                            document.body.classList.remove('svg-expandido-activo');
                        }
                    }
                });
            }

            function efctAparSuaveBio() {
                let elm = document.getElementById("textoBio");
                if (!elm) return;
                let h3 = elm.querySelector("h3");
                let txts = elm.querySelectorAll("p");
                let vlc = 15;
                let pActual = 0;
                let elmP;

                elm.innerHTML = "";
                if (h3) elm.appendChild(h3);

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
                const logosContainer = document.querySelector('.logosSvg');
                if (logosContainer && typeof Sortable !== 'undefined') {
                    Sortable.create(logosContainer, {
                        animation: 150,
                        ghostClass: 'sortable-ghost'
                    });
                }
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
        <style>
            .sortable-ghost {
                opacity: 0.4;
            }
        </style>

        <?php echo self::renderBioSection($templateDir); ?>
        <?php echo self::renderProyectosSection($templateDir, $templatePath); ?>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza la sección de biografía y skills.
     */
    private static function renderBioSection(string $templateDir): string
    {
        ob_start();
    ?>
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
                        <p>I have a proven ability to create interactive and robust web applications, always seeking innovative and high-impact solutions that exceed expectations. I am seeking a challenging role where I can apply my knowledge and contribute significantly to the organization's success.</p>
                    </div>

                    <div class="diva2 bloque svg-container noExpandir">
                        <h3>Favorite tools</h3>
                        <div class="logosSvg">
                            <?php for ($i = 1; $i <= 13; $i++): ?>
                                <?php $filename = $i === 9 ? '9a' : $i; ?>
                                <div class="lazy-svg" data-src="<?php echo esc_url($templateDir . '/assets/svgs/logos/' . $filename . '.svg'); ?>"></div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="diva3 bloque svg-container noExpandir">
                        <img src="<?php echo esc_url($templateDir . '/assets/img/0505.jpg'); ?>" alt="asley wandorius">
                    </div>

                    <div class="diva4 bloque svg-container noExpandir">
                        <h3>ABOUT</h3>
                        <?php
                        $aboutData = [
                            ['label' => 'Name', 'value' => 'Asley Navarro'],
                            ['label' => 'Date of Birth', 'value' => 'November 17, 1999'],
                            ['label' => 'Country', 'value' => 'Venezuela'],
                            ['label' => 'City', 'value' => 'Puerto Ordaz'],
                            ['label' => 'Languages', 'value' => ['Spanish', 'English']],
                            ['label' => 'Degree', 'value' => 'Graphic Designer'],
                            ['label' => 'Interests', 'value' => 'Music, Algorithms, Books'],
                        ];
                        foreach ($aboutData as $item): ?>
                            <div class="infoAsley">
                                <h3 style="font-size: 11px; width: 90px; opacity: 0.6;"><?php echo esc_html($item['label']); ?></h3>
                                <?php if (is_array($item['value'])): ?>
                                    <?php foreach ($item['value'] as $val): ?>
                                        <p><?php echo esc_html($val); ?></p>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p><?php echo esc_html($item['value']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="diva5 bloque svg-container noExpandir">
                        <h3>EXPERIENCE</h3>
                        <?php
                        $experienceData = [
                            ['year' => '2017', 'company' => 'Century 21', 'desc' => 'Created visual marketing materials, including brochures and advertisements, for real estate promotions.'],
                            ['year' => '2018 - 2019', 'company' => 'MN Real Estate', 'desc' => 'Developed a wide range of marketing materials to enhance brand visibility, including print and digital media.'],
                            ['year' => '2019', 'company' => 'Premed', 'desc' => 'Executed marketing strategies and designed content for both digital and print platforms, ensuring user-friendly websites.'],
                            ['year' => '2020 - 2023', 'company' => 'Bounce Creative', 'desc' => 'Founded and managed a web design agency, providing web development, design, and client management services.'],
                        ];
                        foreach ($experienceData as $exp): ?>
                            <div class="infoAsley">
                                <h3 style="font-size: 11px; width: 120px; opacity: 0.6; line-height: 24px; margin-bottom: auto;"><?php echo esc_html($exp['year']); ?></h3>
                                <div class="detailsAsley">
                                    <p><?php echo esc_html($exp['company']); ?></p>
                                    <p style="font-size: 10px; margin-top: 2px; opacity: 0.8;"><?php echo esc_html($exp['desc']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="diva6 bloque svg-container noExpandir">
                        <h3>SKILLS</h3>
                        <?php
                        $skillsData = [
                            ['cat' => 'Programming', 'skills' => ['JavaScript', 'PHP', 'Python', 'SQL', 'Rust']],
                            ['cat' => 'Frameworks', 'skills' => ['React', 'Laravel', 'Node.js', 'Next.js']],
                            ['cat' => 'Design', 'skills' => ['UI/UX Design', 'Graphic Design', 'Visual Identity', 'Logo Design', 'Stationery Design']],
                            ['cat' => 'Databases', 'skills' => ['MySQL', 'SQLite', 'PostgreSQL']],
                            ['cat' => 'API & Cloud', 'skills' => ['API Management', 'OpenAI', 'Claude', 'Google Gemini', 'AWS', 'Microsoft Azure']],
                            ['cat' => 'Other', 'skills' => ['Linux Server Admin', 'Problem Solving', 'Project Management', 'Client Acquisition']],
                        ];
                        foreach ($skillsData as $cat): ?>
                            <div class="infoAsley">
                                <h3 style="font-size: 11px; width: 180px; opacity: 0.6;"><?php echo esc_html($cat['cat']); ?></h3>
                                <div class="detailsAsley">
                                    <?php foreach ($cat['skills'] as $skill): ?>
                                        <span><?php echo esc_html($skill); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza la sección de proyectos (2upra, Galle, etc).
     */
    private static function renderProyectosSection(string $templateDir, string $templatePath): string
    {
        ob_start();
    ?>
        <div class="BKXAFN">
            <h3>Proyectos</h3>
            <div class="botonesProyectos">
                <span pest="2upraPest">2upra</span>
                <span pest="gallePest">Galle</span>
                <span pest="proyecto3">proyecto3</span>
                <span pest="proyecto4">proyecto4</span>
            </div>
            <div class="OSFED tabProjects">
                <!-- 2upra -->
                <div class="ADEEDE itemPortafolio" id="2upraPest">
                    <div class="divb1 bloque svg-container">
                        <div class="lazy-svg" data-src="<?php echo esc_url($templateDir . '/assets/svgs/inicio2upra.svg'); ?>"></div>
                    </div>

                    <div class="divb2 bloque svg-container" style="padding: 20px;">
                        <?php
                        $svgFilePath = $templatePath . '/assets/svgs/phone/phonew.svg';
                        if (file_exists($svgFilePath)) {
                            $svgContent = file_get_contents($svgFilePath);
                            $imageBaseUrl = $templateDir . '/assets/svgs/phone/';
                            for ($i = 1; $i <= 23; $i++) {
                                $imageFilename = 'phonew' . $i . '.png';
                                $svgContent = str_replace(
                                    'xlink:href="' . $imageFilename . '"',
                                    'xlink:href="' . $imageBaseUrl . $imageFilename . '"',
                                    $svgContent
                                );
                            }
                            echo '<div class="lazy-svg">' . $svgContent . '</div>';
                        }
                        ?>
                    </div>

                    <div class="divb3 bloque svg-container">
                        <?php
                        $svgFilePath1 = $templatePath . '/assets/svgs/main/main.svg';
                        if (file_exists($svgFilePath1)) {
                            $svgContent1 = file_get_contents($svgFilePath1);
                            $imageBaseUrl1 = $templateDir . '/assets/svgs/main/';
                            for ($i = 1; $i <= 17; $i++) {
                                $imageFilename1 = 'main' . $i . '.png';
                                $svgContent1 = str_replace(
                                    'xlink:href="' . $imageFilename1 . '"',
                                    'xlink:href="' . $imageBaseUrl1 . $imageFilename1 . '"',
                                    $svgContent1
                                );
                            }
                            echo '<div class="lazy-svg">' . $svgContent1 . '</div>';
                        }
                        ?>
                    </div>

                    <div class="divb4 bloque svg-container noExpandir">
                        <div class="lazy-svg" data-src="<?php echo esc_url($templateDir . '/assets/svgs/2upraapp.svg'); ?>"></div>
                    </div>

                    <div class="divb5 bloque svg-container" style="padding: 0;">
                        <div class="lazy-svg" data-src="<?php echo esc_url($templateDir . '/assets/svgs/main1.svg'); ?>"></div>
                    </div>

                    <div class="divb6 bloque svg-container noExpandir">
                        <div class="lazy-svg" data-src="<?php echo esc_url($templateDir . '/assets/svgs/logo2upra.svg'); ?>"></div>
                    </div>

                    <div class="infoProyecto1">
                        <h3>2upra</h3>
                        <p>2upra is a social network focused on music production, featuring a sample catalog with intelligent algorithms, a collection system, and a user system. It also offers chat and social interaction features. The goal is to surpass Splice in functionality and features.</p>
                    </div>
                </div>

                <!-- Galle -->
                <div class="ADEEDE itemPortafolio" id="gallePest">
                    <div class="divc1 bloque svg-container">
                        <div class="lazy-svg h400h" data-src="<?php echo esc_url($templateDir . '/assets/svgs/chat.svg'); ?>"></div>
                    </div>
                    <div class="divc2 bloque svg-container">
                        <div class="lazy-svg h400h" data-src="<?php echo esc_url($templateDir . '/assets/svgs/chat614.svg'); ?>"></div>
                    </div>
                    <div class="divc3 bloque svg-container">
                        <div class="lazy-svg h400h" data-src="<?php echo esc_url($templateDir . '/assets/svgs/chat437.svg'); ?>"></div>
                    </div>
                    <div class="divc4 bloque svg-container">
                        <div class="lazy-svg h400h" data-src="<?php echo esc_url($templateDir . '/assets/svgs/chat621.svg'); ?>"></div>
                    </div>
                    <div class="divc5 bloque svg-container noExpandir">
                        <div class="lazy-svg h200h" data-src="<?php echo esc_url($templateDir . '/assets/svgs/logoGalle.svg'); ?>"></div>
                    </div>
                    <div class="infoProyecto1">
                        <h3>Galle</h3>
                        <p>A lightweight and secure real-time messaging application, engineered for seamless integration with the 2upra music production platform. Designed with a minimalist interface for ease of use, Galle prioritizes utility for music producers.</p>
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
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza la página de inversores/patrocinadores.
     * NOTA: Esta función depende de funciones de app/Finanza/ que aún no están migradas.
     *
     * @return string HTML de la página.
     */
    public static function renderInversorSector(): string
    {
        $currentUser = wp_get_current_user();
        $userName = $currentUser->display_name;
        $userId = get_current_user_id();
        $acciones = get_user_meta($userId, 'acciones', true);

        /* 
         * Funciones de Finanza (legacy) - Se usan si están disponibles
         * calc_ing(), botonSponsor(), botonComprarAcciones(), graficoHistorialAcciones(),
         * capitalValores(), bolsavalores(), calcularAccionPorUsuario(), formCompraAcciones(),
         * modalComprarAcciones()
         */
        $resultados = function_exists('calc_ing') ? calc_ing() : ['valEmp' => 0, 'valAcc' => 0];
        $valEmp = "$" . number_format((float) ($resultados['valEmp'] ?? 0), 2, '.', '.');
        $valAcc = "$" . number_format((float) ($resultados['valAcc'] ?? 0), 2, '.', '.');
        $acc = (float) $acciones;
        $valD = $acc * (float) ($resultados['valAcc'] ?? 0);

        ob_start();
    ?>
        <div class="UIKMYM">
            <?php if (is_user_logged_in()): ?>
                <div class="WZEFLA">
                    <p>Hola <?php echo esc_html($userName); ?>!</p>
                </div>
                <div class="OIEODG">
                    <p>Ayudanos a seguir construyendo herramientas libres y accesibles para artistas y productores musicales! Juntos, podemos potenciar la creatividad y hacer que la musica llegue mas lejos. Este proyecto tiene un futuro brillante, y tu puedes ser parte de el!</p>
                </div>
            <?php endif; ?>

            <div class="JUJRQG">
                <a href="https://github.com/1ndoryu" class="no-ajax">
                    <button class="DZYBQD" id="github-button">
                        <?php echo isset($GLOBALS['Github']) ? $GLOBALS['Github'] : ''; ?> GitHub
                    </button>
                </a>
                <a href="https://chat.whatsapp.com/G8hH7Gytfn5D2uYPibZT7N" class="no-ajax">
                    <button class="DZYBQD" id="whatsapp-button">
                        <?php echo isset($GLOBALS['Whatsapp']) ? $GLOBALS['Whatsapp'] : ''; ?> WhatsApp
                    </button>
                </a>
            </div>
        </div>

        <div class="DAEOXT">
            <div class="TTVMWQ">
                <div class="XXDD IUNRBL">
                    <h3 class="XXD1"><strong>Conviertete en patrocinador</strong></h3>
                    <h3 class="XXD1 XXGE3D">Tu apoyo puede darte participacion creativa, acceso anticipado, contenido exclusivo, reconocimiento y hasta acciones mensuales del proyecto.</h3>
                    <div class="DZYSQD DZYSQF">
                        <?php
                        if (function_exists('botonSponsor')) echo botonSponsor();
                        if (function_exists('botonComprarAcciones')) echo botonComprarAcciones();
                        ?>
                    </div>
                </div>
                <div class="XXDD IUNRBL">
                    <h3 class="XXD1"><strong>Unete como desarrollador</strong></h3>
                    <h3 class="XXD1 XXGE3D">Tu talento sera recompensado. Podras obtener reconocimiento, acciones del proyecto o incluso unirte al equipo principal.</h3>
                    <a href="https://chat.whatsapp.com/JOduGKvWGR9KbYfBS9BWGL" class="no-ajax">
                        <div class="DZYSQD DZYSQF">
                            <button class="DZYBQD unirteproyecto<?php if (!is_user_logged_in()) echo ' boton-sesion'; ?>">
                                <?php echo isset($GLOBALS['randomIcono']) ? $GLOBALS['randomIcono'] : ''; ?>Sumarme al proyecto!
                            </button>
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
                        <p class="ZTHAWI">Vamos por!</p>
                        <p class="BFUUUL">5000$</p>
                    </div>
                </div>
                <div class="progress-containerA1">
                    <div class="progress-barA1"></div>
                </div>

                <div class="GTVVIG">
                    <div class="XFBZWO">
                        <div class="flex justify-between items-center">
                            <p class="ZTHAWI">Tu valor actual!</p>
                        </div>
                        <p class="BFUUUL">$<?php echo number_format($valD, 2, '.', '.'); ?></p>
                        <div class="GraficoCapital">
                            <?php if (function_exists('graficoHistorialAcciones')) echo graficoHistorialAcciones(); ?>
                        </div>
                    </div>
                    <div class="XFBZWO">
                        <p class="ZTHAWI">Valor 2upra</p>
                        <p class="BFUUUL"><?php echo $valEmp; ?></p>
                        <div class="GraficoCapital">
                            <?php if (function_exists('capitalValores')) echo capitalValores(); ?>
                        </div>
                    </div>
                    <div class="XFBZWO">
                        <p class="ZTHAWI">Valor Accion</p>
                        <p class="BFUUUL"><?php echo $valAcc; ?></p>
                        <div class="GraficoCapital">
                            <?php if (function_exists('bolsavalores')) echo bolsavalores(); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="articulosPost">
                <h3>Noticias y avances</h3>
                <?php
                if (function_exists('publicaciones')) {
                    echo publicaciones(['filtro' => 'nada', 'post_type' => 'post', 'tab_id' => 'Proyecto', 'posts' => 12]);
                }
                ?>
            </div>

            <div class="WLOZDD">
                <p>Mil gracias por tu apoyo!</p>
                <?php if (function_exists('calcularAccionPorUsuario')) echo calcularAccionPorUsuario(); ?>
            </div>

            <?php if (current_user_can('administrator')): ?>
                <div class="YXJWYY flex">
                    <div class="XFBZWO">
                        <?php if (function_exists('formCompraAcciones')) echo formCompraAcciones(); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (function_exists('modalComprarAcciones')) echo modalComprarAcciones(); ?>
        </div>
<?php
        return ob_get_clean();
    }
}
