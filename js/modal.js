function modalDetallesIA() {
    const modal = document.getElementById('modalDetallesIA');
    const modalBackground = document.getElementById('backgroundDetallesIA');
    const modalContent = document.getElementById('modalDetallesContent');

    // Evento para abrir el modal
    document.querySelectorAll('.infoIA-btn').forEach(button => {
        button.addEventListener('click', function () {
            // Obtener el ID del post desde el atributo data-post-id
            const postId = this.getAttribute('data-post-id');

            // Buscar el párrafo invisible que tiene el id-post con el mismo valor
            const postDetalles = document.querySelector(`p[id-post="${postId}"]`);

            // Asegurarse de que existe el elemento y mostrar el contenido en el modal
            if (postDetalles) {
                modalContent.textContent = postDetalles.textContent;

                // Mostrar el modal y el fondo
                modal.style.display = 'block';
                modalBackground.style.display = 'block';

                // Bloquear el scroll del fondo
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // Cerrar el modal al hacer clic en el fondo oscuro
    modalBackground.addEventListener('click', function () {
        modal.style.display = 'none';
        modalBackground.style.display = 'none';

        // Restablecer el scroll del fondo
        document.body.style.overflow = 'auto';
    });
}

class ModalManager {
    constructor(modalBackgroundSelector) {
        this.modalBackground = document.querySelector(modalBackgroundSelector);
        this.modals = {};
        this.currentOpenModal = null;

        if (!this.modalBackground) {
            console.warn('No se encontra el background del modal');
            return;
        }

        this.setupBackgroundListener();
        this.setupBodyListener();
    }

    añadirModal(id, modalSelector, triggerSelectors, closeButtonSelector = null) {
        const modal = document.querySelector(modalSelector);
        if (!modal) {
            console.warn(`Modal elemento id:: ${id}`);
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
            closeButton: closeButtonSelector
        };

        this.setupTriggers(id);
        this.setupCloseButton(id);
        this.setupModalListener(modal);
    }

    setupTriggers(modalId) {
        const {triggers} = this.modals[modalId];
        if (!triggers || triggers.length === 0) return;

        triggers.forEach(trigger => {
            trigger.addEventListener('click', event => {
                event.stopPropagation();
                this.toggleModal(modalId, true);
            });
        });
    }

    setupCloseButton(modalId) {
        const {closeButton} = this.modals[modalId];
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

    setupBackgroundListener() {
        if (!this.modalBackground) return;

        this.modalBackground.addEventListener('click', () => this.closeAllModals());
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
        if (this.modalBackground) {
            this.modalBackground.style.display = show ? 'block' : 'none';
        }
        this.currentOpenModal = show ? modalId : null;
    }

    closeAllModals() {
        Object.keys(this.modals).forEach(modalId => this.toggleModal(modalId, false));
        this.currentOpenModal = null;
    }
}

// Example usage
const modalManager = new ModalManager('.modalBackground2');

function smooth() {
    modalManager.añadirModal('modalinvertir', '#modalinvertir', ['.donar'], '.cerrardonar');
    modalManager.añadirModal('modalproyecto', '#modalproyecto', ['.unirteproyecto'], '.DGFDRDC');
    modalManager.añadirModal('proPro', '#propro', ['.prostatus0']);
    modalManager.añadirModal('proProAcciones', '#proproacciones', ['.subpro']);
    modalManager.añadirModal('W0512KN', '#a84J76WY', ['#W0512KN'], '#MkzIeq');
    modalDetallesIA();
}
