class ModalManager {
    constructor() {
        this.modals = {};
        this.currentOpenModal = null;

        this.setupBodyListener();
    }

    añadirModal(id, modalSelector, triggerSelectors, closeButtonSelector = null) {
        // Obtenemos el elemento modal
        const modal = document.querySelector(modalSelector);
        if (!modal) {
            console.warn(`Modal elemento id:: ${id} no encontrado.`);
            return;
        }

        // Obtenemos los triggers
        const triggers = triggerSelectors.flatMap(selector => {
            try {
                return Array.from(document.querySelectorAll(selector)); // Usamos querySelectorAll y lo convertimos en array
            } catch (error) {
                console.warn(`Selector fallo:: ${selector}`);
                return [];
            }
        });

        if (triggers.length === 0) {
            console.warn(`Fail triggers modal id: ${id}`);
            return;
        }

        let modalInfo = this.modals[id];

        if (!modalInfo) {
            // Si no existe, lo creamos
            modalInfo = this.modals[id] = {
                modal,
                triggers: [],
                closeButtonSelector,
                darkBackground: null,
                triggerListeners: [],
                closeButtonElement: null,
                closeButtonListener: null,
                modalListener: null,
                darkBackgroundListener: null,
                isOpen: false // Añadimos un flag para saber si está abierto
            };
        } else {
            // Si ya existe, actualizamos referencias
            // Actualizamos el modal (por si el elemento cambió)
            modalInfo.modal = modal;
            modalInfo.closeButtonSelector = closeButtonSelector;

            // Removemos los event listeners anteriores de los triggers
            if (modalInfo.triggerListeners) {
                modalInfo.triggerListeners.forEach(({trigger, listener}) => {
                    trigger.removeEventListener('click', listener);
                });
            }
            // Removemos el event listener del botón de cerrar
            if (modalInfo.closeButtonElement && modalInfo.closeButtonListener) {
                modalInfo.closeButtonElement.removeEventListener('click', modalInfo.closeButtonListener);
            }
            // Removemos el event listener del modal
            if (modalInfo.modalListener) {
                modalInfo.modal.removeEventListener('click', modalInfo.modalListener);
            }
        }

        // Actualizamos los triggers y volvemos a configurar los event listeners
        modalInfo.triggers = triggers;
        this.setupTriggers(id);
        this.setupCloseButton(id);
        this.setupModalListener(id);
    }

    setupTriggers(modalId) {
        const modalInfo = this.modals[modalId];
        const {triggers} = modalInfo;
        if (!triggers || triggers.length === 0) return;

        modalInfo.triggerListeners = [];

        triggers.forEach(trigger => {
            const listener = event => {
                event.stopPropagation();
                this.toggleModal(modalId, true);
            };
            trigger.addEventListener('click', listener);
            modalInfo.triggerListeners.push({trigger, listener});
        });
    }

    setupCloseButton(modalId) {
        const modalInfo = this.modals[modalId];
        const {closeButtonSelector} = modalInfo;
        if (!closeButtonSelector) return;

        const closeButtonElement = document.querySelector(closeButtonSelector);
        if (closeButtonElement) {
            const listener = event => {
                event.stopPropagation();
                this.toggleModal(modalId, false);
            };
            closeButtonElement.addEventListener('click', listener);
            // Guardamos referencias
            modalInfo.closeButtonElement = closeButtonElement;
            modalInfo.closeButtonListener = listener;
        } else {
            console.warn(`Close button element not found for modal id: ${modalId}`);
        }
    }

    setupModalListener(modalId) {
        const modalInfo = this.modals[modalId];
        const modal = modalInfo.modal;
        const listener = event => event.stopPropagation();
        modal.addEventListener('click', listener);
        // Guardamos referencia
        modalInfo.modalListener = listener;
    }

    setupBodyListener() {
        document.body.addEventListener('click', event => {
            if (this.currentOpenModal && !this.modals[this.currentOpenModal].modal.contains(event.target)) {
                this.closeAllModals();
            }
        });
    }

    toggleModal(modalId, show) {
        const modalInfo = this.modals[modalId];
        if (!modalInfo) {
            console.warn(`Modal info not found for id: ${modalId}`);
            return;
        }

        if (this.currentOpenModal && this.currentOpenModal !== modalId) {
            this.toggleModal(this.currentOpenModal, false);
        }

        modalInfo.modal.style.display = show ? 'flex' : 'none';

        if (show) {
            if (!modalInfo.darkBackground) {
                // Crear o reutilizar el fondo oscuro
                modalInfo.darkBackground = this.createOrGetDarkBackground(modalInfo.modal);
                const listener = () => this.closeAllModals();
                modalInfo.darkBackground.addEventListener('click', listener);
                modalInfo.darkBackgroundListener = listener;
            }
            modalInfo.darkBackground.style.display = 'block';
            modalInfo.isOpen = true; // Marcamos el modal como abierto
        } else if (modalInfo.darkBackground) {
            modalInfo.darkBackground.style.display = 'none';
            modalInfo.isOpen = false; // Marcamos el modal como cerrado
        }

        this.currentOpenModal = show ? modalId : null;
    }

