async function establecerFiltros() {
    try {
        const res = await enviarAjax('obtenerFiltrosTotal');
        if (!res.success) {
            console.error('establecerFiltros: Error al obtener filtros:', res.data?.message || 'Error desconocido');
            return;
        }

        let {filtroPost, filtroTiempo} = res.data;

        if (typeof filtroPost === 'string') {
            try {
                filtroPost = JSON.parse(filtroPost);
            } catch (err) {
                console.error('establecerFiltros: Error al parsear filtroPost como JSON', err);
                filtroPost = {};
            }
        }

        if (Array.isArray(filtroPost)) {
            const temp = {};
            filtroPost.forEach(item => (temp[item] = true));
            filtroPost = temp;
        }

        filtroPost = typeof filtroPost !== 'object' || filtroPost === null ? {} : filtroPost;

        const hayFiltros = filtroTiempo !== 0 || Object.keys(filtroPost).length > 0;
        const btnRestablecer = document.querySelector('.restablecerBusqueda');
        const btnPost = document.querySelector('.postRestablecer');
        const btnColeccion = document.querySelector('.coleccionRestablecer');

        if (btnPost) btnPost.style.display = 'none';
        if (btnColeccion) btnColeccion.style.display = 'none';

        if (hayFiltros) {
            const filtrosPost = ['misPost', 'mostrarMeGustan', 'ocultarEnColeccion', 'ocultarDescargados'];
            const hayFiltrosPost = Object.keys(filtroPost).some(filtro => filtrosPost.includes(filtro));
            const hayFiltroColeccion = filtroPost.hasOwnProperty('misColecciones');

            if (hayFiltrosPost && btnPost) btnPost.style.display = 'block';
            if (hayFiltroColeccion && btnColeccion) btnColeccion.style.display = 'block';

            if (btnRestablecer && !btnRestablecer.dataset.listenerAdded) {
                const restablecerFiltro = async data => {
                    try {
                        const resRestablecer = await enviarAjax('restablecerFiltros', data);
                        if (resRestablecer.success) {
                            alert(resRestablecer.data.message);
                            window.reiniciarContenido();
                            window.recargarFiltros();
                            if (btnPost) btnPost.style.display = 'none';
                            if (btnColeccion) btnColeccion.style.display = 'none';
                        } else {
                            alert('Error: ' + (resRestablecer.data?.message || 'No se pudo restablecer'));
                        }
                    } catch (err) {
                        console.error('establecerFiltros: Error al restablecer:', err);
                        alert('Error en la solicitud.');
                    }
                };

                if (btnPost) {
                    btnPost.addEventListener('click', async () => await restablecerFiltro({post: true}));
                }

                if (btnColeccion) {
                    btnColeccion.addEventListener('click', async () => await restablecerFiltro({coleccion: true}));
                }

                btnRestablecer.dataset.listenerAdded = true;
            }
        }
    } catch (err) {
        console.error('establecerFiltros: Error en AJAX:', err);
    }
}

function getNombreFiltro(filtroTiempo) {
    const filtros = {
        0: 'Feed',
        1: 'Reciente',
        2: 'Semanal',
        3: 'Mensual'
    };
    const nombreFiltro = filtros[filtroTiempo] || 'Feed';
    return nombreFiltro;
}

async function actualizarBotonFiltro() {
    try {
        const res = await enviarAjax('obtenerFiltroActual', {});
        if (res.success) {
            const filtroActual = res.data.filtroTiempo;
            const nombreFiltro = res.data.nombreFiltro || getNombreFiltro(filtroActual);
            const botonFiltro = document.querySelector('.filtrosboton');
            if (botonFiltro) botonFiltro.innerHTML = `${nombreFiltro} ${FLECHA_SVG}`;
        }
    } catch (err) {
        console.error('Error en actualizarBotonFiltro:', err);
    }
}

async function cambiarFiltroTiempo() {
    const botonesFiltro = document.querySelectorAll('.filtroFeed, .filtroReciente, .filtroSemanal, .filtroMensual');
    if (!botonesFiltro) return;

    botonesFiltro.forEach(boton => {
        boton.addEventListener('click', async e => {
            e.preventDefault();
            const filtroTiempo = boton.classList.contains('filtroFeed') ? 0 : boton.classList.contains('filtroReciente') ? 1 : boton.classList.contains('filtroSemanal') ? 2 : boton.classList.contains('filtroMensual') ? 3 : 0;

            const res = await enviarAjax('guardarFiltro', {filtroTiempo});
            if (res.success) {
                botonesFiltro.forEach(btn => btn.classList.remove('filtroSelec'));
                boton.classList.add('filtroSelec');
                await actualizarBotonFiltro();
                window.reiniciarContenido();
                establecerFiltros();
            } else {
                console.error('Error al guardar el filtro:', res.message);
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
            act = r.success && r.data && r.data.filtros ? r.data.filtros : [];
            act.forEach(f => {
                const c = document.querySelector(`input[name="${f}"]`);
                if (c) c.checked = true;
            });
        } catch (error) {
            act = [];
        }
        window.filtrosGlobales = act;
    }

    function reiniciar() {
        checks.forEach(c => (c.checked = act.includes(c.name)));
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
        c.addEventListener('change', () => {
            act = this.checked ? [...(Array.isArray(act) ? act : []), this.name] : (Array.isArray(act) ? act : []).filter(f => f !== this.name);
            window.filtrosGlobales = act;
        });
    });

    const btnG = elem.querySelector('.botonprincipal');
    if (!btnG) {
        console.error("Error: No se encontró el botón con la clase 'botonprincipal'.");
        return;
    }

    btnG.addEventListener('click', async () => {
        const r = await enviarAjax('guardarFiltroPost', {filtros: JSON.stringify(Array.isArray(act) ? act : [])});
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

    btnR.addEventListener('click', async () => {
        act = [];
        checks.forEach(c => (c.checked = false));
        const r = await enviarAjax('guardarFiltroPost', {filtros: JSON.stringify([])});
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