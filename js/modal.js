class ModalManager {
    constructor() {
        this.modals = {};
        this.currentOpenModal = null;

        this.setupBodyListener();
    }

    añadirModal(id, modalSelector, triggerSelectors, closeButtonSelector = null) {
        if (this.modals[id]) {
            // Si ya existe un modal con este ID, lo eliminamos antes de añadir el nuevo
            this.removeModal(id);
        }

        const modal = document.querySelector(modalSelector);
        if (!modal) {
            console.warn(`Modal elemento id:: ${id} no encontrado.`);
            return;
        }

        const triggers = triggerSelectors
            .map(selector => {
                try {
                    return document.querySelector(selector);
                } catch (error) {
                    console.warn(`Selector fallo:: ${selector}`);
                    return null;
                }
            })
            .filter(Boolean);

        if (triggers.length === 0) {
            console.warn(`Fail triggers modal id: ${id}`);
            return;
        }

        this.modals[id] = {
            modal,
            triggers,
            closeButtonSelector,
            darkBackground: null, // Guardaremos el fondo oscuro aquí
            triggerListeners: [],
            closeButtonElement: null,
            closeButtonListener: null,
            modalListener: null,
            darkBackgroundListener: null,
        };

        this.setupTriggers(id);
        this.setupCloseButton(id);
        this.setupModalListener(id);
    }

    setupTriggers(modalId) {
        const modalInfo = this.modals[modalId];
        const { triggers } = modalInfo;
        if (!triggers || triggers.length === 0) return;

        // Guardamos las funciones de los event listeners para poder removerlos después
        modalInfo.triggerListeners = [];

        triggers.forEach(trigger => {
            const listener = event => {
                event.stopPropagation();
                this.toggleModal(modalId, true);
            };
            trigger.addEventListener('click', listener);
            modalInfo.triggerListeners.push({ trigger, listener });
        });
    }

    setupCloseButton(modalId) {
        const modalInfo = this.modals[modalId];
        const { closeButtonSelector } = modalInfo;
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
                // Crear el fondo oscuro como hermano del modal
                modalInfo.darkBackground = createModalDarkBackground(modalInfo.modal);
                const listener = () => this.closeAllModals();
                modalInfo.darkBackground.addEventListener('click', listener);
                modalInfo.darkBackgroundListener = listener;
            }
            modalInfo.darkBackground.style.display = 'block';
        } else if (modalInfo.darkBackground) {
            modalInfo.darkBackground.style.display = 'none';
        }

        this.currentOpenModal = show ? modalId : null;
    }

    closeAllModals() {
        Object.keys(this.modals).forEach(modalId => this.toggleModal(modalId, false));
        this.currentOpenModal = null;
    }

    removeModal(id) {
        const modalInfo = this.modals[id];
        if (!modalInfo) return;

        // Remover todos los event listeners

        // Para triggers
        if (modalInfo.triggerListeners) {
            modalInfo.triggerListeners.forEach(({ trigger, listener }) => {
                trigger.removeEventListener('click', listener);
            });
        }

        // Para el botón de cerrar
        if (modalInfo.closeButtonElement && modalInfo.closeButtonListener) {
            modalInfo.closeButtonElement.removeEventListener('click', modalInfo.closeButtonListener);
        }

        // Para el modal
        if (modalInfo.modalListener) {
            modalInfo.modal.removeEventListener('click', modalInfo.modalListener);
        }

        // Remover el fondo oscuro y su listener
        if (modalInfo.darkBackground) {
            if (modalInfo.darkBackgroundListener) {
                modalInfo.darkBackground.removeEventListener('click', modalInfo.darkBackgroundListener);
            }
            modalInfo.darkBackground.parentNode.removeChild(modalInfo.darkBackground);
        }

        // Eliminar los datos del modal
        delete this.modals[id];

        // Si este modal estaba abierto, reiniciamos currentOpenModal
        if (this.currentOpenModal === id) {
            this.currentOpenModal = null;
        }
    }
}

function createModalDarkBackground(modalElement) {
    const darkBackground = document.createElement('div');
    darkBackground.classList.add('modal-background');
    // Insertar el fondo oscuro antes del modal en el DOM
    modalElement.parentNode.insertBefore(darkBackground, modalElement);
    return darkBackground;
}

// Uso de ejemplo
const modalManager = new ModalManager();

function smooth() {
    modalManager.añadirModal('opcionesFiltros', '#filtrosPost', ['.ORDENPOSTSL'])
    modalManager.añadirModal('carta', '#modalCarta', ['.carta'], '.cerrarCarta')
    modalManager.añadirModal('modalinvertir', '#modalinvertir', ['.donar'], '.cerrardonar');
    modalManager.añadirModal('modalproyecto', '#modalproyecto', ['.unirteproyecto'], '.DGFDRDC');
    modalManager.añadirModal('proPro', '#propro', ['.prostatus0']);
    modalManager.añadirModal('proProAcciones', '#proproacciones', ['.subpro']);
    modalManager.añadirModal('W0512KN', '#a84J76WY', ['#W0512KN'], '#MkzIeq');
    modalDetallesIA();
}