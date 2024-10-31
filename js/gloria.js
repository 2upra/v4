/*

tengo este error 
4gloria.js?ver=1.0.1.1513955390:66  Uncaught TypeError: Failed to execute 'getComputedStyle' on 'Window': parameter 1 is not of type 'Element'.
    at window.mostrar (gloria.js?ver=1.0.1.1513955390:66:9)
    at abrirColec (colec.js?ver=1.0.1.362106248:53:5)
    at HTMLBodyElement.<anonymous> (colec.js?ver=1.0.1.362106248:20:13)
*/

(function (global) {
    function $(selector) {
        const elementos = document.querySelectorAll(selector);
        if (elementos.length === 0) return null;
        return elementos.length === 1 ? elementos[0] : elementos;
    }

    // Métodos adicionales para manipular clases
    $.agregarClase = function (selector, nombreClase) {
        document.querySelectorAll(selector).forEach(el => el.classList.add(nombreClase));
    };

    $.removerClase = function (selector, nombreClase) {
        document.querySelectorAll(selector).forEach(el => el.classList.remove(nombreClase));
    };

    $.toggleClase = function (selector, nombreClase) {
        document.querySelectorAll(selector).forEach(el => el.classList.toggle(nombreClase));
    };

    global.$ = $;
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

window.mostrar = function (element) {
    if (!element || !(element instanceof Element)) {
        console.error('No se proporcionó un elemento válido o el elemento no es de tipo Element');
        return;
    }

    if (getComputedStyle(element).display === 'none') {
        element._previousDisplay = getComputedStyle(element).display === 'none' ? 'block' : getComputedStyle(element).display;
        addTransition(element, 0, 1);
    }
};

window.ocultar = function (element) {
    if (element && getComputedStyle(element).display !== 'none') {
        addTransition(element, 1, 0);
    }
}


