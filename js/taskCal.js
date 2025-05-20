// taskCal.js

window.initCal = function () {
    const sfl = document.getElementById('sFechaLimite');
    const cal = document.getElementById('calCont');
    const calPrevBtn = document.getElementById('calPrev');
    const calNextBtn = document.getElementById('calNext');
    const calHoyBtn = document.getElementById('calHoyBtn');
    const calBorrarBtn = document.getElementById('calBorrarBtn');
    const inputFechaOculto = document.getElementById('inputFechaLimite');

    if (!sfl || !cal || !calPrevBtn || !calNextBtn || !calHoyBtn || !calBorrarBtn || !inputFechaOculto) {
        console.error('initCal: Faltan elementos del calendario en el DOM.');
        return;
    }

    const trDiasSemana = document.getElementById('calDiasSemana');
    if (trDiasSemana.innerHTML === '') {
        calDiasSemanaCabecera.forEach(dia => {
            const th = document.createElement('th');
            th.textContent = dia;
            trDiasSemana.appendChild(th);
        });
    }

    const sflClickHandler = sfl._sflClickHandler;
    if (sflClickHandler) sfl.removeEventListener('click', sflClickHandler);

    sfl._sflClickHandler = e => {
        e.stopPropagation();
        window.hideAllOpenTaskMenus();

        contextoCalendario = {
            esParaTareaEspecifica: false,
            idTarea: null,
            elementoSpanTexto: null,
            elementoLiTarea: null,
            elementoDisparador: sfl,
            tipoFecha: 'limite' // Selector global es para fechaLimite
        };

        if (cal.style.display === 'block') {
            ocultarCal();
        } else {
            mostrarCal(sfl, fechaLimite.valor);
        }
    };
    sfl.addEventListener('click', sfl._sflClickHandler);

    calPrevBtn.onclick = () => {
        calMes--;
        if (calMes < 0) {
            calMes = 11;
            calAnio--;
        }
        renderCal();
    };
    calNextBtn.onclick = () => {
        calMes++;
        if (calMes > 11) {
            calMes = 0;
            calAnio++;
        }
        renderCal();
    };

    calHoyBtn.onclick = () => {
        const hoy = new Date();
        const fechaHoyStr = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`;

        if (contextoCalendario.esParaTareaEspecifica) {
            if (contextoCalendario.tipoFecha === 'proxima') {
                actualizarFechaProximaHabitoServidorUI(contextoCalendario.idTarea, fechaHoyStr, contextoCalendario.elementoSpanTexto, contextoCalendario.elementoLiTarea);
            } else {
                // 'limite'
                actualizarFechaLimiteTareaServidorUI(contextoCalendario.idTarea, fechaHoyStr, contextoCalendario.elementoSpanTexto, contextoCalendario.elementoLiTarea);
            }
        } else {
            fechaLimite.valor = fechaHoyStr;
            if (inputFechaOculto) inputFechaOculto.value = fechaLimite.valor;
            actSel(fechaLimite, fechaLimite.valor);
            calMes = hoy.getMonth();
            calAnio = hoy.getFullYear();
            renderCal();
        }
        ocultarCal();
    };

    calBorrarBtn.onclick = () => {
        if (contextoCalendario.esParaTareaEspecifica) {
            if (contextoCalendario.tipoFecha === 'proxima') {
                actualizarFechaProximaHabitoServidorUI(contextoCalendario.idTarea, null, contextoCalendario.elementoSpanTexto, contextoCalendario.elementoLiTarea);
            } else {
                // 'limite'
                actualizarFechaLimiteTareaServidorUI(contextoCalendario.idTarea, null, contextoCalendario.elementoSpanTexto, contextoCalendario.elementoLiTarea);
            }
        } else {
            fechaLimite.valor = null;
            if (inputFechaOculto) inputFechaOculto.value = '';
            actSel(fechaLimite, null);
            renderCal();
        }
        ocultarCal();
    };
};

// MODIFICADA: `mostrarCal` ahora toma el elemento de referencia y la fecha actual para ese contexto.
function mostrarCal(elementoRef, fechaActualISO) {
    const cal = document.getElementById('calCont');
    if (!cal || !elementoRef) return;

    const rect = elementoRef.getBoundingClientRect();
    cal.style.top = rect.bottom + window.scrollY + 5 + 'px';
    cal.style.left = rect.left + window.scrollX + 'px';

    let usarFechaActualPredeterminada = true; // Asumimos que usaremos la fecha actual por defecto

    if (fechaActualISO) {
        // Intentar parsear la fechaActualISO
        const partes = fechaActualISO.split('-');
        if (partes.length === 3) {
            const anioParseado = parseInt(partes[0], 10);
            const mesParseado0Index = parseInt(partes[1], 10) - 1; // JS usa meses 0-11

            // Validar que el año y mes parseados sean utilizables para el calendario.
            // Un año como 0 (de "0000-xx-xx") o un mes fuera de 0-11 no son válidos.
            if (anioParseado > 0 && mesParseado0Index >= 0 && mesParseado0Index <= 11) {
                calAnio = anioParseado;
                calMes = mesParseado0Index;
                usarFechaActualPredeterminada = false; // Se pudo parsear una fecha válida, no usar la actual
            }
            // Si el año es 0, o el mes es inválido, usarFechaActualPredeterminada sigue siendo true.
        }
        // Si las partes no son 3 (formato incorrecto), usarFechaActualPredeterminada sigue siendo true.
    }
    // Si fechaActualISO era null, undefined o vacía, if (fechaActualISO) es falso,
    // y usarFechaActualPredeterminada sigue siendo true.

    if (usarFechaActualPredeterminada) {
        const hoy = new Date();
        calAnio = hoy.getFullYear();
        calMes = hoy.getMonth();
    }

    cal.style.display = 'block';
    renderCal(); // renderCal usará calAnio y calMes globales

    // El resto de tu lógica para el listener de clic fuera permanece igual
    if (!document._calClickListener) {
        document._calClickListener = event => {
            if (cal.style.display === 'block' && !cal.contains(event.target) && contextoCalendario.elementoDisparador && !contextoCalendario.elementoDisparador.contains(event.target)) {
                ocultarCal();
            }
        };
        setTimeout(() => document.addEventListener('click', document._calClickListener), 0);
    }
}
function ocultarCal() {
    const cal = document.getElementById('calCont');
    if (cal) cal.style.display = 'none';

    if (document._calClickListener) {
        document.removeEventListener('click', document._calClickListener);
        document._calClickListener = null;
    }
    contextoCalendario = {
        esParaTareaEspecifica: false,
        idTarea: null,
        elementoSpanTexto: null,
        elementoLiTarea: null,
        elementoDisparador: null,
        tipoFecha: null // Reseteamos tipoFecha
    };
}

function renderCal() {
    const calMesAnioEl = document.getElementById('calMesAnio');
    const calBodyEl = document.getElementById('calBody');

    if (!calMesAnioEl || !calBodyEl) return;

    calMesAnioEl.textContent = `${calNombresMeses[calMes]} ${calAnio}`;
    calBodyEl.innerHTML = '';

    const primerDiaMes = new Date(calAnio, calMes, 1);
    const diasEnMes = new Date(calAnio, calMes + 1, 0).getDate();
    let diaSemanaPrimerDia = primerDiaMes.getDay();
    diaSemanaPrimerDia = diaSemanaPrimerDia === 0 ? 6 : diaSemanaPrimerDia - 1;

    const hoy = new Date();
    const hoyStr = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`;

    let fechaSeleccionadaActual = null;
    if (contextoCalendario.esParaTareaEspecifica && contextoCalendario.elementoLiTarea) {
        if (contextoCalendario.tipoFecha === 'proxima') {
            fechaSeleccionadaActual = contextoCalendario.elementoLiTarea.dataset.proxima;
        } else {
            // 'limite'
            fechaSeleccionadaActual = contextoCalendario.elementoLiTarea.dataset.fechalimite;
        }
    } else if (!contextoCalendario.esParaTareaEspecifica && contextoCalendario.tipoFecha === 'limite') {
        fechaSeleccionadaActual = fechaLimite.valor; // Selector global
    }

    let fechaActualDia = 1;
    for (let i = 0; i < 6; i++) {
        const fila = document.createElement('tr');
        for (let j = 0; j < 7; j++) {
            const celda = document.createElement('td');
            const divDia = document.createElement('div');
            divDia.classList.add('cal-dia');

            if ((i === 0 && j < diaSemanaPrimerDia) || fechaActualDia > diasEnMes) {
                divDia.classList.add('cal-dia-fuera');
            } else {
                const spanNum = document.createElement('span');
                spanNum.classList.add('cal-dia-num');
                spanNum.textContent = fechaActualDia;
                divDia.appendChild(spanNum);

                const fechaCompletaStr = `${calAnio}-${String(calMes + 1).padStart(2, '0')}-${String(fechaActualDia).padStart(2, '0')}`;
                celda.dataset.fecha = fechaCompletaStr;

                if (fechaCompletaStr === hoyStr) divDia.classList.add('cal-dia-hoy');
                if (fechaSeleccionadaActual === fechaCompletaStr) divDia.classList.add('cal-dia-sel');

                celda.onclick = e => {
                    const fechaSel = e.currentTarget.dataset.fecha;
                    const inputFechaOculto = document.getElementById('inputFechaLimite');

                    if (contextoCalendario.esParaTareaEspecifica) {
                        if (contextoCalendario.tipoFecha === 'proxima') {
                            actualizarFechaProximaHabitoServidorUI(contextoCalendario.idTarea, fechaSel, contextoCalendario.elementoSpanTexto, contextoCalendario.elementoLiTarea);
                        } else {
                            // 'limite'
                            actualizarFechaLimiteTareaServidorUI(contextoCalendario.idTarea, fechaSel, contextoCalendario.elementoSpanTexto, contextoCalendario.elementoLiTarea);
                        }
                    } else if (!contextoCalendario.esParaTareaEspecifica && contextoCalendario.tipoFecha === 'limite') {
                        fechaLimite.valor = fechaSel;
                        if (inputFechaOculto) inputFechaOculto.value = fechaSel;
                        actSel(fechaLimite, fechaSel);
                    }
                    ocultarCal();
                };
                fechaActualDia++;
            }
            celda.appendChild(divDia);
            fila.appendChild(celda);
        }
        calBodyEl.appendChild(fila);
        if (fechaActualDia > diasEnMes && i >= Math.floor((diaSemanaPrimerDia + diasEnMes - 1) / 7)) break;
    }
}
