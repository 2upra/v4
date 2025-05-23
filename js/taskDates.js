// js/taskDates.js

let calMes;
let calAnio;
const calNombresMeses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
const calDiasSemanaCabecera = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do'];

let contextoCalendario = {
    esParaTareaEspecifica: false,
    idTarea: null,
    elementoSpanTexto: null,
    elementoLiTarea: null,
    elementoDisparador: null,
    tipoFecha: null // Nuevo: 'limite' o 'proxima'
};

window.iniciarManejadoresFechaLimiteMeta = function() {
    document.querySelectorAll('.divFechaLimite[data-tarea]').forEach(div => {
        const listenerExistente = div._manejadorClicFechaLimiteMeta;
        if (listenerExistente) div.removeEventListener('click', listenerExistente);

        div._manejadorClicFechaLimiteMeta = function (event) {
            event.stopPropagation();
            window.hideAllOpenTaskMenus();

            const tareaId = this.dataset.tarea;
            const liTarea = this.closest('.POST-tarea');
            const fechaActual = liTarea ? liTarea.dataset.fechalimite : null;
            const spanTexto = this.querySelector('.textoFechaLimite');

            contextoCalendario = {
                esParaTareaEspecifica: true,
                idTarea: tareaId,
                elementoSpanTexto: spanTexto,
                elementoLiTarea: liTarea,
                elementoDisparador: this,
                tipoFecha: 'limite' // Especificamos que es para fechaLimite
            };

            mostrarCal(this, fechaActual || null);
        };

        div.addEventListener('click', div._manejadorClicFechaLimiteMeta);
    });
}

window.actualizarFechaLimiteTareaServidorUI = async function(idTarea, nuevaFechaISO, spanDelIconoDisparador, liTarea) {
    // spanDelIconoDisparador y liTarea no se usarán activamente si reinicias el post,
    // pero los mantenemos por si alguna lógica futura los necesita o para consistencia.
    const datos = {tareaId: idTarea, fechaLimite: nuevaFechaISO};
    let logBase = `actualizarFechaLimiteTareaServidorUI: Tarea ${idTarea}, `;
    logBase += nuevaFechaISO ? `FechaNueva "${nuevaFechaISO}"` : 'Fecha Borrada';

    try {
        const rta = await enviarAjax('modificarFechaLimiteTarea', datos);
        let logDetalles = '';

        if (rta.success) {
            logDetalles += 'Servidor OK. ';

            // No necesitamos actualizar el dataset del liTarea o el atributo 'dif' manualmente aquí,
            // porque reiniciarPost() obtendrá la información más reciente del servidor.
            // Tampoco necesitamos tocar el spanDelIconoDisparador ni el display de fecha real.

            // Llamamos a reiniciarPost para actualizar toda la tarea.
            // Asumimos que 'idTarea' es el mismo que se necesita para reiniciarPost.
            // Si window.reiniciarPost es asíncrono, puedes usar await.
            // Si es síncrono o no devuelve una promesa que necesitemos esperar, no hace falta await.
            await window.reiniciarPost(idTarea, 'tarea');
            logDetalles += `Se llamó a reiniciarPost(${idTarea}, 'tarea') para actualizar UI.`;

            console.log(logBase + '. ' + logDetalles);
        } else {
            logDetalles = `Error Servidor: ${rta.data || 'Desconocido'}`;
            console.error(logBase + '. ' + logDetalles);
            // Considera si quieres mostrar un alert aquí, ya que reiniciarPost no se llamará.
            alert('Error al actualizar fecha límite en servidor: ' + (rta.data || 'Error desconocido'));
        }
    } catch (error) {
        const logError = `Excepción AJAX. Error: ${error.message || error}`;
        console.error(logBase + '. ' + logError);
        alert('Error de conexión al actualizar fecha límite.');
    }
}

