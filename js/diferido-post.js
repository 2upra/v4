(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        reiniciarCargaDiferida();
    });
    const DEPURAR = false;
    const log = DEPURAR ? console.log.bind(console) : () => {};

    let estaCargando = false;
    log('Carga reactivada en diferido start');
    let hayMasContenido = true;
    let paginaActual = 2;
    const publicacionesCargadas = new Set();
    let identificador = '';
    let eventoBusquedaConfigurado = false;
    let scrollTimeout = null;
    const MAX_POSTS = 50;
    let intervalId;
    let loadingCount = 0;

    function reiniciarCargaDiferida() {
        log('Reiniciando carga diferida');
        window.removeEventListener('scroll', manejarScrollGlobal);
        window.removeEventListener('scroll', manejarScrollLista);
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
                reiniciarContenido();
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
        const divConIdUsuario = document.querySelector('div.X522YA.FRRVBB[data-iduser]');
        if (divConIdUsuario) {
            const idUsuario = divConIdUsuario.getAttribute('data-iduser');
            if (idUsuario) {
                const contenedorPerfil = document.querySelector('.custom-uprofile-container');
                contenedorPerfil?.setAttribute('data-author-id', idUsuario);
                window.idUsuarioActual = idUsuario;
            } else {
            }
        } else {
        }
    }

    function habilitarCargaPorScroll() {
        log('Configurando evento de scroll');
        window.addEventListener('scroll', manejarScrollGlobal);

        const listas = document.querySelectorAll('.social-post-list');
        listas.forEach(lista => {
            lista.addEventListener('scroll', manejarScrollLista);
        });
    }

    function manejarScrollGlobal() {
        if (scrollTimeout) return;

        scrollTimeout = setTimeout(() => {
            scrollTimeout = null;

            const tabActivo = document.querySelector('.tab.active');
            if (!tabActivo) return;

            const listas = tabActivo.querySelectorAll('.social-post-list');

            if (listas.length === 1) {
                //console.log('Caso 1: Una sola lista');
                manejarScrollVentana(tabActivo, listas[0]);
            }
        }, 20);
    }

    function manejarScrollLista(event) {
        const lista = event.target;
        const tabActivo = document.querySelector('.tab.active');
        if (!tabActivo || !hayMasContenido || estaCargando) return;

        const scrollTop = lista.scrollTop;
        const alturaLista = lista.scrollHeight;
        const alturaVisible = lista.clientHeight;

        if (scrollTop + alturaVisible >= alturaLista - 100) {
            //console.log('Cargando contenido en lista:', lista.id);
            precargarContenido(lista, tabActivo);
        }
    }

    function manejarScrollVentana(tabActivo, lista) {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const alturaVentana = window.innerHeight;
        const alturaDocumento = Math.max(document.body.scrollHeight, document.body.offsetHeight, document.documentElement.clientHeight, document.documentElement.scrollHeight, document.documentElement.offsetHeight);

        if (scrollTop + alturaVentana > alturaDocumento - 100 && !estaCargando && hayMasContenido) {
            //console.log('Cargando contenido en caso 1');
            precargarContenido(lista, tabActivo);
        }
    }

    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    function precargarContenido(lista, tabActivo) {
        if (tabActivo.getAttribute('ajax') === 'no') {
            estaCargando = false;
            return;
        }

        let colec = null;
        if (tabActivo.getAttribute('colec')) {
            colec = tabActivo.getAttribute('colec');
        }

        let idea = false;
        if (tabActivo.getAttribute('idea') === 'true') {
            idea = true;
        }

        cargarMasContenido(lista, null, colec, idea);
    }

    document.querySelectorAll('.social-post-list .EDYQHV').forEach(publicacion => {
        const idPublicacion = publicacion.getAttribute('id-post')?.trim();
        if (idPublicacion) {
            publicacionesCargadas.add(idPublicacion);
        }
    });

    async function cargarMasContenido(listaPublicaciones, ajax = null, colec = null, idea = null, arriba = false, prioridad = false, id = null) {
        let log = '';
        let respuestaCompleta = null; // Variable para almacenar la respuesta completa

        if (estaCargando) {
            log += 'La función ya está en ejecución.\n';
            //console.log(log);
            return {log, respuestaCompleta}; // Devuelve log y respuestaCompleta
        }

        let {filtro = '', tabId = '', posttype = ''} = listaPublicaciones ? listaPublicaciones.dataset : {};
        log += `Datos iniciales: filtro=${filtro}, tabId=${tabId}, posttype=${posttype}\n`;
        establecerIdUsuarioDesdeInput();
        estaCargando = true;

        if (listaPublicaciones) {
            insertarMarcadorCarga(listaPublicaciones, filtro);
        }

        if (prioridad && filtro === 'tarea') {
            filtro = 'tareaPrioridad';
            log += 'Se cambió el filtro a tareaPrioridad.\n';
        }

        const maxIntentos = 5;
        let intentos = 0;

        while (!listaPublicaciones && intentos < maxIntentos) {
            intentos++;
            log += `No se encontró listaPublicaciones, intento: ${intentos}.\n`;
            //console.log(log);
            await new Promise(resolve => setTimeout(resolve, 1000)); // Espera 1 segundo
            listaPublicaciones = document.querySelector(`.tab.active .social-post-list.clase-${posttype}`);
            ({filtro = '', tabId = '', posttype = ''} = listaPublicaciones ? listaPublicaciones.dataset : {});
        }

        if (!listaPublicaciones) {
            log += 'No se encontró listaPublicaciones después de varios intentos.\n';
            //console.log(log);
            estaCargando = false;
            return {log, respuestaCompleta}; // Devuelve log y respuestaCompleta
        }

        const idUsuario = window.idUsuarioActual;
        log += `ID de usuario actual: ${idUsuario}.\n`;

        try {
            const data = new URLSearchParams({
                action: 'cargar_mas_publicaciones',
                paged: paginaActual,
                filtro: filtro || '',
                posttype: posttype || '',
                identifier: identificador,
                tab_id: tabId || '',
                user_id: idUsuario || '',
                cargadas: Array.from(publicacionesCargadas).join(',') || '',
                colec: colec || '',
                idea: idea ? 'true' : 'false',
                id: id || ''
            });
            log += `Datos enviados en la petición: ${data.toString()}.\n`;

            const respuesta = await fetch(ajaxUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: data
            });
            log += `Respuesta recibida: ${respuesta.status} ${respuesta.statusText}.\n`;

            if (!respuesta.ok) {
                throw new Error(`HTTP error! status: ${respuesta.status}`);
            }

            const textoRespuesta = await respuesta.text();
            log += `Texto de la respuesta: ${textoRespuesta.substring(0, 200)}... (truncado).\n`;
            respuestaCompleta = textoRespuesta; // Guardar la respuesta completa
            await procesarRespuesta(textoRespuesta, listaPublicaciones, arriba, id);
        } catch (error) {
            log += `Error en la petición AJAX: ${error}.\n`;
            //console.error(log);
        } finally {
            estaCargando = false;
            log += 'La función ha finalizado.\n';
            //console.log(log);
        }

        return {log, respuestaCompleta}; // Devuelve log y respuestaCompleta
    }

    async function procesarRespuesta(respuesta, listaPublicaciones, arriba = false, id = null) {
        const doc = validarRespuesta(respuesta);
        if (!doc) {
            eliminarMarcadorCarga(listaPublicaciones);
            return;
        }

        let force = false;

        if (id !== null) {
            force = true;
        }

        const publicacionesValidas = procesarPublicaciones(doc, force);
        manejarContenido(publicacionesValidas, listaPublicaciones, arriba, id);
        eliminarMarcadorCarga(listaPublicaciones);
    }

    function manejarContenido(publiValidas, listaPubli, arriba = false, id = null) {
        let log = '';
        //aqui necesito que si recibe una id, entonces, si no la puedo remplazar, la agrega
        if (id) {
            const postExistente = listaPubli.querySelector(`.EDYQHV[id-post="${id}"]`);
            log += `Se recibió ID: ${id}. `;
            log += postExistente ? `Elemento .EDYQHV[id-post="${id}"] encontrado. ` : `No se encontró .EDYQHV[id-post="${id}"]. `;
            log += publiValidas.length > 0 ? `${publiValidas.length} publicación(es) válida(s). ` : `No hay publicaciones válidas. `;

            if (postExistente && publiValidas.length > 0) {
                postExistente.outerHTML = publiValidas[0];
                log += `Publicación ${id} reemplazada. Reinicializando funciones y eventos. `;
                reiniciarFuncionesYEventos();
                reiniciarEventosPostTag();
            } else {
                log += `No se puede reemplazar la publicación ${id}. `;
                if (!postExistente) log += `Publicación no existe en el DOM. `;
                if (publiValidas.length === 0) log += `No hay publicaciones válidas. `;
            }
        } else if (publiValidas.length > 0) {
            log += `Insertando ${publiValidas.length} nuevas publicaciones. `;
            const pos = arriba ? 'afterbegin' : 'beforeend';
            listaPubli.insertAdjacentHTML(pos, publiValidas.join(''));
            paginaActual++;

            const publiEnDOM = listaPubli.querySelectorAll('.EDYQHV');
            const exceso = publiEnDOM.length - MAX_POSTS;
            if (exceso > 0) {
                log += `Excedido límite de publicaciones en ${exceso}. Eliminando antiguas. `;
                for (let i = 0; i < exceso; i++) {
                    const elim = publiEnDOM[i];
                    const idPubli = elim.getAttribute('id-post')?.trim();
                    if (idPubli) {
                        publicacionesCargadas.delete(idPubli);
                        log += `Eliminada publicación ${idPubli}. `;
                    }
                    elim.remove();
                }
            }

            log += `Reinicializando funciones y eventos. `;
            reiniciarFuncionesYEventos();
            reiniciarEventosPostTag();
        } else {
            log += `No hay publicaciones válidas. Deteniendo carga. `;
            detenerCarga();
        }

        //console.log('manejarContenido:', log);
    }

    function reiniciarFuncionesYEventos() {
        const funciones = ['inicializarWaveforms', 'empezarcolab', 'submenu', 'seguir', 'modalDetallesIA', 'tagsPosts', 'handleAllRequests', 'registrarVistas', 'colec', 'animacionLike', 'initTareas', 'initNotas'];
        funciones.forEach(func => {
            if (typeof window[func] === 'function') {
                window[func]();
            }
        });
    }

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
        let log = '';
        log += `Respuesta recibida: ${respuesta.substring(0, 100)}... `;

        const respuestaLimpia = respuesta.trim();
        if (respuestaLimpia === '<div id="no-more-posts"></div>') {
            log += `No hay más publicaciones. `;
            detenerCarga();
            //console.log('validarRespuesta: ', log);
            return null;
        }
        if (!respuestaLimpia) {
            log += `Respuesta vacía recibida. `;
            detenerCarga();
            //console.log('validarRespuesta: ', log);
            return null;
        }
        log += `Respuesta válida. Parseando a DOM. `;
        //console.log('validarRespuesta: ', log);
        return new DOMParser().parseFromString(respuesta, 'text/html');
    }

    function procesarPublicaciones(doc, force = false) {
        let log = '';
        const totalPostsInputFromResponse = doc.querySelector('.total-posts-sampleList');
        if (totalPostsInputFromResponse) {
            const totalPostsValue = totalPostsInputFromResponse.getAttribute('value');
            const totalPostsInputInDOM = document.querySelector('.total-posts-sampleList');
            if (totalPostsInputInDOM) {
                totalPostsInputInDOM.value = totalPostsValue;
                log += `Campo total-posts-sampleList actualizado desde la respuesta: ${totalPostsValue}. `;
                contadorDeSamples();
            }
        }

        const publicacionesNuevas = doc.querySelectorAll('.EDYQHV');
        const publicacionesValidas = [];

        if (publicacionesNuevas.length === 0) {
            log += `No se encontraron publicaciones nuevas en la respuesta. `;
            detenerCarga();
            //console.log('procesarPublicaciones: ', log);
            return [];
        }

        publicacionesNuevas.forEach(publicacion => {
            const idPublicacion = publicacion.getAttribute('id-post')?.trim();
            const existeEnDOM = document.querySelector(`.social-post-list .EDYQHV[id-post="${idPublicacion}"]`);

            if (idPublicacion) {
                log += `Procesando publicación con ID: ${idPublicacion}. `;

                if (!force) {
                    if (!publicacionesCargadas.has(idPublicacion)) {
                        log += `La publicación con ID ${idPublicacion} no está en el conjunto de publicaciones cargadas. `;
                    } else {
                        log += `La publicación con ID ${idPublicacion} ya está en el conjunto de publicaciones cargadas. `;
                    }

                    if (!existeEnDOM) {
                        log += `La publicación con ID ${idPublicacion} no existe en el DOM. `;
                    } else {
                        log += `La publicación con ID ${idPublicacion} existe en el DOM. `;
                    }
                }

                if (idPublicacion && (force || (!publicacionesCargadas.has(idPublicacion) && !existeEnDOM))) {
                    if (!force && (publicacionesCargadas.has(idPublicacion) || existeEnDOM)) {
                        log += `Aunque la publicación con ID ${idPublicacion} ya fue procesada o existe en el DOM, se procesará de nuevo debido a que force es true. `;
                    }

                    publicacionesCargadas.add(idPublicacion);
                    publicacionesValidas.push(publicacion.outerHTML);
                    log += `Publicación válida añadida: ${idPublicacion}. `;
                } else {
                    log += `Publicación duplicada o ya existente omitida: ${idPublicacion}. `;
                }
            } else {
                log += `Publicación sin ID, no se procesa. `;
            }
        });
        contadorDeSamples();
        //console.log('procesarPublicaciones: ', log);
        return publicacionesValidas;
    }

    // Parte 3: Insertar y manejar contenido en el DOM

    function reiniciarEventosPostTag() {
        log('Reiniciando eventos de clic mediante delegación en <span class="postTag">');
        configurarDelegacionEventosPostTag();
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

    window.reiniciarPost = async function (id, clase) {
        const respuesta = await window.reiniciarContenido(false, false, clase, false, null, id);
        return respuesta;
    };

    window.reiniciarContenido = async function (limpiar = true, arriba = false, clase = null, prioridad = false, callback = null, id = null) {
        let classClase = clase ? `clase-${clase}` : '';
        const lista = document.querySelector(`.tab.active .social-post-list.${classClase}`);
        let respuestaCompleta = null;

        if (!lista) {
            return {log: 'No se encontró la lista', respuestaCompleta};
        }

        publicacionesCargadas.clear();
        identificador = '';

        if (limpiar) {
            lista.innerHTML = '';
        }
        actualizarUIBusqueda('');
        resetearCarga();

        const {log, respuestaCompleta: respuesta} = await cargarMasContenido(lista, null, null, null, arriba, prioridad, id);
        respuestaCompleta = respuesta;

        if (typeof callback === 'function') {
            callback();
        }

        return {log, respuestaCompleta};
    };

    function resetearCarga() {
        log('Ejecutando resetearCarga');
        paginaActual = 1;
        publicacionesCargadas.clear();
        log('Carga reactivada en resetearCarga');
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
        window.removeEventListener('scroll', manejarScrollGlobal);
        window.removeEventListener('scroll', manejarScrollLista);
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
                    //console.error(data.data.message || 'Error desconocido.');
                }
            })
            .catch(error => {
                // Manejar errores de la solicitud
                resultadosElement.textContent = '0 resultados';
                //console.error('Error en la solicitud AJAX:', error);
            });
    }

    // Ejecutar la función al cargar la página
    if (resultadosElement) {
        contarPostsFiltrados();
    }
};
