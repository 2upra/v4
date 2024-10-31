/*

Porque dice que no es un elemeento valido, estoy haciendo mi propia biblioteca pero $ no pasa un elemento valido cuando se seleciona uno
gloria.js?ver=1.0.1.2048570573:61  No se proporcionó un elemento válido o el elemento no es de tipo Element

function abrirColec() {
    const modal = $('.modalColec');
    if (!modal) {
        console.error('No se encontró el elemento .modalColec');
        return;
    }
    mostrar(modal);
    crearBackgroundColec();
    $.agregarClase('body', 'no-scroll');
}
*/

(function (global) {
    function $(selector) {
        if (typeof selector === 'string') {
            const elementos = document.querySelectorAll(selector);
            if (elementos.length === 0) return null;
            // Siempre devolver el primer elemento si es una búsqueda por selector
            return elementos[0];
        }
        return selector; // Si ya es un elemento, devolverlo tal cual
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
};