    closeAllModals() {
        Object.keys(this.modals).forEach(modalId => this.toggleModal(modalId, false));
        this.currentOpenModal = null;
    }

    createOrGetDarkBackground(modalElement) {
        let darkBackground = document.querySelector('.modal-backgroundModal');
        if (!darkBackground) {
            darkBackground = document.createElement('div');
            darkBackground.classList.add('modal-backgroundModal');
            // Insertar el fondo oscuro antes del modal en el DOM
            modalElement.parentNode.insertBefore(darkBackground, modalElement);
        }
        return darkBackground;
    }
}

// Uso de ejemplo
const modalManager = new ModalManager();

function smooth() {
    modalManager.añadirModal('opcionesFiltros', '#filtrosPost', ['.ORDENPOSTSL']);
    modalManager.añadirModal('carta', '#modalCarta', ['.carta'], '.cerrarCarta');
    modalManager.añadirModal('modalinvertir', '#modalinvertir', ['.donar'], '.cerrardonar');
    modalManager.añadirModal('modalproyecto', '#modalproyecto', ['.unirteproyecto'], '.DGFDRDC');
    modalManager.añadirModal('proPro', '#propro', ['.prostatus0']);
    modalManager.añadirModal('proProAcciones', '#proproacciones', ['.subpro']);
    modalManager.añadirModal('W0512KN', '#a84J76WY', ['#W0512KN'], '#MkzIeq');
    modalManager.añadirModal('notificaciones', '#notificacionesModal', ['.icono-notificaciones']);
    modalManager.añadirModal('config', '#modalConfig', ['.botonConfig']);
    modalManager.añadirModal('RS', '#formRs', ['.subiricono']);
}

function busquedaMenuMovil() {
    // Obtener todos los elementos necesarios
    const elementos = {
        iconoBusqueda: document.getElementById('iconobusqueda'),
        filtros: document.getElementById('filtros'),
        overlay: document.getElementById('overlay'),
        header: document.getElementById('header'),
        endMenu: document.querySelector('.endmenu.MENUDGE')
    };

    // Verificar si todos los elementos existen
    const todosElementosExisten = Object.values(elementos).every(elemento => elemento !== null);

    // Si falta algún elemento, salir de la función
    if (!todosElementosExisten) {
        console.warn('No se encontraron todos los elementos necesarios para el menú móvil');
        return;
    }

    // Desestructurar los elementos para usar en el resto del código
    const { iconoBusqueda, filtros, overlay, header, endMenu } = elementos;

    // Verifica si la pantalla tiene menos de 640px
    function actualizarVisibilidad() {
        if (window.innerWidth <= 640) {
            iconoBusqueda.style.display = 'flex';
        } else {
            iconoBusqueda.style.display = 'none';
            cerrarModal();
        }
    }

    // Función para mover el contenedor de búsqueda fuera de "endmenu"
    function moverFiltrosAHeader() {
        if (filtros && endMenu.contains(filtros)) {
            const filtrosClone = filtros.cloneNode(true);
            filtrosClone.classList.add('modal');
            endMenu.removeChild(filtros);
            header.appendChild(filtrosClone);
            window.filtros = filtrosClone;
            return filtrosClone;
        }
        return filtros;
    }

    // Función para devolver el contenedor de búsqueda a "endmenu"
    function devolverFiltrosAEndMenu() {
        const currentFiltros = document.getElementById('filtros');
        if (currentFiltros && header.contains(currentFiltros)) {
            const filtrosClone = currentFiltros.cloneNode(true);
            filtrosClone.classList.remove('modal');
            header.removeChild(currentFiltros);
            endMenu.appendChild(filtrosClone);
            window.filtros = filtrosClone;
            return filtrosClone;
        }
        return currentFiltros;
    }

    // Abre el modal
    function abrirModal() {
        const movedFiltros = moverFiltrosAHeader();
        if (movedFiltros) {
            movedFiltros.style.display = 'flex';
            overlay.style.display = 'block';
        }
    }

    // Cierra el modal
    function cerrarModal() {
        const currentFiltros = document.getElementById('filtros');
        if (currentFiltros) {
            currentFiltros.style.display = 'none';
            overlay.style.display = 'none';
            devolverFiltrosAEndMenu();
        }
    }

    // Evento de clic en el ícono de búsqueda
    iconoBusqueda.addEventListener('click', () => {
        const currentFiltros = document.getElementById('filtros');
        if (currentFiltros.style.display === 'flex') {
            cerrarModal();
        } else {
            abrirModal();
        }
    });

    // Evento de clic en el overlay
    overlay.addEventListener('click', cerrarModal);

    // Ajustar visibilidad al cambiar el tamaño de la ventana
    window.addEventListener('resize', actualizarVisibilidad);

    // Configuración inicial
    actualizarVisibilidad();
}