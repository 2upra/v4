//////////////////////////////////////////////
//ACTIVAR O DESACTIVAR LOGS
const A08 = false; // Cambia a true para activar los logs
const log08 = A08 ? console.log : function () {};
//////////////////////////////////////////////

function mostrarPestana(id) {
    log08("mostrarPestana: Inicio de la función. ID recibido:", id);

    // Ocultar y desactivar todas las pestañas
    jQuery(".tab-content .tab").hide().removeClass("active");
    log08("mostrarPestana: Todas las pestañas ocultas y la clase 'active' eliminada de todos los elementos con clase '.tab'.");

    // Verifica si el ID existe en el DOM
    if (jQuery(id).length === 0) {
        console.error(`mostrarPestana: No se encontró la pestaña con ID ${id}.`);
        return;
    }

    // Desactivar todos los links de pestañas
    jQuery(".tab-links li").removeClass("active");
    log08("mostrarPestana: Clase 'active' eliminada de todos los elementos con clase '.tab-links li'.");

    // Mostrar y activar la pestaña específica
    jQuery(id).show().addClass("active");
    log08(`mostrarPestana: Pestaña con ID '${id}' mostrada y clase 'active' agregada. Selector afectado: '${id}'.`);

    // Activar el link asociado a la pestaña
    var linkSelector = `.tab-links a[href="${id}"]`;
    jQuery(linkSelector).parent().addClass("active");
    log08(`mostrarPestana: Clase 'active' agregada al elemento padre del link con selector '${linkSelector}'. Selector del padre afectado: '${linkSelector}.parent()'`);

    // Actualizar el atributo 'pestanaActual' en #menuData
    var pestanaActual = id.replace('#', '');
    jQuery("#menuData").attr("pestanaActual", pestanaActual);
    log08(`mostrarPestana: Atributo 'pestanaActual' actualizado en el elemento con ID '#menuData'. Valor asignado: '${pestanaActual}'.`);
}


function inicializarPestanas() {
    log08("inicializarPestanas: Inicio de la función.");

    asignarPestanas();
    log08("inicializarPestanas: Función asignarPestanas() ejecutada.");

    jQuery(".tab-content .tab").hide().removeClass("active");
    log08("inicializarPestanas: Todas las pestañas ocultas y clase 'active' eliminada de todos los elementos con clase '.tab'.");

    // Detectar si hay un hash en la URL al cargar la página
    var hash = window.location.hash;
    
    if (hash && jQuery(hash).length) {
        // Si hay un hash y corresponde a una pestaña, mostrarla
        mostrarPestana(hash);
        log08(`inicializarPestanas: Hash existente detectado en la URL (${hash}).`);
    } else {
        // Si no hay hash o el hash no corresponde a una pestaña, activar la primera pestaña
        var $firstPestana = jQuery(".tab-content .tab").first();
        mostrarPestana('#' + $firstPestana.attr('id'));
        log08(`inicializarPestanas: No se encontró hash válido, activando la primera pestaña con ID='${$firstPestana.attr('id')}'.`);
    }

    jQuery(".tab-links a").on("click", function (e) {
        e.preventDefault();
        var targetId = jQuery(this).attr("href");
        log08(`inicializarPestanas: Click en pestaña detectado. ID del target: '${targetId}'`);
        mostrarPestana(targetId);
    });
}


function asignarPestanas() {
    log08("asignarPestanas: Inicio de la función.");

    const menuData = document.getElementById('menuData');
    const adaptableTabs = document.getElementById('adaptableTabs');

    if (menuData && adaptableTabs) {
        log08("asignarPestanas: menuData y adaptableTabs encontrados.");

        adaptableTabs.innerHTML = '';
        log08("asignarPestanas: Contenido de adaptableTabs limpiado.");

        const tabs = menuData.querySelectorAll('[data-tab]');
        log08("asignarPestanas: Pestañas encontradas en menuData:", tabs.length);

        tabs.forEach((tab, index) => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            const tabName = tab.getAttribute('data-tab');
            log08("asignarPestanas: Procesando pestaña:", tabName);

            a.href = '#' + tabName;
            a.textContent = tabName.charAt(0).toUpperCase() + tabName.slice(1);
            log08("asignarPestanas: Enlace creado para la pestaña:", a.href);

            if (index === 0) {
                li.classList.add('active');
                log08("asignarPestanas: Primera pestaña activada:", tabName);
            }

            li.appendChild(a);
            adaptableTabs.appendChild(li);
            log08("asignarPestanas: Pestaña añadida a adaptableTabs:", tabName);
        });
    } else {
        log08("asignarPestanas: menuData o adaptableTabs no encontrados.");
    }
}
