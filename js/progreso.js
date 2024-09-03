function textflux() {
    const textContainer = document.querySelector('#textflux');
    const stickyContainer = document.querySelector('#containerflux');
    if (!textContainer || !stickyContainer) {
        console.warn('textflux: Elementos no encontrados.');
        return;
    }

    const texts = [
        "Interacción social, compartir ideas artísticas con facilidad",
        "Chat en tiempo real con funciones adaptadas al desarrollo de ideas",
        "Descentralización musical: nuestra propia plataforma de streaming",
        "Respetar la seguridad, privacidad y los derechos de las personas y también de cada trabajo artístico",
        "Queremos ofrecer un espacio único al arte... y que sea autosustentable",
        "Buscamos personas que quieran apoyar, hay 99% de fe y 0.1% de posibilidad (0.9% para las chelas)"
    ];

    let lastIndex = -1;

    function updateText() {
        const rect = stickyContainer.getBoundingClientRect();
        const containerHeight = stickyContainer.offsetHeight;
        const windowHeight = window.innerHeight;
        const relativePosition = (windowHeight - rect.top) / containerHeight;

        if (relativePosition > 0 && relativePosition <= 1) {
            let index = Math.floor(relativePosition * texts.length);
            index = Math.max(0, Math.min(index, texts.length - 1));
            if (index !== lastIndex) {
                textContainer.style.opacity = 0;
                setTimeout(() => {
                    textContainer.textContent = texts[index];
                    textContainer.style.opacity = 1;
                }, 250);
                lastIndex = index;
            }
        } else if (rect.top > windowHeight) {
            textContainer.textContent = texts[0];
            textContainer.style.opacity = 1;
            lastIndex = 0;
        } else {
            textContainer.style.opacity = 0;
            lastIndex = -1;
        }
    }

    window.addEventListener('scroll', updateText);
    window.addEventListener('load', updateText);
    updateText();
}
function avances() {
    window.addEventListener('load', () => {
        const avancesContents = document.querySelectorAll('.avances-content');

        // Verificar si se encontraron elementos antes de continuar
        if (avancesContents.length === 0) {
            return;
        }

        const observer = new IntersectionObserver(
            entries => {
                // Filtrar solo los elementos que están intersectando
                const intersectingEntries = entries.filter(entry => entry.isIntersecting);

                if (intersectingEntries.length > 0) {
                    // Calcular el centro de la pantalla
                    const screenCenter = window.innerHeight / 2;

                    // Encontrar el elemento más cercano al centro de la pantalla
                    const closestEntry = intersectingEntries.reduce((closest, entry) => {
                        const entryCenter = entry.boundingClientRect.top + entry.boundingClientRect.height / 2;
                        const closestCenter = closest.boundingClientRect.top + closest.boundingClientRect.height / 2;
                        return Math.abs(entryCenter - screenCenter) < Math.abs(closestCenter - screenCenter) ? entry : closest;
                    });

                    // Remover la clase "visible" de todos los elementos
                    avancesContents.forEach(avancesContent => {
                        avancesContent.classList.remove('visible');
                    });

                    // Agregar la clase "visible" al elemento más cercano al centro
                    closestEntry.target.classList.add('visible');
                }
            },
            {
                threshold: Array.from({length: 101}, (_, i) => i * 0.01), // Ajuste fino del threshold
                rootMargin: '-10% 0px -10% 0px' // Ajuste del rootMargin
            }
        );

        avancesContents.forEach(avancesContent => {
            observer.observe(avancesContent);
        });

        // Función para actualizar la visibilidad manualmente
        const updateVisibility = () => {
            const screenCenter = window.innerHeight / 2;
            let closestEntry = null;

            avancesContents.forEach(avancesContent => {
                const rect = avancesContent.getBoundingClientRect();
                const entryCenter = rect.top + rect.height / 2;

                if (!closestEntry || Math.abs(entryCenter - screenCenter) < Math.abs(closestEntry.center - screenCenter)) {
                    closestEntry = {
                        element: avancesContent,
                        center: entryCenter
                    };
                }
            });

            if (closestEntry) {
                avancesContents.forEach(avancesContent => {
                    avancesContent.classList.remove('visible');
                });
                closestEntry.element.classList.add('visible');
            }
        };

        // Debouncing para mejorar el rendimiento
        let debounceTimeout;
        const debounceUpdateVisibility = () => {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(updateVisibility, 100);
        };

        window.addEventListener('scroll', debounceUpdateVisibility);
        window.addEventListener('resize', debounceUpdateVisibility);
    });
}
function manageSeparatorsAndOrder(spaceProgresoSelector, toggleOrderButtonSelector) {
    function addSeparators() {
        var spaceprogreso = document.querySelector(spaceProgresoSelector);

        // Verificar si el elemento spaceprogreso existe
        if (!spaceprogreso) {
            return;
        }

        var children = Array.from(spaceprogreso.children);

        // Eliminar separadores existentes
        children.forEach(child => {
            if (child.classList.contains('separator')) {
                spaceprogreso.removeChild(child);
            }
        });

        // Añadir nuevos separadores
        for (var i = children.length - 1; i > 0; i--) {
            var separator = document.createElement('div');
            separator.className = 'separator';
            spaceprogreso.insertBefore(separator, children[i]);
        }
    }

    var toggleOrderButton = document.querySelector(toggleOrderButtonSelector);

    // Verificar si el botón toggleOrderButton existe
    if (toggleOrderButton) {
        toggleOrderButton.addEventListener('click', function () {
            var spaceprogreso = document.querySelector(spaceProgresoSelector);

            // Verificar si el elemento spaceprogreso existe dentro del evento click
            if (!spaceprogreso) {
                return;
            }

            var children = Array.from(spaceprogreso.children);
            children.reverse().forEach(child => spaceprogreso.appendChild(child));
            addSeparators();
        });
    }

    addSeparators();
}
function updateDates() {
    function timeSince(date) {
        const now = new Date();
        const past = new Date(date);

        // Verificar si la fecha es válida
        if (isNaN(past)) {
            return 'Fecha inválida';
        }

        const seconds = Math.floor((now - past) / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);
        const months = Math.floor(days / 30);
        const years = Math.floor(days / 365);

        function pluralize(value, singular, plural) {
            return value === 1 ? `${value} ${singular}` : `${value} ${plural}`;
        }

        if (years > 0) {
            return `Hace ${pluralize(years, 'año', 'años')} y ${pluralize(months % 12, 'mes', 'meses')}`;
        } else if (months > 0) {
            return `Hace ${pluralize(months, 'mes', 'meses')} y ${pluralize(days % 30, 'día', 'días')}`;
        } else if (days > 0) {
            return `Hace ${pluralize(days, 'día', 'días')}`;
        } else if (hours > 0) {
            return `Hace ${pluralize(hours, 'hora', 'horas')}`;
        } else if (minutes > 0) {
            return `Hace ${pluralize(minutes, 'minuto', 'minutos')}`;
        } else {
            return `Hace ${pluralize(seconds, 'segundo', 'segundos')}`;
        }
    }

    const elements = document.querySelectorAll('.XXD11');

    // Verificar si se encontraron elementos antes de continuar
    if (elements.length === 0) {
        return;
    }

    elements.forEach(element => {
        const dateText = element.textContent.trim();

        // Verificar si el texto de la fecha no está vacío
        if (!dateText) {
            return;
        }

        const timeAgo = timeSince(dateText);
        element.textContent = timeAgo;
    });
}
function initializeProgressSegments() {
    function hideAllTooltips() {
        document.querySelectorAll('.progress-segment').forEach(segment => {
            segment.classList.remove('show-tooltip');
        });
    }

    // Mostrar tooltip al pasar el mouse
    document.querySelectorAll('.progress-segment').forEach(segment => {
        segment.addEventListener('mouseover', function () {
            hideAllTooltips(); // Ocultar todos los tooltips visibles
            const tooltip = this.querySelector('.tooltip');
            if (tooltip) {
                tooltip.innerHTML = this.getAttribute('data-tooltip').replace(/\*salto de linea\*/g, '<br>');
                this.classList.add('show-tooltip');
            }
        });

        segment.addEventListener('mouseout', function () {
            this.classList.remove('show-tooltip');
        });
    });

    // Mostrar tooltips secuencialmente
    function showTooltipSequentially() {
        const segments = document.querySelectorAll('.progress-segment.green');
        let currentIndex = 0;

        function showNextTooltip() {
            hideAllTooltips(); // Ocultar todos los tooltips visibles

            if (currentIndex < segments.length) {
                const tooltip = segments[currentIndex].querySelector('.tooltip');
                if (tooltip) {
                    tooltip.innerHTML = segments[currentIndex].getAttribute('data-tooltip').replace(/\*salto de linea\*/g, '<br>');
                }
                segments[currentIndex].classList.add('show-tooltip');
                currentIndex++;
            } else {
                currentIndex = 0;
            }
            setTimeout(showNextTooltip, 5000); // Cambia el tiempo según tus necesidades
        }

        showNextTooltip();
    }

    // Llamar a showTooltipSequentially cuando se cargue la ventana
    window.onload = showTooltipSequentially;

    // Mover el scroll al hacer clic en un segmento
    document.querySelectorAll('.progress-segment').forEach(segment => {
        segment.addEventListener('click', function () {
            const targetId = this.id.replace('pp', 'ppp');
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({behavior: 'smooth'});
            }
        });
    });
}
function initializeCustomTooltips() {
    try {
        // Selecciona todos los elementos con la clase 'valorbolsa1'
        var tooltips = document.querySelectorAll('.valorbolsa1');

        // Si no hay tooltips, simplemente retorna para evitar errores.
        if (!tooltips.length) return;

        tooltips.forEach(function (tooltip) {
            var tooltipText = tooltip.getAttribute('title');
            tooltip.removeAttribute('title'); // Elimina el atributo title para evitar el tooltip nativo del navegador

            var customTooltip = document.createElement('div');
            customTooltip.className = 'custom-tooltip';
            customTooltip.textContent = tooltipText;
            document.body.appendChild(customTooltip);

            tooltip.addEventListener('mouseenter', function () {
                // Cuando el ratón pasa sobre el elemento
                customTooltip.style.display = 'block';
                customTooltip.style.opacity = '1';
            });

            tooltip.addEventListener('mouseleave', function () {
                // Cuando el ratón deja el elemento
                customTooltip.style.display = 'none';
                customTooltip.style.opacity = '0';
            });

            tooltip.addEventListener('mousemove', function (e) {
                // Cuando el ratón se mueve dentro del elemento
                customTooltip.style.top = e.pageY + 10 + 'px';
                customTooltip.style.left = e.pageX + 10 + 'px';
            });
        });
    } catch (error) {}
}
function updateDaysElapsed(startDateStr) {
    try {
        const startDate = new Date(startDateStr); // Formato YYYY-MM-DD
        const today = new Date();

        if (isNaN(startDate.getTime())) {
            return;
        }

        // Calcula la diferencia en milisegundos
        const diffTime = Math.abs(today - startDate);
        // Convierte la diferencia a días
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        // Actualiza el contenido del elemento con el ID "diasPasados"
        const diasPasadosElement = document.getElementById('diasPasados');

        if (diasPasadosElement) {
            diasPasadosElement.textContent = diffDays;
        } else {
        }
    } catch (error) {}
}
function fondoAcciones() {
    const parentDivs = document.querySelectorAll('.XX1.A300524152');

    parentDivs.forEach(parentDiv => {
        const childDivs = parentDiv.children;

        function updateBackground() {
            if (childDivs.length >= 2) {
                const firstChild = childDivs[0];
                const secondChild = childDivs[1];

                const parentRect = parentDiv.getBoundingClientRect();
                const firstChildRect = firstChild.getBoundingClientRect();
                const secondChildRect = secondChild.getBoundingClientRect();

                const topDifference = Math.abs(secondChildRect.top - firstChildRect.top);
                const verticalOverlap = secondChildRect.top < firstChildRect.bottom && firstChildRect.top < secondChildRect.bottom;

                if (topDifference > firstChildRect.height / 2 || !verticalOverlap) {
                    parentDiv.style.background = `linear-gradient(to bottom, #0f0f0f 50%, #0a0a0a 50%)`;
                } else {
                    parentDiv.style.background = `linear-gradient(to right, #0f0f0f 50%, #0a0a0a 50%)`;
                }
            }
        }

        updateBackground();
        window.addEventListener('resize', updateBackground);
    });
}
fondoAcciones();
function inicializarPestanasSec(config) {
    const pestanas = {};

    for (const [grupoPestanas, elementos] of Object.entries(config)) {
        pestanas[grupoPestanas] = {
            botones: {},
            contenidos: {}
        };

        for (const [nombre, ids] of Object.entries(elementos)) {
            const boton = document.getElementById(ids.boton);
            const contenido = document.getElementById(ids.contenido);

            if (boton && contenido) {
                pestanas[grupoPestanas].botones[nombre] = boton;
                pestanas[grupoPestanas].contenidos[nombre] = contenido;
            }
        }

        const {botones, contenidos} = pestanas[grupoPestanas];

        // Verifica si hay botones y contenidos válidos antes de proceder
        if (Object.keys(botones).length > 0 && Object.keys(contenidos).length > 0) {
            function resetBotones() {
                Object.values(botones).forEach(boton => boton.classList.remove('active'));
            }
            
            function showContent(content, activeButton) {
                Object.values(contenidos).forEach(contenido => (contenido.style.display = 'none'));
                content.style.display = 'flex';
                resetBotones();
                activeButton.classList.add('active');
            }

            Object.entries(botones).forEach(([key, boton]) => {
                boton.addEventListener('click', () => showContent(contenidos[key], boton));
            });

            // Mostrar inicialmente el contenido del primer botón
            const primerBoton = Object.values(botones)[0];
            const primerContenido = Object.values(contenidos)[0];
            if (primerBoton && primerContenido) {
                showContent(primerContenido, primerBoton);
            }
        }
    }
}
function pestanasgroup() {
    inicializarPestanasSec({
        panelAcciones: {
            lista: {
                boton: 'botonlistaaccion',
                contenido: 'contenidolista'
            },
            perfil: {
                boton: 'botonperfilaccion',
                contenido: 'contenidoperfil'
            },
            comprar: {
                boton: 'botoncompraraccion',
                contenido: 'contenidocomprar'
            }
        },
        transacciones: {
            transacciones: {
                boton: 'BotonListaTransacciones',
                contenido: 'ContenidoListaTranssacciones'
            },
            errores: {
                boton: 'BotonErrores',
                contenido: 'ContenidoErrores'
            }
        },
        logs: {
            logs1: {
                boton: 'BotonLogs1',
                contenido: 'ContenidoLogs1'
            },
            logs2: {
                boton: 'BotonLogs2',
                contenido: 'ContenidoLogs2'
            }
        }
    });
}
function progresosinteractive() {
    const elementos = document.querySelectorAll('.E17072412');
    const contenedor = document.getElementById('contenedor1707');
    const barraProgreso = document.getElementById('barraProgreso1707');

    if (!contenedor || !barraProgreso || elementos.length === 0) {
        return;
    }

    let index = 0;
    let intervalo;

    function mostrarElemento(indice) {
        elementos.forEach((el, i) => {
            el.classList.toggle('activo', i === indice);
        });
    }

    function cambiarElemento(direccion) {
        index = (index + direccion + elementos.length) % elementos.length;
        mostrarElemento(index);
        reiniciarIntervalo();
    }

    function reiniciarIntervalo() {
        clearInterval(intervalo);
        barraProgreso.style.width = '0';

        let progreso = 0;
        const duracion = 10000; // 10 segundos

        intervalo = setInterval(() => {
            progreso += 100;
            const porcentaje = (progreso / duracion) * 100;
            barraProgreso.style.width = `${porcentaje}%`;

            if (porcentaje >= 100) {
                cambiarElemento(-1);
            }
        }, 100);
    }

    function detenerIntervalo() {
        clearInterval(intervalo);
    }

    mostrarElemento(index);
    reiniciarIntervalo();

    elementos.forEach(el => {
        el.addEventListener('mouseenter', detenerIntervalo);
        el.addEventListener('mouseleave', reiniciarIntervalo);
    });

    contenedor.addEventListener('click', function (event) {
        const rect = contenedor.getBoundingClientRect();
        const x = event.clientX - rect.left;

        if (x > rect.width / 2) {
            cambiarElemento(-1);
        } else {
            cambiarElemento(1);
        }
    });
}
function setupScrolling() {
    const container = document.querySelector('#content');
    if (!container) {
        return;
    }

    const items = container.querySelectorAll('.C2024715');
    if (!items || items.length === 0) {
        return;
    }

    let lastScrollTop = 0;

    function centerElement(element) {
        const containerHeight = container.clientHeight;
        const elementHeight = element.offsetHeight;
        const scrollTop = element.offsetTop - (containerHeight - elementHeight) / 2;

        container.scrollTo({
            top: Math.max(0, scrollTop),
            behavior: 'smooth'
        });
    }

    container.addEventListener('scroll', () => {
        const st = container.scrollTop;
        const scrollingDown = st > lastScrollTop;
        lastScrollTop = st;

        clearTimeout(container.isScrolling);
        container.isScrolling = setTimeout(() => {
            let targetItem;

            if (scrollingDown) {
                targetItem = Array.from(items).find(item => item.offsetTop > st);
            } else {
                targetItem = Array.from(items)
                    .reverse()
                    .find(item => item.offsetTop < st + container.clientHeight);
            }

            if (targetItem) {
                centerElement(targetItem);
            }
        }, 150);
    });

    // Centrar el primer elemento al cargar
    if (items.length > 0) {
        centerElement(items[0]);
    }
}


