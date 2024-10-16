class ModalManager {
    constructor() {
        this.modals = {};
        this.currentOpenModal = null;

        this.setupBodyListener();
    }

    añadirModal(id, modalSelector, triggerSelectors, closeButtonSelector = null) {
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
            closeButton: closeButtonSelector,
            darkBackground: null // Guardaremos el fondo oscuro aquí
        };

        this.setupTriggers(id);
        this.setupCloseButton(id);
        this.setupModalListener(modal);
    }

    setupTriggers(modalId) {
        const { triggers } = this.modals[modalId];
        if (!triggers || triggers.length === 0) return;

        triggers.forEach(trigger => {
            trigger.addEventListener('click', event => {
                event.stopPropagation();
                this.toggleModal(modalId, true);
            });
        });
    }

    setupCloseButton(modalId) {
        const { closeButton } = this.modals[modalId];
        if (!closeButton) return;

        const closeButtonElement = document.querySelector(closeButton);
        if (closeButtonElement) {
            closeButtonElement.addEventListener('click', event => {
                event.stopPropagation();
                this.toggleModal(modalId, false);
            });
        } else {
            console.warn(`Close button element not found for modal id: ${modalId}`);
        }
    }

    setupModalListener(modal) {
        modal.addEventListener('click', event => event.stopPropagation());
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
                modalInfo.darkBackground.addEventListener('click', () => this.closeAllModals());
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
}

// Example usage
const modalManager = new ModalManager();

function smooth() {
    modalManager.añadirModal('carta', '#modalCarta', ['.carta'], '.cerrarCarta')
    modalManager.añadirModal('modalinvertir', '#modalinvertir', ['.donar'], '.cerrardonar');
    modalManager.añadirModal('modalproyecto', '#modalproyecto', ['.unirteproyecto'], '.DGFDRDC');
    modalManager.añadirModal('proPro', '#propro', ['.prostatus0']);
    modalManager.añadirModal('proProAcciones', '#proproacciones', ['.subpro']);
    modalManager.añadirModal('W0512KN', '#a84J76WY', ['#W0512KN'], '#MkzIeq');
    modalDetallesIA();
}