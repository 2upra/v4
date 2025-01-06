async function establecerFiltros() {
    //console.log('establecerFiltros: Inicio');
    try {
        const response = await enviarAjax('obtenerFiltrosTotal');
        //console.log('establecerFiltros: Respuesta de obtenerFiltrosTotal', response);
        if (response.success) {
            let {filtroPost, filtroTiempo} = response.data;

            // Verificar si filtroPost es una cadena y tratar de deserializarla como JSON
            if (typeof filtroPost === 'string') {
                try {
                    filtroPost = JSON.parse(filtroPost);
                    //console.log('establecerFiltros: filtroPost deserializado como JSON:', filtroPost);
                } catch (error) {
                    console.error('establecerFiltros: Error al parsear filtroPost como JSON', error);
                    filtroPost = {};
                }
            }

            // Si filtroPost es un array (posiblemente datos serializados), convertirlo a un objeto
            if (Array.isArray(filtroPost)) {
                const tempObj = {};
                filtroPost.forEach(item => {
                    tempObj[item] = true; // Asignar un valor genérico
                });
                filtroPost = tempObj;
                //console.log('establecerFiltros: filtroPost convertido de array a objeto:', filtroPost);
            }

            // Asegurarse de que filtroPost sea un objeto
            if (typeof filtroPost !== 'object' || filtroPost === null) {
                filtroPost = {};
                //console.log('establecerFiltros: filtroPost no era un objeto válido, inicializado como objeto vacío');
            }

            const hayFiltrosActivados = filtroTiempo !== 0 || Object.keys(filtroPost).length > 0;
            //console.log('establecerFiltros: Hay filtros activados:', hayFiltrosActivados);

            const botonRestablecer = document.querySelector('.restablecerBusqueda');
            const botonPostRestablecer = document.querySelector('.postRestablecer');
            const botonColeccionRestablecer = document.querySelector('.coleccionRestablecer');

            // Ocultar ambos botones por defecto
            if (botonPostRestablecer) {
                botonPostRestablecer.style.display = 'none';
                //console.log('establecerFiltros: Ocultando botonPostRestablecer');
            }
            if (botonColeccionRestablecer) {
                botonColeccionRestablecer.style.display = 'none';
                //console.log('establecerFiltros: Ocultando botonColeccionRestablecer');
            }

            if (hayFiltrosActivados) {
                //console.log('establecerFiltros: Hay filtros activos, procesando...');

                const filtrosPost = ['misPost', 'mostrarMeGustan', 'ocultarEnColeccion', 'ocultarDescargados'];
                const hayFiltrosPost = Object.keys(filtroPost).some(filtro => filtrosPost.includes(filtro));
                //console.log('establecerFiltros: hayFiltrosPost', hayFiltrosPost);
                const hayFiltroColeccion = filtroPost.hasOwnProperty('misColecciones');
                //console.log('establecerFiltros: hayFiltroColeccion', hayFiltroColeccion);

                // Mostrar el botón correspondiente si es necesario
                if (hayFiltrosPost && botonPostRestablecer) {
                    botonPostRestablecer.style.display = 'block';
                    //console.log('establecerFiltros: Mostrando botonPostRestablecer');
                }
                if (hayFiltroColeccion && botonColeccionRestablecer) {
                    botonColeccionRestablecer.style.display = 'block';
                    //console.log('establecerFiltros: Mostrando botonColeccionRestablecer');
                }

                // Evento para restablecer filtros
                if (botonRestablecer && !botonRestablecer.dataset.listenerAdded) {
                    //console.log('establecerFiltros: Agregando event listener a botonRestablecer');

                    // Función para restablecer filtros
                    const restablecerFiltro = async function (data) {
                        try {
                            //console.log('establecerFiltros: Enviando solicitud para restablecer filtros', data);
                            const restablecerResponse = await enviarAjax('restablecerFiltros', data);
                            //console.log('establecerFiltros: Respuesta de restablecerFiltros', restablecerResponse);
                            if (restablecerResponse.success) {
                                alert(restablecerResponse.data.message);
                                window.reiniciarContenido(); // Llamar a reiniciarContenido después del restablecimiento
                                window.recargarFiltros();

                                if (botonPostRestablecer) {
                                    botonPostRestablecer.style.display = 'none';
                                    //console.log('establecerFiltros: Ocultando botonPostRestablecer tras restablecer');
                                }
                                if (botonColeccionRestablecer) {
                                    botonColeccionRestablecer.style.display = 'none';
                                    //console.log('establecerFiltros: Ocultando botonColeccionRestablecer tras restablecer');
                                }
                            } else {
                                alert('Error: ' + (restablecerResponse.data?.message || 'No se pudo restablecer'));
                            }
                        } catch (error) {
                            console.error('establecerFiltros: Error al restablecer:', error);
                            alert('Error en la solicitud.');
                        }
                    };

                    // Evento click para botón de post
                    if (botonPostRestablecer) {
                        botonPostRestablecer.addEventListener('click', async function () {
                            //console.log('establecerFiltros: Evento click en botonPostRestablecer');
                            await restablecerFiltro({post: true});
                        });
                    }

                    // Evento click para botón de coleccion
                    if (botonColeccionRestablecer) {
                        botonColeccionRestablecer.addEventListener('click', async function () {
                            //console.log('establecerFiltros: Evento click en botonColeccionRestablecer');
                            await restablecerFiltro({coleccion: true});
                        });
                    }

                    botonRestablecer.dataset.listenerAdded = true;
                    //console.log('establecerFiltros: Listener agregado');
                }
            }
        } else {
            console.error('establecerFiltros: Error al obtener filtros:', response.data?.message || 'Error desconocido');
        }
    } catch (error) {
        console.error('establecerFiltros: Error en AJAX:', error);
    }
    //console.log('establecerFiltros: Fin');
}