// NUEVA FUNCIÓN (JS): Equivalente a tu calcularTextoTiempo de PHP
window.calcularTextoTiempoJS = function(fechaReferenciaISO) {
    // YYYY-MM-DD o null
    if (!fechaReferenciaISO) return {txt: '', simbolo: '', claseNeg: ''};

    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0); // Normalizar a medianoche

    // Crear fechaReferencia también a medianoche para comparación correcta de días
    const [anio, mes, dia] = fechaReferenciaISO.split('-').map(Number);
    const fechaRef = new Date(anio, mes - 1, dia, 0, 0, 0, 0);

    const difMs = fechaRef.getTime() - hoy.getTime();
    const difDias = Math.round(difMs / (1000 * 60 * 60 * 24));

    let txt = '',
        simbolo = '',
        claseNeg = '';
    if (difDias === 0) txt = 'Hoy';
    else if (difDias === 1) txt = 'Mañana';
    else if (difDias === -1) {
        txt = 'Ayer';
        claseNeg = 'diaNegativo';
    } else if (difDias > 1) txt = difDias + 'd';
    else if (difDias < -1) {
        txt = Math.abs(difDias) + 'd';
        simbolo = '-';
        claseNeg = 'diaNegativo';
    }

    return {txt: txt, simbolo: simbolo, claseNeg: claseNeg};
}

window.iniciarManejadoresFechaProximaHabito = function() {
    document.querySelectorAll('.divProxima[data-tarea]').forEach(div => {
        const listenerExistente = div._manejadorClicFechaProximaHabito;
        if (listenerExistente) div.removeEventListener('click', listenerExistente);

        div._manejadorClicFechaProximaHabito = function (event) {
            event.stopPropagation();
            window.hideAllOpenTaskMenus();

            const tareaId = this.dataset.tarea;
            const liTarea = this.closest('.POST-tarea');
            const fechaActual = liTarea ? liTarea.dataset.proxima : null;
            const spanTexto = this.querySelector('.textoProxima');

            contextoCalendario = {
                esParaTareaEspecifica: true,
                idTarea: tareaId,
                elementoSpanTexto: spanTexto,
                elementoLiTarea: liTarea,
                elementoDisparador: this,
                tipoFecha: 'proxima'
            };

            mostrarCal(this, fechaActual || null);
        };

        div.addEventListener('click', div._manejadorClicFechaProximaHabito);
    });
}

window.actualizarFechaProximaHabitoServidorUI = async function(idTarea, nuevaFechaISO, spanTexto, liTarea) {
    const datos = {tareaId: idTarea, fechaProxima: nuevaFechaISO};
    console.log(`actualizarFechaProximaHabitoServidorUI: Enviando AJAX para tarea ${idTarea}, fecha próxima: ${nuevaFechaISO}`);

    try {
        // Asumimos que tendrás un endpoint PHP llamado 'modificarFechaProximaHabito'
        const rta = await enviarAjax('modificarFechaProximaHabito', datos);
        if (rta.success) {
            const tiempo = calcularTextoTiempoJS(nuevaFechaISO);
            if (spanTexto) {
                spanTexto.textContent = tiempo.simbolo + tiempo.txt;
                spanTexto.className = 'textoProxima ' + tiempo.claseNeg; // Asegúrate que la clase base es correcta
            }
            if (liTarea) {
                liTarea.dataset.proxima = nuevaFechaISO || '';
                const difDias = nuevaFechaISO ? Math.round((new Date(nuevaFechaISO + 'T00:00:00').getTime() - new Date(new Date().setHours(0, 0, 0, 0)).getTime()) / (1000 * 60 * 60 * 24)) : 0;
                liTarea.setAttribute('dif', difDias);
            }
            console.log(`actualizarFechaProximaHabitoServidorUI: Tarea ${idTarea} (próxima) actualizada a ${nuevaFechaISO || 'ninguna'}.`);
        } else {
            alert('Error al actualizar fecha próxima en servidor: ' + (rta.data || 'Error desconocido'));
            console.error(`actualizarFechaProximaHabitoServidorUI: Error AJAX para ${idTarea}`, rta);
        }
    } catch (error) {
        alert('Error de conexión al actualizar fecha próxima.');
        console.error(`actualizarFechaProximaHabitoServidorUI: Excepción AJAX para ${idTarea}`, error);
    }
}
