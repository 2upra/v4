(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        reiniciarCargaDiferida();
    });
    const DEPURAR = true;
    const log = DEPURAR ? console.log.bind(console) : () => {};

    let estaCargando = false;
    log('Carga reactivada en diferido start');
    let hayMasContenido = true;
    let paginaActual = 2;
    const publicacionesCargadas = new Set();
    let identificador = '';
    let eventoBusquedaConfigurado = false;
    let scrollTimeout = null;

    function reiniciarCargaDiferida() {
        log('Reiniciando carga diferida');
        window.removeEventListener('scroll', manejarScroll);
        estaCargando = false;
        log('Carga reactivada ecn reiniciarCargaDiferida');
        hayMasContenido = true;
        paginaActual = 2;
        publicacionesCargadas.clear();
        identificador = '';
        window.idUsuarioActual = null;

        if (!eventoBusquedaConfigurado) {
            configurarEventoBusqueda();
            eventoBusquedaConfigurado = true;
        }

        //ajustarAlturaMaxima();
        habilitarCargaPorScroll();
        configurarDelegacionEventosPostTag();

        // Configurar el botón de limpiar
        const botonLimpiar = document.getElementById('clearSearch');
        if (botonLimpiar) {
            botonLimpiar.addEventListener('click', e => {
                e.preventDefault();
                limpiarBusqueda();
            });
        }
        const busquedaInicial = obtenerBusquedaDeURL();
        if (busquedaInicial) {
            identificador = busquedaInicial;
            //resetearCarga();
            actualizarUIBusqueda(busquedaInicial);
            //cargarMasContenido();
        }

        reiniciarEventosPostTag();
    }

    function establecerIdUsuarioDesdeInput() {
        // Busca el div que contiene el atributo `data-iduser`
        const divConIdUsuario = document.querySelector('div.X522YA.FRRVBB[data-iduser]');

        if (divConIdUsuario) {
            // Extrae el valor del atributo `data-iduser`
            const idUsuario = divConIdUsuario.getAttribute('data-iduser');

            if (idUsuario) {
                // Busca el contenedor de perfil y establece el atributo `data-author-id`
                const contenedorPerfil = document.querySelector('.custom-uprofile-container');
                contenedorPerfil?.setAttribute('data-author-id', idUsuario);

                // Guarda el ID de usuario en una variable global (opcional)
                window.idUsuarioActual = idUsuario;
            } else {
            }
        } else {
        }
    }

    function manejarScroll() {
        if (scrollTimeout) return;
        const listaPublicaciones = document.querySelector('.tab.active .social-post-list');
        if (!listaPublicaciones) {
            log('No se encontró .social-post-list para añadir contenido');
            return;
        }
        scrollTimeout = setTimeout(() => {
            scrollTimeout = null;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const alturaVentana = window.innerHeight;
            const alturaDocumento = Math.max(document.body.scrollHeight, document.body.offsetHeight, document.documentElement.clientHeight, document.documentElement.scrollHeight, document.documentElement.offsetHeight);

            //log('Evento de scroll detectado:', {scrollTop, alturaVentana, alturaDocumento, estaCargando});

            if (scrollTop + alturaVentana > alturaDocumento - 100 && !estaCargando && hayMasContenido) {
                log('Condiciones para cargar más contenido cumplidas estaCargando', 'estaCargando:', {estaCargando}, 'hayMasContenido:', {hayMasContenido});
                const elementoPestañaActiva = document.querySelector('.tab.active');
                if (elementoPestañaActiva?.getAttribute('ajax') === 'no') {
                    log('ajax no carga detenido');
                    estaCargando = false;
                    return;
                }

                let colec = null;
                if (elementoPestañaActiva?.getAttribute('colec')) {
                    colec = elementoPestañaActiva.getAttribute('colec');
                }

                let idea = false;
                if (elementoPestañaActiva?.getAttribute('idea') === 'true') {
                    idea = true;
                }
                log('[manejarScroll] idea:', {idea}, 'colec:', {colec});
                cargarMasContenido(listaPublicaciones, null, colec, idea);
            } else {
                log('Condiciones para cargar más contenido no cumplidas');
            }
        }, 20);
    }

    // Inicializar con publicaciones existentes
    document.querySelectorAll('.social-post-list .EDYQHV').forEach(publicacion => {
        const idPublicacion = publicacion.getAttribute('id-post')?.trim();
        if (idPublicacion) {
            publicacionesCargadas.add(idPublicacion);
        }
    });

    async function cargarMasContenido(listaPublicaciones, ajax = null, colec = null, idea = null, arriba = false) {
        if (estaCargando) {
            return;
        }
        const {filtro = '', tabId = '', posttype = ''} = listaPublicaciones.dataset;

        establecerIdUsuarioDesdeInput();
        estaCargando = true;
        insertarMarcadorCarga(listaPublicaciones, filtro);
        log('Iniciando carga de más contenido');

        let intentos = 0;
        const maxIntentos = 5;
        const intervalo = 1000;
        log('[cargarMasContenido] idea:', {idea}, 'colec:', {colec});
        const buscarPestañaActiva = setInterval(async () => {
            if (listaPublicaciones) {
                log('Pestaña activa encontrada');
                clearInterval(buscarPestañaActiva);

                const idUsuario = window.idUsuarioActual;

                log('Parámetros de carga:', {filtro, tabId, identificador, idUsuario, paginaActual, colec, idea});

                try {
                    log('[fetch] idea:', {idea}, 'colec:', {colec}, 'identifier:', {identificador});
                    const respuesta = await fetch(ajaxUrl, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams({
                            action: 'cargar_mas_publicaciones',
                            paged: paginaActual,
                            filtro: filtro || '',
                            posttype: posttype || '',
                            identifier: identificador,
                            tab_id: tabId || '',
                            user_id: idUsuario || '',
                            cargadas: Array.from(publicacionesCargadas).join(',') || '',
                            colec: colec || '',
                            idea: idea ? 'true' : 'false'
                        })
                    });

                    if (!respuesta.ok) {
                        throw new Error(`HTTP error! status: ${respuesta.status}`);
                    }

                    const textoRespuesta = await respuesta.text();
                    await procesarRespuesta(textoRespuesta, listaPublicaciones, arriba);
                } catch (error) {
                    log('Error en la petición AJAX:', error);
                } finally {
                    estaCargando = false;
                }
            } else {
                intentos++;
                log('No se encontró una pestaña activa, intento:', intentos);
                if (intentos >= maxIntentos) {
                    clearInterval(buscarPestañaActiva);
                    log('No se encontró una pestaña activa después de varios intentos');
                    estaCargando = false;
                }
            }
        }, intervalo);
    }

    const MAX_POSTS = 50;

    async function procesarRespuesta(respuesta, listaPublicaciones, arriba = false) {
        const doc = validarRespuesta(respuesta);
        if (!doc) {
            eliminarMarcadorCarga(listaPublicaciones);
            return;
        }

        const publicacionesValidas = procesarPublicaciones(doc);
        manejarContenido(publicacionesValidas, listaPublicaciones, arriba);
        eliminarMarcadorCarga(listaPublicaciones);
    }

    let intervalId;
    let loadingCount = 0;

    function insertarMarcadorCarga(lista, filtro) {
        if (filtro !== 'sampleList' || !lista) return;
        const marca = document.createElement('div');
        marca.setAttribute('role', 'status');
        marca.className = 'loading-placeholder';
        const texto = document.createElement('span');
        texto.className = 'sr-only';
        texto.textContent = 'Loading...';
        marca.appendChild(texto);
        lista.insertAdjacentElement('beforeend', marca);
        intervalId = setInterval(() => {
            if (loadingCount < 12) {
                const barra = document.createElement('div');
                barra.className = 'loading-bar';
                marca.appendChild(barra);
                loadingCount++;
            } else {
                clearInterval(intervalId);
            }
        }, 1);
    }

    function eliminarMarcadorCarga(listaPublicaciones) {
        if (!listaPublicaciones) return;
        const marcadorCarga = listaPublicaciones.querySelector('.loading-placeholder');
        if (marcadorCarga) {
            marcadorCarga.remove();
        }
        clearInterval(intervalId);
        intervalId = null;
        loadingCount = 0;
    }

    function validarRespuesta(respuesta) {
        log('Respuesta recibida:', respuesta.substring(0, 100) + '...');

        const respuestaLimpia = respuesta.trim();
        if (respuestaLimpia === '<div id="no-more-posts"></div>') {
            log('No hay más publicaciones');
            detenerCarga();
            return null;
        }
        if (!respuestaLimpia) {
            log('Respuesta vacía recibida');
            detenerCarga();
            return null;
        }
        return new DOMParser().parseFromString(respuesta, 'text/html');
    }

    function procesarPublicaciones(doc) {

        const totalPostsInputFromResponse = doc.querySelector('.total-posts-sampleList');
        if (totalPostsInputFromResponse) {
            const totalPostsValue = totalPostsInputFromResponse.getAttribute('value');
            const totalPostsInputInDOM = document.querySelector('.total-posts-sampleList');
            if (totalPostsInputInDOM) {
                totalPostsInputInDOM.value = totalPostsValue;
                log('Campo total-posts-sampleList actualizado desde la respuesta:', totalPostsValue);
                contadorDeSamples();
            }
        }

        const publicacionesNuevas = doc.querySelectorAll('.EDYQHV');
        const publicacionesValidas = [];

        if (publicacionesNuevas.length === 0) {
            log('No se encontraron publicaciones nuevas en la respuesta');
            detenerCarga();
            return [];
        }

        publicacionesNuevas.forEach(publicacion => {
            const idPublicacion = publicacion.getAttribute('id-post')?.trim();
            const existeEnDOM = document.querySelector(`.social-post-list .EDYQHV[id-post="${idPublicacion}"]`);

            if (idPublicacion && !publicacionesCargadas.has(idPublicacion) && !existeEnDOM) {
                publicacionesCargadas.add(idPublicacion);
                publicacionesValidas.push(publicacion.outerHTML);
                log('Publicación añadida:', idPublicacion);
            } else {
                log('Publicación duplicada omitida:', idPublicacion);
            }
        });
        contadorDeSamples();
        return publicacionesValidas;
    }

    // Parte 3: Insertar y manejar contenido en el DOM

    function manejarContenido(publiValidas, listaPubli, arriba = false) {
        if (publiValidas.length > 0) {
            const pos = arriba ? 'afterbegin' : 'beforeend';
            listaPubli.insertAdjacentHTML(pos, publiValidas.join(''));
            paginaActual++;
    
            const publiEnDOM = listaPubli.querySelectorAll('.EDYQHV');
            if (publiEnDOM.length > MAX_POSTS) {
                const exceso = publiEnDOM.length - MAX_POSTS;
                for (let i = 0; i < exceso; i++) {
                    const elim = publiEnDOM[i];
                    const idPubli = elim.getAttribute('id-post')?.trim();
                    if (idPubli) {
                        publicacionesCargadas.delete(idPubli);
                    }
                    listaPubli.removeChild(elim);
                }
            }
    
            ['inicializarWaveforms', 'empezarcolab', 'submenu', 'seguir', 'modalDetallesIA', 'tagsPosts', 'handleAllRequests', 'registrarVistas', 'colec', 'animacionLike', 'initTareas'].forEach(func => {
                if (typeof window[func] === 'function') window[func]();
            });
    
            reiniciarEventosPostTag();
        } else {
            detenerCarga();
        }
    }

    function reiniciarEventosPostTag() {
        log('Reiniciando eventos de clic mediante delegación en <span class="postTag">');
        configurarDelegacionEventosPostTag();
    }

    function habilitarCargaPorScroll() {
        log('Configurando evento de scroll');
        window.addEventListener('scroll', manejarScroll);
    }

    function configurarDelegacionEventosPostTag() {
        document.removeEventListener('click', delegarClickPostTag);
        document.addEventListener('click', delegarClickPostTag);
        log('Delegación de eventos de clic configurada globalmente');
    }

    function delegarClickPostTag(e) {
        const tag = e.target.closest('.postTag');
        if (tag) {
            e.preventDefault();
            e.stopPropagation();
            const valorTag = tag.textContent.trim();

            if (valorTag) {
                const listaPublicaciones = document.querySelector('.tab.active .social-post-list');
                if (!listaPublicaciones) {
                    log('No se encontró .social-post-list para añadir contenido');
                    return;
                }
                identificador = valorTag;
                actualizarUIBusqueda(valorTag);
                log('Nuevo identificador establecido:', identificador);
                resetearCarga();
                cargarMasContenido(listaPublicaciones);
            }
        }
    }

    window.limpiarBusqueda = function (limpiar = true, arriba = false) {
        const lista = document.querySelector('.tab.active .social-post-list');
        if (!lista) {
            return;
        }
        publicacionesCargadas.clear();
        identificador = '';

        if (limpiar) {
            lista.innerHTML = '';
        }

        actualizarUIBusqueda('');
        resetearCarga();
        cargarMasContenido(lista, null, null, null, arriba);
    };

    function resetearCarga() {
        log('Ejecutando resetearCarga');
        paginaActual = 1;
        publicacionesCargadas.clear();
        log('Carga reactivada en resetearCarga');
        hayMasContenido = true;
        // Opcional: Scroll hacia la parte superior
        window.scrollTo(0, 0);
    }

    function manejadorEventoBusqueda(e) {
        const esEnter = e.type === 'keydown' && (e.key === 'Enter' || e.keyCode === 13);

        // Verificar si el evento proviene de un botón
        const esBoton = e.type === 'click' && (e.target.classList.contains('buttonBI') || e.target.classList.contains('buttonBuscar'));

        // Ejecutar la lógica solo si es Enter o un botón
        if (esEnter || esBoton) {
            if (esEnter) e.preventDefault(); // Prevenir comportamiento predeterminado del Enter (si necesario)

            const listaPublicaciones = document.querySelector('.tab.active .social-post-list');
            if (!listaPublicaciones) {
                log('No se encontró .social-post-list para añadir contenido');
                return;
            }

            // Obtener el identificador del input de búsqueda
            const inputBusqueda = document.getElementById('identifier');
            identificador = inputBusqueda.value.trim();

            if (identificador === '') {
                log('El campo de búsqueda está vacío');
                return;
            }

            actualizarUIBusqueda(identificador);
            log('Búsqueda activada, valor de identificador:', identificador);
            resetearCarga();
            cargarMasContenido(listaPublicaciones);
        }
    }

    function configurarEventoBusqueda() {
        const inputBusqueda = document.getElementById('identifier');
        const botonesBusqueda = document.querySelectorAll('.buttonBI, .buttonBuscar');

        if (inputBusqueda) {
            // Usar keydown en lugar de keypress
            inputBusqueda.removeEventListener('keydown', manejadorEventoBusqueda);
            inputBusqueda.addEventListener('keydown', manejadorEventoBusqueda);

            log('Evento de búsqueda configurado para el input #identifier');
        } else {
            log('No se encontró el elemento input de búsqueda');
        }

        // Eventos para los botones
        if (botonesBusqueda.length > 0) {
            botonesBusqueda.forEach(boton => {
                boton.removeEventListener('click', manejadorEventoBusqueda);
                boton.addEventListener('click', manejadorEventoBusqueda);
            });
            log('Evento de búsqueda configurado para los botones .buttonBI y .buttonBuscar');
        } else {
            log('No se encontraron botones de búsqueda');
        }
    }

    window.detenerCarga = function () {
        log('Carga detenida');
        hayMasContenido = false;
        window.removeEventListener('scroll', manejarScroll);
    };
    /*
    function ajustarAlturaMaxima() {
        const contenedor = document.querySelector('.SAOEXP .clase-rolastatus');
        const elemento = contenedor?.querySelector('li[filtro="rolastatus"]');
        if (contenedor && elemento) {
            contenedor.style.maxHeight = `${elemento.offsetHeight + 40}px`;
            log('Altura máxima ajustada');
        } else {
            log('No se encontró contenedor o elemento para ajustar altura máxima');
        }
    }

    window.addEventListener('resize', ajustarAlturaMaxima);
    */
    /////////////////////////////////
    // Actualizar URL con el parámetro de búsqueda
    function actualizarURL(busqueda) {
        const nuevaURL = new URL(window.location);
        if (busqueda) {
            nuevaURL.searchParams.set('busqueda', busqueda);
        } else {
            nuevaURL.searchParams.delete('busqueda');
        }
        window.history.pushState({}, '', nuevaURL);
    }

    function obtenerBusquedaDeURL() {
        const params = new URLSearchParams(window.location.search);
        return params.get('busqueda') || '';
    }

    // Modificar la función actualizarUIBusqueda
    function actualizarUIBusqueda(valor) {
        const inputBusqueda = document.getElementById('identifier');
        const botonLimpiar = document.getElementById('clearSearch');

        if (inputBusqueda) {
            inputBusqueda.value = valor;
        }

        if (botonLimpiar) {
            botonLimpiar.style.display = valor ? 'block' : 'none';
        }

        actualizarURL(valor);
    }

    window.actualizarUIBusquedaNoURL = function () {
        const inputBusqueda = document.getElementById('identifier');
        const botonLimpiar = document.getElementById('clearSearch');

        if (inputBusqueda) {
            inputBusqueda.value = '';
        }

        if (botonLimpiar) {
            botonLimpiar.style.display = 'none';
        }
    };
})();

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
                } else {
                }
            }
        });
    });

    // Seleccionamos todos los elementos con la clase 'lazy-svg'
    const lazySvgs = document.querySelectorAll('.lazy-svg');

    lazySvgs.forEach(div => {
        observer.observe(div);
    });
} else {
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.svg-container').forEach(function (contenedorSvg) {
        const infoTooltip = contenedorSvg.querySelector('.tinfo');
        contenedorSvg.addEventListener('mouseenter', function () {
            infoTooltip.style.display = 'block';
        });
        contenedorSvg.addEventListener('mouseleave', function () {
            infoTooltip.style.display = 'none';
        });
    });
});

