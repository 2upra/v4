// js/taskProperties.js

window.ocultarBotones = function() {
    const elementosLi = document.querySelectorAll('.draggable-element'); // Asumo que esta es la clase de tus <li> o contenedores de tarea

    elementosLi.forEach(li => {
        // Evita añadir listeners múltiples veces al mismo elemento li
        if (li.dataset.botonesOcultosInicializados) {
            // Si los elementos ocultos pudieran cambiar dinámicamente DESPUÉS de esta inicialización,
            // se necesitaría una lógica más compleja para actualizar los listeners o los elementos cacheados.
            // Por ahora, asumimos que una vez que un li es procesado, sus hijos 'ocultadoAutomatico' no cambian.
            return;
        }

        const elementosOcultos = li.querySelectorAll('.ocultadoAutomatico'); // Clave: seleccionar TODOS

        if (elementosOcultos.length > 0) {
            const manejadorMouseOver = () => {
                elementosOcultos.forEach(eo => {
                    // La condición "solo aparecera cuando la tarea no tenga fecha limite"
                    // la maneja tu PHP al no generar el div si ya hay fecha, o no dándole la clase 'ocultadoAutomatico'.
                    // Por lo tanto, si el elemento está aquí y tiene 'ocultadoAutomatico', debe mostrarse.
                    eo.style.display = 'flex';
                });
            };

            const manejadorMouseOut = () => {
                elementosOcultos.forEach(eo => {
                    eo.style.display = 'none';
                });
            };

            li.addEventListener('mouseover', manejadorMouseOver);
            li.addEventListener('mouseout', manejadorMouseOut);

            // Guardar referencias a los manejadores si necesitaras removerlos específicamente después
            // li._manejadorMouseOverBotonesOcultos = manejadorMouseOver;
            // li._manejadorMouseOutBotonesOcultos = manejadorMouseOut;

            li.dataset.botonesOcultosInicializados = 'true'; // Marcar como inicializado
        }
    });
}

window.actSel = function(obj, val, txtPredeterminado = '') {
    // Por defecto, no hay texto predeterminado
    let ico = obj.selector.querySelector('span.icono');

    // Limpiar contenido previo (texto) del span.icono, dejando el SVG/ícono base
    while (ico.childNodes.length > 1 && (ico.lastChild.nodeType === Node.TEXT_NODE || ico.lastChild.tagName === 'P')) {
        ico.removeChild(ico.lastChild);
    }

    let textoAMostrar = '';
    if (val) {
        if (obj === fechaLimite) {
            // Formatear fecha si es el selector de fecha
            const partesFecha = val.split('-'); // val es YYYY-MM-DD
            // Formato corto: DD/MM
            textoAMostrar = `${partesFecha[2]}/${partesFecha[1]}`;
            // Formato más completo: DD NombreMesCorto (ej: 25 Jul)
            // const fechaObj = new Date(parseInt(partesFecha[0]), parseInt(partesFecha[1]) - 1, parseInt(partesFecha[2]));
            // textoAMostrar = `${partesFecha[2]} ${calNombresMeses[fechaObj.getMonth()]}`;
        } else {
            textoAMostrar = val;
        }
    } else if (txtPredeterminado) {
        // Solo si se proporciona explícitamente un texto predeterminado
        textoAMostrar = txtPredeterminado;
    }

    if (textoAMostrar) {
        // Solo añadir el <p> si hay algo que mostrar
        let txtElem = document.createElement('p');
        txtElem.textContent = textoAMostrar;
        ico.appendChild(txtElem);
    }
    obj.valor = val;
}

window.selectorTipoTarea = function() {
    importancia.selector = document.getElementById('sImportancia');
    tipoTarea.selector = document.getElementById('sTipo');
    fechaLimite.selector = document.getElementById('sFechaLimite');
    // La función actSel ya está definida globalmente

    const impContenedor = document.querySelector('#sImportancia-sImportancia .A1806242');
    const tipoContenedor = document.querySelector('#sTipo-sTipo .A1806242');

    if (impContenedor) {
        impContenedor.addEventListener('click', event => {
            if (event.target.tagName === 'BUTTON') {
                actSel(importancia, event.target.value);
                window.hideAllSubmenus();
            }
        });
    }

    if (tipoContenedor) {
        tipoContenedor.addEventListener('click', event => {
            if (event.target.tagName === 'BUTTON') {
                actSel(tipoTarea, event.target.value);
                window.hideAllSubmenus();
            }
        });
    }

    // Valores iniciales
    actSel(importancia, 'media');
    actSel(tipoTarea, 'una vez');
    actSel(fechaLimite, null); // No pasamos 'Sin fecha', actSel lo maneja
}

