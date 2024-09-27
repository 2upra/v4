class OverlayManager {
    constructor() {
        this.overlays = {};
        this.currentOpenOverlay = null;
        this.setupBodyListener();

        window.addEventListener('resize', () => {
            Object.values(this.overlays).forEach(overlayInfo => {
                const {element, options} = overlayInfo;
                if (options.mobileClass) {
                    element.classList.toggle(options.mobileClass, window.innerWidth <= 640);
                }
            });
        });
    }

    addOverlay(id, elementSelector, triggers, options = {}) {
        const element = document.querySelector(elementSelector);
        if (!element) {
            console.warn(Overlay element not found: ${id});
            return;
        }

        const triggerElements = triggers.map(trigger => {
            if (typeof trigger === 'string') {
                return document.querySelector(trigger);
            } else {
                return trigger;
            }
        }).filter(Boolean);

        if (triggerElements.length === 0) {
            console.warn(No triggers found for overlay id: ${id});
            return;
        }

        this.overlays[id] = {
            element,
            triggers: triggerElements,
            options
        };

        this.setupTriggers(id);
        if (options.closeButtonSelector) {
            this.setupCloseButton(id);
        }
        this.setupElementListener(element);
    }

    setupTriggers(overlayId) {
        const {triggers} = this.overlays[overlayId];
        if (!triggers || triggers.length === 0) return;

        triggers.forEach(trigger => {
            trigger.addEventListener('click', event => {
                event.stopPropagation();
                this.toggleOverlay(overlayId, true, event, trigger);
            });
        });
    }

    setupCloseButton(overlayId) {
        const {options} = this.overlays[overlayId];
        const closeButtonSelector = options.closeButtonSelector;
        if (!closeButtonSelector) return;

        const closeButtonElement = document.querySelector(closeButtonSelector);
        if (closeButtonElement) {
            closeButtonElement.addEventListener('click', event => {
                event.stopPropagation();
                this.toggleOverlay(overlayId, false);
            });
        } else {
            console.warn(Close button element not found for overlay id: ${overlayId});
        }
    }

    setupElementListener(element) {
        element.addEventListener('click', event => event.stopPropagation());
    }

    setupBodyListener() {
        document.body.addEventListener('click', event => {
            if (this.currentOpenOverlay) {
                const overlayInfo = this.overlays[this.currentOpenOverlay];
                if (!overlayInfo.element.contains(event.target)) {
                    this.closeAllOverlays();
                }
            }
        });
    }

    toggleOverlay(overlayId, show, event = null, triggerElement = null) {
        const overlayInfo = this.overlays[overlayId];
        if (!overlayInfo) {
            console.warn(Overlay info not found for id: ${overlayId});
            return;
        }

        if (this.currentOpenOverlay && this.currentOpenOverlay !== overlayId) {
            this.toggleOverlay(this.currentOpenOverlay, false);
        }

        const {element, options} = overlayInfo;

        if (show) {
            if (options.isModal) {
                element.style.display = 'flex';
            } else {
                this.positionElement(element, triggerElement, options);
                element.style.display = 'block';
            }

            if (options.generateBackground) {
                this.createBackgroundOverlay(element, options.mobileClass);
            }

            this.currentOpenOverlay = overlayId;
        } else {
            element.style.display = 'none';
            this.removeBackgroundOverlay(element);
            this.currentOpenOverlay = null;
        }
    }

    positionElement(element, triggerElement, options) {
        if (!triggerElement) return;

        const rect = triggerElement.getBoundingClientRect();
        const { innerWidth: vw, innerHeight: vh } = window;

        const adjustTop = options.positionAdjust && options.positionAdjust.top || 0;
        const adjustLeft = options.positionAdjust && options.positionAdjust.left || 0;

        if (vw > 640) {
            element.style.position = "fixed";
            element.style.top = ${Math.min(rect.bottom + adjustTop, vh - element.offsetHeight)}px;
            element.style.left = ${Math.min(rect.left + adjustLeft, vw - element.offsetWidth)}px;
        }

        if (options.mobileClass && vw <= 640) {
            element.classList.add(options.mobileClass);
        } else if (options.mobileClass) {
            element.classList.remove(options.mobileClass);
        }
    }

    createBackgroundOverlay(element, mobileClass = null) {
        const backgroundOverlay = document.createElement('div');
        backgroundOverlay.classList.add('overlay-background');
        backgroundOverlay.style.position = 'fixed';
        backgroundOverlay.style.top = 0;
        backgroundOverlay.style.left = 0;
        backgroundOverlay.style.width = '100vw';
        backgroundOverlay.style.height = '100vh';
        backgroundOverlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        backgroundOverlay.style.zIndex = 999;
        backgroundOverlay.style.pointerEvents = 'none';

        element.parentElement.appendChild(backgroundOverlay);

        element._backgroundOverlay = backgroundOverlay;
        element.style.zIndex = 1000;

        document.body.classList.add('no-scroll');

        if (mobileClass && window.innerWidth <= 640) {
            element.classList.add(mobileClass);
        }
    }

    removeBackgroundOverlay(element) {
        if (element._backgroundOverlay) {
            element._backgroundOverlay.remove();
            element._backgroundOverlay = null;
        }

        // Check if any other overlay is open
        const activeOverlays = Object.values(this.overlays).filter(overlayInfo => overlayInfo.element.style.display !== 'none');

        if (activeOverlays.length === 0) {
            document.body.classList.remove('no-scroll');
        }
    }

    closeAllOverlays() {
        Object.keys(this.overlays).forEach(overlayId => this.toggleOverlay(overlayId, false));
        this.currentOpenOverlay = null;
    }
}

