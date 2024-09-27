class UIManager {
    constructor() {
        this.elements = {};
        this.currentOpenElement = null;
        this.setupBodyListener();
    }

    addElement(id, elementSelector, triggerSelectors, closeButtonSelector = null, isModal = false) {
        const element = document.querySelector(elementSelector);
        if (!element) {
            console.warn(`Elemento no encontrado: ${id}`);
            return;
        }

        const triggers = triggerSelectors
            .map(selector => document.querySelector(selector))
            .filter(Boolean);

        if (triggers.length === 0) {
            console.warn(`No se encontraron triggers para: ${id}`);
            return;
        }

        this.elements[id] = { element, triggers, closeButton: closeButtonSelector, isModal };

        this.setupTriggers(id);
        this.setupCloseButton(id);
        this.setupElementListener(element);
    }

    setupTriggers(id) {
        const { triggers, isModal } = this.elements[id];
        triggers.forEach(trigger => {
            trigger.addEventListener('click', event => {
                event.stopPropagation();
                this.toggleElement(id, true, event, isModal);
            });
        });
    }

    setupCloseButton(id) {
        const { closeButton } = this.elements[id];
        if (!closeButton) return;

        const closeButtonElement = document.querySelector(closeButton);
        if (closeButtonElement) {
            closeButtonElement.addEventListener('click', () => this.toggleElement(id, false));
        }
    }

    setupElementListener(element) {
        element.addEventListener('click', event => event.stopPropagation());
    }

    setupBodyListener() {
        document.body.addEventListener('click', () => {
            if (this.currentOpenElement) {
                this.toggleElement(this.currentOpenElement, false);
            }
        });
    }

    toggleElement(id, show, event = null, isModal = false) {
        const elementInfo = this.elements[id];
        if (!elementInfo) return;

        if (this.currentOpenElement && this.currentOpenElement !== id) {
            this.toggleElement(this.currentOpenElement, false);
        }

        const { element } = elementInfo;

        if (show) {
            if (isModal) {
                element.style.display = 'flex';
                this.showModalBackground();
            } else {
                this.positionSubmenu(element, event);
                element.style.display = 'block';
                this.showDarkBackground(element);
            }
            this.currentOpenElement = id;
        } else {
            element.style.display = 'none';
            this.hideBackgrounds();
            this.currentOpenElement = null;
        }

        document.body.classList.toggle('no-scroll', show);
    }

    positionSubmenu(submenu, event) {
        if (!event || window.innerWidth <= 640) return;

        const rect = event.target.getBoundingClientRect();
        const { innerWidth: vw, innerHeight: vh } = window;

        submenu.style.position = "fixed";
        submenu.style.top = `${Math.min(rect.bottom, vh - submenu.offsetHeight)}px`;
        submenu.style.left = `${Math.min(rect.left, vw - submenu.offsetWidth)}px`;
    }

    showModalBackground() {
        let background = document.querySelector('.modalBackground');
        if (!background) {
            background = document.createElement('div');
            background.className = 'modalBackground';
            document.body.appendChild(background);
        }
        background.style.display = 'block';
    }

    showDarkBackground(element) {
        const darkBackground = document.createElement('div');
        darkBackground.className = 'submenu-background';
        darkBackground.style.position = 'fixed';
        darkBackground.style.top = '0';
        darkBackground.style.left = '0';
        darkBackground.style.width = '100vw';
        darkBackground.style.height = '100vh';
        darkBackground.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        darkBackground.style.zIndex = '999';
        document.body.appendChild(darkBackground);

        element._darkBackground = darkBackground;
        element.style.zIndex = '1000';
    }

    hideBackgrounds() {
        const modalBackground = document.querySelector('.modalBackground');
        if (modalBackground) modalBackground.style.display = 'none';

        const darkBackgrounds = document.querySelectorAll('.submenu-background');
        darkBackgrounds.forEach(bg => bg.remove());
    }
}

const uiManager = new UIManager();

function initializeUI() {
    // Modales
    uiManager.addElement('modalinvertir', '#modalinvertir', ['.donar'], '.cerrardonar', true);
    uiManager.addElement('modalproyecto', '#modalproyecto', ['.unirteproyecto'], '.DGFDRDC', true);
    uiManager.addElement('proPro', '#propro', ['.prostatus0'], null, true);
    uiManager.addElement('proProAcciones', '#proproacciones', ['.subpro'], null, true);
    uiManager.addElement('W0512KN', '#a84J76WY', ['#W0512KN'], '#MkzIeq', true);

    // Submenús
    uiManager.addElement('submenusubir', '#submenusubir', ['.subiricono']);
    uiManager.addElement('submenuperfil', '#submenuperfil', ['.mipsubmenu']);
    uiManager.addElement('opcionesrola', '#opcionesrola', ['.HR695R7']);
    uiManager.addElement('opcionespost', '#opcionespost', ['.HR695R8']);
    uiManager.addElement('opcionescolab', '#opcionescolab', ['.submenucolab']);
}

document.addEventListener('DOMContentLoaded', initializeUI);