// Función para obtener el nombre del filtro según el valor
function getNombreFiltro(filtroTiempo) {
    const filtros = {
        0: 'Feed',
        1: 'Reciente',
        2: 'Semanal',
        3: 'Mensual'
    };
    //console.log('Valor de filtroTiempo recibido:', filtroTiempo);
    //console.log('Tipo de filtroTiempo:', typeof filtroTiempo);
    const nombreFiltro = filtros[filtroTiempo] || 'Feed';
    //console.log('Nombre de filtro seleccionado:', nombreFiltro);
    return nombreFiltro;
}

// Función para actualizar el texto del botón

async function actualizarBotonFiltro() {
    //console.log('Iniciando actualizarBotonFiltro');
    try {
        const response = await enviarAjax('obtenerFiltroActual', {});
        //console.log('Respuesta completa del servidor:', response);

        if (response.success) {
            // Corregimos el acceso a los datos
            const filtroActual = response.data.filtroTiempo;
            //console.log('Filtro actual obtenido:', filtroActual);

            // También podríamos usar directamente el nombreFiltro que viene del servidor
            const nombreFiltro = response.data.nombreFiltro || getNombreFiltro(filtroActual);
            //console.log('Nombre del filtro obtenido:', nombreFiltro);

            const botonFiltro = document.querySelector('.filtrosboton');
            //console.log('Botón encontrado:', botonFiltro);

            if (botonFiltro) {
                const nuevoContenido = `${nombreFiltro} ${FLECHA_SVG}`;
                //console.log('Nuevo contenido del botón:', nuevoContenido);
                botonFiltro.innerHTML = nuevoContenido;
            }
        } else {
            //console.log('La respuesta no fue exitosa:', response);
        }
    } catch (error) {
        console.error('Error en actualizarBotonFiltro:', error);
    }
}

// Modificar la función cambiarFiltroTiempo para actualizar el botón
async function cambiarFiltroTiempo() {
    const filtroButtons = document.querySelectorAll('.filtroFeed, .filtroReciente, .filtroSemanal, .filtroMensual');

    if (!filtroButtons) {
        //console.log('No se encontraron botones de filtro');
        return;
    }

    filtroButtons.forEach(button => {
        button.addEventListener('click', async event => {
            event.preventDefault();

            let filtroTiempo;
            if (button.classList.contains('filtroFeed')) {
                filtroTiempo = 0;
            } else if (button.classList.contains('filtroReciente')) {
                filtroTiempo = 1;
            } else if (button.classList.contains('filtroSemanal')) {
                filtroTiempo = 2;
            } else if (button.classList.contains('filtroMensual')) {
                filtroTiempo = 3;
            } else {
                filtroTiempo = 0;
            }

            //console.log('Enviando filtroTiempo:', filtroTiempo);

            const resultado = await enviarAjax('guardarFiltro', {filtroTiempo: filtroTiempo});
            //console.log('Resultado:', resultado);

            if (resultado.success) {
                filtroButtons.forEach(btn => btn.classList.remove('filtroSelec'));
                button.classList.add('filtroSelec');
                await actualizarBotonFiltro(); // Actualizar el botón después de cambiar el filtro
                window.reiniciarContenido();
                establecerFiltros();
            } else {
                console.error('Error al guardar el filtro:', resultado.message);
            }
        });
    });
}

