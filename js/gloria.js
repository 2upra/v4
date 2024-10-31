/*
PROGRESO DE BIBLIOTECA PRORPIA 
MOSTRAR: MOSTRAR ALGO (LISTO)
OCULTAR: OCULTAR ALGO (LISTO)
PUN: SELECIONAR COSAS (LISTO)
PIN: ASIGNAR EVENTOS (FALTA)
*/

(function (global) {
    function pin(selector) {
        const elemento = pun(selector);
        if (!elemento) return null;

        return {
            // Método básico para agregar eventos
            en: function (evento, callback, opciones) {
                elemento.addEventListener(evento, callback, opciones);
                return this;
            },

            // Método para delegación de eventos
            delegar: function (evento, childSelector, callback) {
                elemento.addEventListener(evento, e => {
                    const target = e.target.closest(childSelector);
                    if (target) {
                        callback.call(target, e);
                    }
                });
                return this;
            },

            // Método para remover eventos
            ya: function (evento, callback) {
                elemento.removeEventListener(evento, callback);
                return this;
            },

            // Método para eventos de una sola vez
            uno: function (evento, callback) {
                elemento.addEventListener(evento, callback, {once: true});
                return this;
            },

            // Método para emitir eventos personalizados
            emitir: function (nombreEvento, detalle = {}) {
                const evento = new CustomEvent(nombreEvento, {
                    detail: detalle,
                    bubbles: true,
                    cancelable: true
                });
                elemento.dispatchEvent(evento);
                return this;
            }
        };
    }

    // Método estático para múltiples selectores
    pin.multiple = function (selectores, evento, callback) {
        if (Array.isArray(selectores)) {
            selectores.forEach(selector => {
                pin(selector)?.en(evento, callback);
            });
        }
        return pin;
    };

    // Método estático para eventos de una sola vez
    pin.uno = function (selector, evento, callback) {
        return pin(selector)?.uno(evento, callback);
    };

    // Método estático para emitir eventos
    pin.emitir = function (selector, nombreEvento, detalle = {}) {
        return pin(selector)?.emitir(nombreEvento, detalle);
    };

    // Método estático para delegación global
    pin.delegar = function (evento, childSelector, callback) {
        return pin(document).delegar(evento, childSelector, callback);
    };

    // Método para crear y disparar eventos personalizados rápidamente
    pin.gancho = function (selector, nombreEvento) {
        const elemento = pun(selector);
        if (elemento) {
            const evento = new Event(nombreEvento, {
                bubbles: true,
                cancelable: true
            });
            elemento.dispatchEvent(evento);
        }
        return pin;
    };

    global.pin = pin;
})(window);

(function (global) {
    function pun(selector) {
        if (typeof selector === 'string') {
            const elementos = document.querySelectorAll(selector);
            if (elementos.length === 0) return null;
            return elementos[0];
        }
        return selector;
    }
    function obtenerElementos(selector) {
        if (typeof selector === 'string') {
            return Array.from(document.querySelectorAll(selector));
        }
        return [selector];
    }

    pun.agregarClase = function (selector, nombreClase) {
        obtenerElementos(selector).forEach(el => el.classList.add(nombreClase));
    };

    pun.removerClase = function (selector, nombreClase) {
        obtenerElementos(selector).forEach(el => el.classList.remove(nombreClase));
    };

    pun.toggleClase = function (selector, nombreClase) {
        obtenerElementos(selector).forEach(el => el.classList.toggle(nombreClase));
    };

    global.pun = pun;
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
    element.yasetHeight;
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
        {uno: true}
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
