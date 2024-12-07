/*
PUN: SELECIONAR COSAS
PIN: ASIGNAR EVENTOS (FALTA)
*/

/*

*/

(function (global) {
    function a(selector) {
        if (typeof selector === 'string') {
            const elementos = document.querySelectorAll(selector);
            if (elementos.length === 0) return null;
            return elementos[0];
        }
        return selector;
    }

    // Función auxiliar para convertir el selector en un array de elementos
    function obtenerElementos(selector) {
        if (typeof selector === 'string') {
            return Array.from(document.querySelectorAll(selector));
        }
        return [selector]; // Si es un elemento, lo envolvemos en un array
    }

    a.gregar = function (selector, nombreClase) {
        obtenerElementos(selector).forEach(el => el.classList.add(nombreClase));
    };

    a.quitar = function (selector, nombreClase) {
        obtenerElementos(selector).forEach(el => el.classList.remove(nombreClase));
    };

    a.cambiar = function (selector, nombreClase) {
        obtenerElementos(selector).forEach(el => el.classList.toggle(nombreClase));
    };

    global.a = a;
})(window);

// Funciones para mostrar y ocultar elementos con transición
function addTransition(element, from, to) {
    if (!element || !(element instanceof Element)) return; // Validación del elemento
    if (element._isTransitioning) return;
    element._isTransitioning = true;
    element.style.opacity = from;
    element.style.transition = 'opacity 0.3s ease';
    if (to === 1) {
        element.style.display = element._previousDisplay || 'block';
    }
    element.offsetHeight;
    element.style.opacity = to;
    element.addEventListener(
        'transitionend',
        function handler() {
            element._isTransitioning = false;
            element.removeEventListener('transitionend', handler);
            if (to === 0) {
                element.style.display = 'none';
            }
            element.style.transition = '';
        },
        {once: true}
    );
}