function filtrosPost() {
    window.filtrosGlobales = [];
    const elem = document.getElementById('filtrosPost');
    if (!elem) {
        console.error("Error: No se encontró el elemento 'filtrosPost'.");
        return;
    }
    let act = [];
    const checks = elem.querySelectorAll('input[type="checkbox"]');

    async function cargar() {
        try {
            const r = await enviarAjax('obtenerFiltros');
            if (r.success && r.data && r.data.filtros) {
                act = r.data.filtros;
            } else {
                act = [];
            }
            if (Array.isArray(act)) {
                act.forEach(f => {
                    const c = document.querySelector(`input[name="${f}"]`);
                    if (c) {
                        c.checked = true;
                    }
                });
            }
        } catch (error) {
            act = [];
        }
        window.filtrosGlobales = act;
    }

    function reiniciar() {
        checks.forEach(c => {
            c.checked = act.includes(c.name);
        });
    }

    async function recargar() {
        await cargar();
        reiniciar();
        window.filtrosGlobales = act;
    }

    if (!checks.length) {
        console.error("Error: No se encontraron checkboxes dentro de 'filtrosPost'.");
        return;
    }

    checks.forEach(c => {
        c.addEventListener('change', function () {
            if (!Array.isArray(act)) {
                act = [];
            }
            if (this.checked) {
                if (!act.includes(this.name)) {
                    act.push(this.name);
                }
            } else {
                act = act.filter(f => f !== this.name);
            }
            window.filtrosGlobales = act;
        });
    });

    const btnG = elem.querySelector('.botonprincipal');
    if (!btnG) {
        console.error("Error: No se encontró el botón con la clase 'botonprincipal'.");
        return;
    }

    btnG.addEventListener('click', async function () {
        const g = Array.isArray(act) ? act : [];
        const r = await enviarAjax('guardarFiltroPost', {
            filtros: JSON.stringify(g)
        });
        if (r.success) {
            window.reiniciarContenido();
            establecerFiltros();
        } else {
            console.error('Error al guardar los filtros.');
        }
        window.filtrosGlobales = act;
    });

    const btnR = elem.querySelector('.botonsecundario');
    if (!btnR) {
        console.error("Error: No se encontró el botón con la clase 'botonsecundario'.");
        return;
    }

    btnR.addEventListener('click', async function () {
        act = [];
        checks.forEach(c => {
            c.checked = false;
        });
        const r = await enviarAjax('guardarFiltroPost', {
            filtros: JSON.stringify([])
        });
        if (r.success) {
            window.reiniciarContenido();
            establecerFiltros();
        } else {
            console.error('Error al restablecer los filtros.');
        }
        window.filtrosGlobales = act;
    });

    cargar();
    window.recargarFiltros = recargar;
}


const FLECHA_SVG = '<svg data-testid="geist-icon" height="16" stroke-linejoin="round" viewBox="0 0 16 16" width="16" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.7071 2.39644C8.31658 2.00592 7.68341 2.00592 7.29289 2.39644L4.46966 5.21966L3.93933 5.74999L4.99999 6.81065L5.53032 6.28032L7.99999 3.81065L10.4697 6.28032L11 6.81065L12.0607 5.74999L11.5303 5.21966L8.7071 2.39644ZM5.53032 9.71966L4.99999 9.18933L3.93933 10.25L4.46966 10.7803L7.29289 13.6035C7.68341 13.9941 8.31658 13.9941 8.7071 13.6035L11.5303 10.7803L12.0607 10.25L11 9.18933L10.4697 9.71966L7.99999 12.1893L5.53032 9.71966Z" fill="currentColor"></path></svg>';