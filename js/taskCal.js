// taskCal.js
// Se organizo el calendario en un archivo aparte para mejorar la organización

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
    trDiasSemana.innerHTML = '';
    calDiasSemanaCabecera.forEach(dia => {
        const th = document.createElement('th');
        th.textContent = dia;
        trDiasSemana.appendChild(th);
    });

    sfl.addEventListener('click', e => {
        e.stopPropagation();
        if (cal.style.display === 'block') {
            ocultarCal();
        } else {
            mostrarCal();
        }
    });

    calPrevBtn.addEventListener('click', () => {
        calMes--;
        if (calMes < 0) {
            calMes = 11;
            calAnio--;
        }
        renderCal();
    });

    calNextBtn.addEventListener('click', () => {
        calMes++;
        if (calMes > 11) {
            calMes = 0;
            calAnio++;
        }
        renderCal();
    });

    calHoyBtn.addEventListener('click', () => {
        const hoy = new Date();
        fechaLimite.valor = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`;
        inputFechaOculto.value = fechaLimite.valor;
        actSel(fechaLimite, fechaLimite.valor); // Ya no pasamos 'Sin fecha'
        calMes = hoy.getMonth();
        calAnio = hoy.getFullYear();
        renderCal();
        // ocultarCal(); // Descomentar si quieres que se cierre tras seleccionar "Hoy"
    });

    calBorrarBtn.addEventListener('click', () => {
        fechaLimite.valor = null;
        inputFechaOculto.value = '';
        actSel(fechaLimite, null); // No pasar 'Sin fecha', la función actSel ya lo maneja
        renderCal();
        ocultarCal();
    });

    document.addEventListener('click', event => {
        if (cal.style.display === 'block' && !cal.contains(event.target) && !sfl.contains(event.target)) {
            ocultarCal();
        }
    });
}

function mostrarCal() {
    const sfl = document.getElementById('sFechaLimite');
    const cal = document.getElementById('calCont');

    const rect = sfl.getBoundingClientRect();
    cal.style.top = rect.bottom + window.scrollY + 5 + 'px';
    cal.style.left = rect.left + window.scrollX + 'px';

    if (fechaLimite.valor) {
        const partes = fechaLimite.valor.split('-');
        calAnio = parseInt(partes[0]);
        calMes = parseInt(partes[1]) - 1;
    } else {
        const hoy = new Date();
        calAnio = hoy.getFullYear();
        calMes = hoy.getMonth();
    }

    cal.style.display = 'block';
    renderCal();
}

function ocultarCal() {
    const cal = document.getElementById('calCont');
    cal.style.display = 'none';
}

function renderCal() {
    const calMesAnioEl = document.getElementById('calMesAnio');
    const calBodyEl = document.getElementById('calBody');
    const inputFechaOculto = document.getElementById('inputFechaLimite');

    calMesAnioEl.textContent = `${calNombresMeses[calMes]} ${calAnio}`;
    calBodyEl.innerHTML = '';

    const primerDiaMes = new Date(calAnio, calMes, 1);
    const ultimoDiaMes = new Date(calAnio, calMes + 1, 0);
    const diasEnMes = ultimoDiaMes.getDate();

    let diaSemanaPrimerDia = primerDiaMes.getDay();
    if (diaSemanaPrimerDia === 0) diaSemanaPrimerDia = 6;
    else diaSemanaPrimerDia--;

    const hoy = new Date();
    const hoyStr = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`;

    let fechaActual = 1;
    for (let i = 0; i < 6; i++) {
        const fila = document.createElement('tr');
        for (let j = 0; j < 7; j++) {
            const celda = document.createElement('td');
            const divDia = document.createElement('div');
            divDia.classList.add('cal-dia');

            if (i === 0 && j < diaSemanaPrimerDia) {
                divDia.classList.add('cal-dia-fuera');
            } else if (fechaActual > diasEnMes) {
                divDia.classList.add('cal-dia-fuera');
            } else {
                const spanNum = document.createElement('span');
                spanNum.classList.add('cal-dia-num');
                spanNum.textContent = fechaActual;
                divDia.appendChild(spanNum);

                const fechaCompletaStr = `${calAnio}-${String(calMes + 1).padStart(2, '0')}-${String(fechaActual).padStart(2, '0')}`;
                celda.dataset.fecha = fechaCompletaStr;

                if (fechaCompletaStr === hoyStr) {
                    divDia.classList.add('cal-dia-hoy');
                }
                if (fechaLimite.valor === fechaCompletaStr) {
                    divDia.classList.add('cal-dia-sel');
                }

                celda.addEventListener('click', e => {
                    // Esta es la línea que mencionaste (aprox)
                    const fechaSel = e.currentTarget.dataset.fecha;
                    fechaLimite.valor = fechaSel;
                    inputFechaOculto.value = fechaSel;
                    inputFechaOculto.dispatchEvent(new Event('change'));

                    actSel(fechaLimite, fechaSel); // ¡Ahora actSel es global!
                    ocultarCal();
                });
                fechaActual++;
            }
            celda.appendChild(divDia);
            fila.appendChild(celda);
        }
        calBodyEl.appendChild(fila);
        if (fechaActual > diasEnMes && i >= Math.floor((diaSemanaPrimerDia + diasEnMes - 1) / 7)) break;
    }
}
