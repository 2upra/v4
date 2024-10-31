(function (global) {
    function $(selector) {
        const elementos = document.querySelectorAll(selector);
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
    if (element && getComputedStyle(element).display === 'none') {
        element._previousDisplay = getComputedStyle(element).display === 'none' ? 'block' : getComputedStyle(element).display;
        addTransition(element, 0, 1);
    }
}

window.ocultar = function (element) {
    if (element && getComputedStyle(element).display !== 'none') {
        addTransition(element, 1, 0);
    }
}