window.prioridadTarea = function() {
    const boton = document.querySelector('.prioridadTareas');

    if (boton.dataset.eventoAgregado) return;

    boton.addEventListener('click', async () => {
        const lista = document.querySelector('.social-post-list.clase-tarea');
        const divisores = Array.from(lista.querySelectorAll('.divisorTarea'));
        let log = '';

        for (const divisor of divisores) {
            const seccion = divisor.dataset.valor;
            let tarea = divisor.nextElementSibling;
            const tareasSeccion = [];

            while (tarea && tarea.classList.contains('POST-tarea') && tarea.dataset.seccion === seccion) {
                tareasSeccion.push({
                    tarea: tarea,
                    id: tarea.getAttribute('id-post'),
                    impnum: parseInt(tarea.getAttribute('impnum')),
                    padre: tarea.getAttribute('padre'),
                    dif: parseInt(tarea.getAttribute('dif')),
                    tipo: tarea.getAttribute('tipo-tarea')
                });
                tarea = tarea.nextElementSibling;
            }

            const tareasConPadre = tareasSeccion.filter(t => t.padre);
            const tareasSinPadre = tareasSeccion.filter(t => !t.padre);

            tareasSinPadre.sort((a, b) => {
                if (b.impnum !== a.impnum) {
                    return b.impnum - a.impnum;
                } else if ((a.tipo === 'habito' || a.tipo === 'habito rigido') && (b.tipo === 'habito' || b.tipo === 'habito rigido')) {
                    return a.dif - b.dif;
                } else {
                    return 0;
                }
            });

            const tareasOrdenadas = [];

            tareasSinPadre.forEach(tareaSinPadre => {
                tareasOrdenadas.push(tareaSinPadre);
                const subtareas = tareasConPadre.filter(t => t.padre === tareaSinPadre.id);
                subtareas.sort((a, b) => {
                    if (b.impnum !== a.impnum) {
                        return b.impnum - a.impnum;
                    } else if ((a.tipo === 'habito' || a.tipo === 'habito rigido') && (b.tipo === 'habito' || b.tipo === 'habito rigido')) {
                        return a.dif - b.dif;
                    } else {
                        return 0;
                    }
                });

                tareasOrdenadas.push(...subtareas);
            });

            const tablaTareas = [];
            tareasOrdenadas.forEach((t, i) => {
                const indiceDeseado = Array.from(lista.children).indexOf(divisor) + 1 + i;
                const indiceActual = Array.from(lista.children).indexOf(t.tarea);

                if (indiceActual !== indiceDeseado) {
                    const tareaReferencia = lista.children[indiceDeseado];
                    if (tareaReferencia) {
                        lista.insertBefore(t.tarea, tareaReferencia);
                    } else {
                        lista.appendChild(t.tarea);
                    }
                }

                tablaTareas.push({
                    ID: t.id,
                    Imp: t.impnum,
                    Padre: t.padre,
                    Dif: t.dif,
                    Tipo: t.tipo,
                    'Indice Actual': indiceActual,
                    'Indice Deseado': indiceDeseado
                });
            });

            if (tablaTareas.length > 0) {
                //console.table(tablaTareas);
            }
            log += `Se ordenaron ${tareasOrdenadas.length} tareas en la seccion "${seccion}". \n`;
        }
        log += `Se ejecuto prioridadTareas. \n`;

        //console.log(log);
        try {
            window.guardarOrden();
            console.log(log);
        } catch (error) {
            console.error('Error al guardar el orden:', error);
        }
    });

    boton.dataset.eventoAgregado = 'true';
}