const overlayManager = new OverlayManager();

function smooth() {
    overlayManager.addOverlay('modalinvertir', '#modalinvertir', ['.donar'], {
        closeButtonSelector: '.cerrardonar',
        isModal: true,
        generateBackground: true
    });
    overlayManager.addOverlay('modalproyecto', '#modalproyecto', ['.unirteproyecto'], {
        closeButtonSelector: '.DGFDRDC',
        isModal: true,
        generateBackground: true
    });
    overlayManager.addOverlay('proPro', '#propro', ['.prostatus0'], {
        isModal: true,
        generateBackground: true
    });
    overlayManager.addOverlay('proProAcciones', '#proproacciones', ['.subpro'], {
        isModal: true,
        generateBackground: true
    });
    overlayManager.addOverlay('W0512KN', '#a84J76WY', ['#W0512KN'], {
        closeButtonSelector: '#MkzIeq',
        isModal: true,
        generateBackground: true
    });
}

function createSubmenu(triggerSelector, submenuIdPrefix, adjustTop = 0, adjustLeft = 0) {
    const triggers = document.querySelectorAll(triggerSelector);
    
    triggers.forEach(trigger => {
        const submenuId = ${submenuIdPrefix}-${trigger.dataset.postId || trigger.id || "default"};
        const submenuSelector = #${submenuId};
        
        overlayManager.addOverlay(submenuId, submenuSelector, [trigger], {
            isModal: false,
            generateBackground: true,
            positionAdjust: { top: adjustTop, left: adjustLeft },
            mobileClass: 'mobile-submenu'
        });
    });
}

function initializeStaticMenus() {
    createSubmenu(".subiricono", "submenusubir", 0, 120);
}

function submenu() {
    createSubmenu(".mipsubmenu", "submenuperfil", 0, 120);
    createSubmenu(".HR695R7", "opcionesrola", 100, 0);
    createSubmenu(".HR695R8", "opcionespost", 60, 0);
    createSubmenu(".submenucolab", "opcionescolab", 60, 0);
}

document.addEventListener('DOMContentLoaded', () => {
    initializeStaticMenus();
});