window.contadorDeSamples = () => {
    // Obtener el elemento donde se mostrarán los resultados
    const resultadosElement = document.getElementById('resultadosPost-sampleList');

    // Función para contar los posts filtrados
    function contarPostsFiltrados() {
        // Obtener los parámetros de búsqueda y filtros si existen
        const urlParams = new URLSearchParams(window.location.search);
        const searchQuery = urlParams.get('busqueda') || ''; // Cambia 'busqueda' según tu parámetro de URL

        // Obtener el tipo de post del atributo typepost, si existe
        const postType = resultadosElement.getAttribute('typepost') || 'social_post';

        // Enviar la solicitud AJAX
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: new URLSearchParams({
                action: 'contarPostsFiltrados', // Nombre de la acción en PHP
                search: searchQuery,
                post_type: postType // Agregar el tipo de post a la solicitud
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Formatear el número de resultados (puntos para miles, etc.)
                    const totalPosts = data.data.total;
                    const formattedTotalPosts = totalPosts.toLocaleString('es-ES');

                    // Actualizar el contenido del elemento
                    resultadosElement.textContent = `${formattedTotalPosts} resultados`;
                } else {
                    // Mostrar un mensaje de error si algo salió mal
                    resultadosElement.textContent = '0 resultados';
                    console.error(data.data.message || 'Error desconocido.');
                }
            })
            .catch(error => {
                // Manejar errores de la solicitud
                resultadosElement.textContent = '0 resultados';
                console.error('Error en la solicitud AJAX:', error);
            });
    }

    // Ejecutar la función al cargar la página
    if (resultadosElement) {
        contarPostsFiltrados();
    }
};