window.cambiarFrecuencia = function() {
    document.querySelectorAll('.divFrecuencia').forEach(div => {
        const listenerExistente = div.funcionListenerFrecuencia;
        if (listenerExistente) {
            div.removeEventListener('click', listenerExistente);
        }

        const nuevaFuncionListener = async function (event) {
            event.stopPropagation(); // Evita que el listener del documento cierre el menú inmediatamente

            const divClicado = this;
            const tareaId = divClicado.dataset.tarea;
            const li = document.querySelector(`.POST-tarea[id-post="${tareaId}"]`);

            if (!li) return;

            // Verificar si ya hay un menú de frecuencia abierto PARA ESTA TAREA
            const menuExistente = li.nextElementSibling;
            if (menuExistente && menuExistente.classList.contains('opcionesFrecuencia') && menuExistente.dataset.tareaMenuId === tareaId) {
                menuExistente.remove();
                if (window.cerrarMenuSiClicFueraFrecuenciaHandler) {
                    document.removeEventListener('click', window.cerrarMenuSiClicFueraFrecuenciaHandler);
                    window.cerrarMenuSiClicFueraFrecuenciaHandler = null;
                }
                return; // Menú estaba abierto, ahora cerrado (toggle)
            }

            // Si no hay menú para esta tarea, o si hay otro menú abierto, cerrar todos los menús primero
            window.hideAllOpenTaskMenus();

            const ops = document.createElement('div');
            ops.classList.add('opcionesFrecuencia');
            ops.dataset.tareaMenuId = tareaId; // Marcar el menú con el ID de la tarea
            ops.innerHTML = `
                <p data-frecuencia="1">diaria</p>
                <p data-frecuencia="7">semanal</p>
                <p data-frecuencia="30">mensual</p>
                <div class="frecuenciaPersonalizada">
                    <input type="number" id="diasPersonalizados" min="2" max="365" placeholder="Cada X dias">
                    <button id="btnPersonalizar">${window.enviarMensaje || 'Enviar'}</button>
                </div>
            `;

            li.after(ops);

            // Definir y guardar el manejador para poder removerlo
            window.cerrarMenuSiClicFueraFrecuenciaHandler = e => {
                if (!ops.contains(e.target) && !divClicado.contains(e.target)) {
                    ops.remove();
                    if (window.cerrarMenuSiClicFueraFrecuenciaHandler) {
                        document.removeEventListener('click', window.cerrarMenuSiClicFueraFrecuenciaHandler);
                        window.cerrarMenuSiClicFueraFrecuenciaHandler = null;
                    }
                }
            };

            setTimeout(() => {
                // Añadir listener después del ciclo de evento actual
                document.addEventListener('click', window.cerrarMenuSiClicFueraFrecuenciaHandler);
            }, 0);

            const ps = ops.querySelectorAll('p:not([data-frecuencia="personalizada"])');
            ps.forEach(p => {
                p.addEventListener('click', evP => {
                    evP.stopPropagation();
                    const frec = p.dataset.frecuencia;
                    const data = {
                        tareaId: tareaId,
                        frecuencia: parseInt(frec)
                    };
                    actualizarFrecuencia(data, divClicado); // Asumiendo que actualizarFrecuencia existe
                    ops.remove();
                    if (window.cerrarMenuSiClicFueraFrecuenciaHandler) {
                        document.removeEventListener('click', window.cerrarMenuSiClicFueraFrecuenciaHandler);
                        window.cerrarMenuSiClicFueraFrecuenciaHandler = null;
                    }
                });
            });

            const btn = ops.querySelector('#btnPersonalizar');
            btn.addEventListener('click', evBtn => {
                evBtn.stopPropagation();
                const input = ops.querySelector('#diasPersonalizados');
                const dias = parseInt(input.value);
                if (dias >= 2 && dias <= 365) {
                    const data = {
                        tareaId: tareaId,
                        frecuencia: dias
                    };
                    actualizarFrecuencia(data, divClicado); // Asumiendo que actualizarFrecuencia existe
                    ops.remove();
                    if (window.cerrarMenuSiClicFueraFrecuenciaHandler) {
                        document.removeEventListener('click', window.cerrarMenuSiClicFueraFrecuenciaHandler);
                        window.cerrarMenuSiClicFueraFrecuenciaHandler = null;
                    }
                }
            });
        };

        div.addEventListener('click', nuevaFuncionListener);
        div.funcionListenerFrecuencia = nuevaFuncionListener;
    });
}

function actualizarFrecuencia(data, div) {
    enviarAjax('cambiarFrecuencia', data);
    const padre = div.querySelector('.frecuenciaTarea');
    let span = padre.querySelector('.tituloFrecuencia');
    if (!span) {
        span = document.createElement('span');
        span.classList.add('tituloFrecuencia');
        padre.appendChild(span);
    }
    if (data.frecuencia === 1) {
        span.textContent = 'diaria';
    } else if (data.frecuencia === 7) {
        span.textContent = 'semanal';
    } else if (data.frecuencia === 30) {
        span.textContent = 'mensual';
    } else {
        span.textContent = `${data.frecuencia}d`;
    }
}

window.cambiarPrioridad = function() {
    document.querySelectorAll('.divImportancia').forEach(div => {
        const listenerExistente = div.funcionListenerPrioridad;
        if (listenerExistente) {
            div.removeEventListener('click', listenerExistente);
        }
        const nuevaFuncionListener = manejarClicPrioridad;
        div.addEventListener('click', nuevaFuncionListener);
        div.funcionListenerPrioridad = nuevaFuncionListener;
    });
}

