class UIManager {
    constructor() {
        this.elements = {};
        this.currentOpenElement = null;
        this.setupBodyListener();
    }

    addElement(id, elementSelector, triggerSelectors, closeButtonSelector = null, isModal = false) {
        const elements = document.querySelectorAll(elementSelector);
        if (elements.length === 0) {
            console.warn(`Elementos no encontrados: ${id}`);
            return;
        }
    
        elements.forEach((element, index) => {
            const uniqueId = `${id}-${index}`;
            const triggers = triggerSelectors
                .map(selector => Array.from(document.querySelectorAll(selector)))
                .flat()
                .filter(el => el instanceof Element);
    
            if (triggers.length === 0) {
                console.warn(`No se encontraron triggers válidos para: ${uniqueId}`);
                return;
            }
    
            this.elements[uniqueId] = { element, triggers, closeButton: closeButtonSelector, isModal };
    
            this.setupTriggers(uniqueId);
            this.setupCloseButton(uniqueId);
            this.setupElementListener(element);
        });
    }

    setupTriggers(id) {
        const { triggers, element, isModal } = this.elements[id];
        triggers.forEach(trigger => {
            if (trigger && trigger instanceof Element) {
                trigger.addEventListener('click', event => {
                    event.stopPropagation();
                    // Asegúrate de que el trigger está asociado con este elemento específico
                    if (trigger.closest(element.tagName) === element || 
                        (trigger.getAttribute('data-post-id') && 
                         element.id === `opcionespost-${trigger.getAttribute('data-post-id')}`)) {
                        this.toggleElement(id, true, event, isModal);
                    }
                });
            } else {
                console.warn(`Trigger inválido para ${id}:`, trigger);
            }
        });
    }

    setupCloseButton(id) {
        const { closeButton, element } = this.elements[id];
        if (!closeButton) return;

        const closeButtonElement = element.querySelector(closeButton);
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

        const trigger = event.target.closest('.opcionespost');
        if (!trigger) return;

        const rect = trigger.getBoundingClientRect();
        const { innerWidth: vw, innerHeight: vh } = window;

        submenu.style.position = "absolute";
        submenu.style.top = `${Math.min(rect.bottom - rect.top, vh - submenu.offsetHeight)}px`;
        submenu.style.left = `${Math.min(rect.left - submenu.offsetWidth / 2 + rect.width / 2, vw - submenu.offsetWidth)}px`;
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
    uiManager.addElement('opcionespost', '.A1806241', ['.opcionespost']);
    uiManager.addElement('opcionescolab', '#opcionescolab', ['.submenucolab']);

    console.log('UI initialized');
}
