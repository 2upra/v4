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
                ////console.log('Caso 1: Una sola lista');
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
            ////console.log('Cargando contenido en lista:', lista.id);
            precargarContenido(lista, tabActivo);
        }
    }

    function manejarScrollVentana(tabActivo, lista) {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const alturaVentana = window.innerHeight;
        const alturaDocumento = Math.max(document.body.scrollHeight, document.body.offsetHeight, document.documentElement.clientHeight, document.documentElement.scrollHeight, document.documentElement.offsetHeight);

        if (scrollTop + alturaVentana > alturaDocumento - 100 && !estaCargando && hayMasContenido) {
            ////console.log('Cargando contenido en caso 1');
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

    window.reiniciarPost = async function (id, clase) {
        let log = `reiniciarPost: Iniciando reinicio del post con ID ${id} y clase ${clase}.`;
        try {
            const respuesta = await window.reiniciarContenido(false, false, clase, false, null, id);
            log += ' reiniciarPost: Finalizado.';
            //console.log(log);
            return respuesta;
        } catch (error) {
            log += ` Error en reiniciarPost: ${error}`;
            console.error(log);
            return null;
        }
    };

    window.reiniciarContenido = async function (limpiar = true, arriba = false, clase = null, prioridad = false, callback = null, id = null) {
        let log = `reiniciarContenido: Iniciando reinicio de contenido. limpiar=${limpiar}, arriba=${arriba}, clase=${clase}, prioridad=${prioridad}, id=${id}.`;

        // Modificación: Si clase no está definido, se seleccionan todos los .social-post-list
        let listas;
        if (clase) {
            let classClase = `clase-${clase}`;
            listas = document.querySelectorAll(`.tab.active .social-post-list.${classClase}`);
        } else {
            listas = document.querySelectorAll(`.tab.active .social-post-list`);
        }

        let respuestaCompleta = null;

        if (!listas || listas.length === 0) {
            log += ' No se encontró la lista.';
            console.error(log);
            return null;
        }

        // Iterar sobre cada lista encontrada
        for (let lista of listas) {
            // Eliminar elementos no-(clase) si clase no es null
            if (clase) {
                const elementosAEliminar = lista.querySelectorAll(`.LNVHED:not(.clase-${clase})`);
                elementosAEliminar.forEach(elemento => {
                    elemento.remove();
                });
                log += ` Eliminados elementos que no coinciden con la clase ${clase} en una de las listas.`;
            }

            publicacionesCargadas.clear();
            identificador = '';

            if (limpiar) {
                lista.innerHTML = '';
                log += ' Lista limpiada.';
            }

            actualizarUIBusqueda('');
            resetearCarga();

            try {
                const resultadoCarga = await cargarMasContenido(lista, null, null, null, arriba, prioridad, id);
                // Se actualiza respuestaCompleta con el último resultado
                respuestaCompleta = resultadoCarga;

                log += ' reiniciarContenido: Contenido cargado exitosamente en una de las listas.';

                if (typeof callback === 'function') {
                    log += ' Ejecutando callback.';
                    callback();
                }
            } catch (error) {
                log += ` Error en cargarMasContenido: ${error}`;
                console.error(log);
                // Considera si quieres detener la ejecución aquí o continuar con las otras listas
                return null; // Descomenta esto si quieres detener la ejecución en caso de error
            }
        }

        log += ' reiniciarContenido: Finalizado.';
        //console.log(log);
        return respuestaCompleta;
    };

    async function cargarMasContenido(listaPublicaciones, ajax = null, colec = null, idea = null, arriba = false, prioridad = false, id = null) {
        let log = '';
        let respuestaCompleta = null;

        if (estaCargando) {
            log += 'La función ya está en ejecución.\n';
            console.log(log);
            return null;
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
            await new Promise(resolve => setTimeout(resolve, 1000));
            listaPublicaciones = document.querySelector(`.tab.active .social-post-list.clase-${posttype}`);
            ({filtro = '', tabId = '', posttype = ''} = listaPublicaciones ? listaPublicaciones.dataset : {});
        }

        if (!listaPublicaciones) {
            log += 'No se encontró listaPublicaciones después de varios intentos.\n';
            estaCargando = false;
            console.log(log);
            return null;
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
            respuestaCompleta = textoRespuesta;
            await procesarRespuesta(textoRespuesta, listaPublicaciones, arriba, id);
            reiniciarFuncionesYEventos();
            reiniciarEventosPostTag();
        } catch (error) {
            log += `Error en la petición AJAX: ${error}.\n`;
        } finally {
            estaCargando = false;
            log += 'La función ha finalizado.\n';
            console.log(log);
        }

        return respuestaCompleta;
    }

    async function procesarRespuesta(respuesta, listaPublicaciones, arriba = false, id = null) {
        let log = `Iniciando procesarRespuesta. arriba: ${arriba}, id: ${id}\n`;
        const doc = validarRespuesta(respuesta);
        if (!doc) {
            log += 'Respuesta no válida o error en la validación.\n';
            eliminarMarcadorCarga(listaPublicaciones);
            console.log(log);
            return;
        }

        let force = false;

        if (id !== null) {
            force = true;
        }

        log += `Forzar actualización: ${force}\n`;

        const publicacionesValidas = procesarPublicaciones(doc, force);
        log += `Publicaciones válidas encontradas: ${publicacionesValidas.length}\n`;

        manejarContenido(publicacionesValidas, listaPublicaciones, arriba, id);
        log += 'Función manejarContenido completada.\n';

        eliminarMarcadorCarga(listaPublicaciones);
        log += 'Marcador de carga eliminado.\n';
        console.log(log);
    }

    function manejarContenido(publiValidas, listaPubli, arriba = false, id = null) {
        let log = '';
        if (id) {
            const postExistente = listaPubli.querySelector(`.EDYQHV[id-post="${id}"]`);
            log += `Se recibió ID: ${id}. `;
            log += postExistente ? `Elemento .EDYQHV[id-post="${id}"] encontrado. ` : `No se encontró .EDYQHV[id-post="${id}"]. `;
            log += publiValidas.length > 0 ? `${publiValidas.length} publicación(es) válida(s). ` : `No hay publicaciones válidas. `;

            if (postExistente && publiValidas.length > 0) {
                postExistente.outerHTML = publiValidas[0];
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
        } else {
            log += `No hay publicaciones válidas. Deteniendo carga. `;
            detenerCarga();
        }

        console.log('manejarContenido:', log);
    }

    function configurarEventoBusqueda() {
        const inputBusqueda = document.getElementById('identifier');
        const botonesBusqueda = document.querySelectorAll('.buttonBI, .buttonBuscar');

        if (inputBusqueda) {
            inputBusqueda.removeEventListener('keydown', manejadorEventoBusqueda);
            inputBusqueda.addEventListener('keydown', manejadorEventoBusqueda);
        }

        if (botonesBusqueda.length > 0) {
            botonesBusqueda.forEach(boton => {
                boton.removeEventListener('click', manejadorEventoBusqueda);
                boton.addEventListener('click', manejadorEventoBusqueda);
            });
        }
    }


    function manejadorEventoBusqueda(e) {
        let log = 'manejadorEventoBusqueda: ';
        const esEnter = e.type === 'keydown' && (e.key === 'Enter' || e.keyCode === 13);
        const esBoton = e.type === 'click' && (e.target.classList.contains('buttonBI') || e.target.classList.contains('buttonBuscar'));

        if (!esEnter && !esBoton) {
            log += `Evento no manejado: ${e.type}`;
            console.log(log);
            return;
        }

        if (esEnter) e.preventDefault();
        log += esEnter ? 'Enter presionado. ' : 'Click en botón. ';

        const listaMomento = document.querySelector('ul.social-post-list.clase-momento[data-filtro="momento"][data-posttype="social_post"][data-tab-id="Samples"]');
        const divMomento = document.querySelector('div.divmomento.artista'); // Busca el div

        if (listaMomento) {
            listaMomento.remove();
            log += 'Lista momento eliminada. ';
        }

        if (divMomento) {
            divMomento.remove();
            log += 'Div momento eliminado. ';
        }

        const listas = document.querySelectorAll('.social-post-list');
        if (!listaMomento) {
            let listasLimpias = 0;
            listas.forEach(l => {
                l.innerHTML = '';
                listasLimpias++;
            });
            log += `Listas limpias: ${listasLimpias}. `;
        }

        const input = document.getElementById('identifier');
        identificador = input.value.trim();

        if (identificador === '') {
            log += 'Campo vacío.';
            console.log(log);
            return;
        }

        if (!listaMomento) {
            actualizarUIBusqueda(identificador);
            resetearCarga();
            log += 'Búsqueda y carga reseteada. ';
        }

        const listaActiva = document.querySelector('.tab.active .social-post-list');
        if (listaActiva && !listaMomento) {
            cargarMasContenido(listaActiva);
            log += 'cargarMasContenido llamada.';
        } else {
            log += 'No lista activa o lista momento existia.';
        }

        //console.log(log);
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
            ////console.log('validarRespuesta: ', log);
            return null;
        }
        if (!respuestaLimpia) {
            log += `Respuesta vacía recibida. `;
            detenerCarga();
            ////console.log('validarRespuesta: ', log);
            return null;
        }
        log += `Respuesta válida. Parseando a DOM. `;
        ////console.log('validarRespuesta: ', log);
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
            ////console.log('procesarPublicaciones: ', log);
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
        ////console.log('procesarPublicaciones: ', log);
        return publicacionesValidas;
    }

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
                    return;
                }

                const listaMomento = document.querySelector('ul.social-post-list.clase-momento[data-filtro="momento"][data-posttype="social_post"][data-tab-id="Samples"]');
                const divMomento = document.querySelector('div.divmomento.artista'); // Busca el div
        
                if (listaMomento) {
                    listaMomento.remove();
                }
        
                if (divMomento) {
                    divMomento.remove();
                }
        
                const listas = document.querySelectorAll('.social-post-list');
                if (!listaMomento) {
                    let listasLimpias = 0;
                    listas.forEach(l => {
                        l.innerHTML = '';
                        listasLimpias++;
                    });
                }
        
                const input = document.getElementById('identifier');
                identificador = input.value.trim();
        
                if (identificador === '') {
                    console.log(log);
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

    function resetearCarga() {
        paginaActual = 1;
        publicacionesCargadas.clear();
        window.scrollTo(0, 0);
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
        let urlCompleta = window.location.href;
        let posicionHash = urlCompleta.indexOf('#');
        let queryString = '';

        if (posicionHash !== -1) {
            queryString = urlCompleta.substring(urlCompleta.indexOf('?'), posicionHash);
        } else {
            queryString = urlCompleta.substring(urlCompleta.indexOf('?'));
        }

        if (!queryString) {
            //Verificamos que exista algo en el query
            return '';
        }

        const params = new URLSearchParams(queryString);
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