window.manejarClicPrioridad = async function(event) {
    // Este es el listener para el click en .divImportancia
    event.stopPropagation(); //Añadido para que el listener global no lo cierre al instante

    const divPrioridadOriginal = this;
    const idOriginal = divPrioridadOriginal.dataset.tarea;
    const liOriginal = document.querySelector(`.POST-tarea[id-post="${idOriginal}"]`);

    if (!liOriginal) return;

    // Lógica de Toggle: Buscar si ya hay un menú de prioridad abierto PARA ESTA TAREA
    const menuExistente = liOriginal.nextElementSibling;
    if (menuExistente && menuExistente.classList.contains('opcionesPrioridad') && menuExistente.dataset.tareaMenuId === idOriginal) {
        menuExistente.remove();
        if (window.cerrarMenuSiClicFueraPrioridadHandler) {
            document.removeEventListener('click', window.cerrarMenuSiClicFueraPrioridadHandler);
            window.cerrarMenuSiClicFueraPrioridadHandler = null;
        }
        return; // Menú estaba abierto, ahora cerrado (toggle)
    }

    // Si no hay menú para esta tarea, o si hay otro menú abierto, cerramos cualquier otro menú de prioridad/frecuencia que esté abierto
    window.hideAllOpenTaskMenus();

    const ops = document.createElement('div');
    ops.classList.add('opcionesPrioridad');
    ops.dataset.tareaMenuId = idOriginal; // Marcar el menú con el ID de la tarea
    ops.innerHTML = `
        <p data-prioridad="baja">${window.iconbaja || 'B'} baja</p>
        <p data-prioridad="media">${window.iconMedia || 'M'} media</p>
        <p data-prioridad="alta">${window.iconAlta || 'A'} alta</p>
        <p data-prioridad="importante">${window.iconimportante || 'I'} importante</p>
      `;
    liOriginal.after(ops); // Insertar el menú después del elemento de la tarea

    // Definir y guardar el manejador para poder removerlo
    window.cerrarMenuSiClicFueraPrioridadHandler = e => {
        // Si el clic NO es dentro del menú Y NO es en el botón que lo abrió
        if (!ops.contains(e.target) && !divPrioridadOriginal.contains(e.target)) {
            ops.remove();
            if (window.cerrarMenuSiClicFueraPrioridadHandler) {
                document.removeEventListener('click', window.cerrarMenuSiClicFueraPrioridadHandler);
                window.cerrarMenuSiClicFueraPrioridadHandler = null;
            }
        }
    };

    setTimeout(() => {
        // Añadir listener después del ciclo de evento actual
        document.addEventListener('click', window.cerrarMenuSiClicFueraPrioridadHandler);
    }, 0);

    const ps = ops.querySelectorAll('p');
    ps.forEach(p => {
        p.addEventListener('click', async evP => {
            // Renombrado event a evP
            evP.stopPropagation(); // Detener la propagación para clics en items del menú

            const prioSeleccionada = p.dataset.prioridad;
            const tareasSelActuales = tareasSeleccionadas || []; // Asegurarse que tareasSeleccionadas existe

            // console.log(`DEBUG cambiarPrioridad: idOriginal: "${idOriginal}", Prio: ${prioSeleccionada}, tareasSelActuales: ${JSON.stringify(tareasSelActuales)}, incluyeOriginal: ${tareasSelActuales.includes(idOriginal)}, longitud > 1: ${tareasSelActuales.length > 1}`);

            let logs = `cambiarPrioridad: Opción '${prioSeleccionada}' seleccionada para tarea original ${idOriginal}. `;

            let idsParaProcesar = [idOriginal];
            if (tareasSelActuales.length > 1 && tareasSelActuales.includes(idOriginal)) {
                idsParaProcesar = [...tareasSelActuales];
                logs += `Detectada seleccion multiple (${idsParaProcesar.length} tareas). `;
            } else {
                logs += `Accion individual. `;
            }

            // Cerrar menú y remover listener de clic fuera
            ops.remove();
            if (window.cerrarMenuSiClicFueraPrioridadHandler) {
                document.removeEventListener('click', window.cerrarMenuSiClicFueraPrioridadHandler);
                window.cerrarMenuSiClicFueraPrioridadHandler = null;
            }

            let logsFinales = logs;

            async function procesarUnaTarea(id, prio) {
                const data = {tareaId: id, prioridad: prio};
                try {
                    const rta = await enviarAjax('cambiarPrioridad', data);
                    if (rta.success) {
                        logsFinales += `Éxito AJAX para ${id}. Reiniciando post. `;
                        window.reiniciarPost(id, 'tarea');
                    } else {
                        let m = `Error AJAX para ${id}.`;
                        if (rta.data) m += ' Detalles: ' + rta.data;
                        logsFinales += m + ' ';
                    }
                } catch (err) {
                    logsFinales += `Excepcion AJAX para ${id}: ${err}. `;
                }
            }

            for (let i = 0; i < idsParaProcesar.length; i++) {
                const id = idsParaProcesar[i];
                await procesarUnaTarea(id, prioSeleccionada);
                if (idsParaProcesar.length > 1 && i < idsParaProcesar.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, 300));
                }
            }
            console.log(logsFinales + 'Fin cambiarPrioridad.');
        });
    });
}
