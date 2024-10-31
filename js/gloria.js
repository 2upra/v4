(function (global) {
    class PunElement {
        constructor(elementos) {
            this.elementos = Array.isArray(elementos) ? elementos : [elementos];
        }

        evento(tipo, callback) {
            this.elementos.forEach(elemento => {
                if (elemento) {
                    elemento.addEventListener(tipo, callback);
                }
            });
            return this;
        }

        delegado(tipo, selector, callback) {
            this.elementos.forEach(elemento => {
                if (elemento) {
                    elemento.addEventListener(tipo, e => {
                        const target = e.target.closest(selector);
                        if (target) {
                            callback.call(target, e);
                        }
                    });
                }
            });
            return this;
        }

        valor(nuevoValor) {
            if (nuevoValor === undefined) {
                return this.elementos[0]?.value;
            }
            this.elementos.forEach(elemento => {
                if (elemento) elemento.value = nuevoValor;
            });
            return this;
        }

        // Más métodos útiles...
    }

    function pun(selector) {
        if (typeof selector === 'string') {
            const elementos = document.querySelectorAll(selector);
            return new PunElement(elementos.length === 1 ? elementos[0] : Array.from(elementos));
        }
        return new PunElement(selector);
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
    // Método estático para delegación global
    pun.delegado = function (tipo, selector, callback) {
        document.addEventListener(tipo, e => {
            const target = e.target.closest(selector);
            if (target) {
                callback.call(target, e);
            }
        });
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
