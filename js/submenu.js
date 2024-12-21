let submenuIdPrefixes = [];

function createSubmenu(triggerSelector, submenuIdPrefix, position = 'auto') {
    const triggers = document.querySelectorAll(triggerSelector);
    let openSubmenu = null;
    let longPressTimer;
    let isLongPress = false; // Flag para saber si fue una pulsación larga

    if (!submenuIdPrefixes.includes(submenuIdPrefix)) {
        submenuIdPrefixes.push(submenuIdPrefix);
    }

    function toggleSubmenu(event) {
        const trigger = event.target.closest(triggerSelector);
        if (!trigger) return;
        
        let submenuId;
        
        if (triggerSelector === '.EDYQHV') {
           submenuId = `${submenuIdPrefix}-${trigger.getAttribute('id-post')}`;
        } else {
            submenuId = `${submenuIdPrefix}-${trigger.dataset.postId || trigger.id || 'default'}`;
        }

        const submenu = document.getElementById(submenuId);
        
        if (!submenu) {
            console.error("Submenu not found:", submenuId);
            return;
        }
        
        if (openSubmenu && openSubmenu !== submenu) {
            hideSubmenu(openSubmenu);
        }

        submenu._position = position;

        submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);

        if (submenu.style.display === 'block') {
            hideSubmenu(submenu);
        } else {
            showSubmenu(event, trigger, submenu, submenu._position);
        }

        event.stopPropagation();
    }

    function showSubmenu(event, trigger, submenu, position) {
        const {innerWidth: vw, innerHeight: vh} = window;

        if (submenu.parentNode !== document.body) {
            document.body.appendChild(submenu);
        }

        submenu.style.position = 'fixed';
        submenu.style.zIndex = 1006;

        submenu.style.display = 'block';
        submenu.style.visibility = 'hidden';

        let submenuWidth = submenu.offsetWidth;
        let submenuHeight = submenu.offsetHeight;

        const rect = trigger.getBoundingClientRect();

        if (vw <= 640) {
            submenu.style.top = `${(vh - submenuHeight) / 2}px`;
            submenu.style.left = `${(vw - submenuWidth) / 2}px`;
        } else {
            let {top, left} = calculatePosition(rect, submenuWidth, submenuHeight, position);

            if (top + submenuHeight > vh) top = vh - submenuHeight;
            if (left + submenuWidth > vw) left = vw - submenuWidth;
            if (top < 0) top = 0;
            if (left < 0) left = 0;

            submenu.style.top = `${top}px`;
            submenu.style.left = `${left}px`;
        }

        submenu.style.visibility = 'visible';

        createSubmenuDarkBackground();

        document.body.classList.add('no-scroll');

        openSubmenu = submenu;
    }

    function hideSubmenu(submenu) {
        if (submenu) {
            submenu.style.display = 'none';
            openSubmenu = null;
        }

        removeSubmenuDarkBackground();

        const activeSubmenus = Array.from(document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`)).filter(menu => menu.style.display === 'block');

        if (activeSubmenus.length === 0) {
            document.body.classList.remove('no-scroll');
        }
    }

    window.hideAllSubmenus = function () {
        console.log('Ejecutando hideAllSubmenus (versión simplificada)');

        submenuIdPrefixes.forEach(prefix => {
            const allSubmenus = document.querySelectorAll(`[id^="${prefix}-"]`);

            if (allSubmenus.length === 0) {
                console.log(`No se encontraron submenús con el prefijo '${prefix}-'.`);
            } else {
                console.log(`Se encontraron ${allSubmenus.length} submenús con el prefijo '${prefix}-':`, allSubmenus);
                allSubmenus.forEach((submenu, index) => {
                    console.log(`Ocultando submenú ${index + 1} con prefijo '${prefix}-':`, submenu);
                    hideSubmenu(submenu);
                });
            }
        });

        console.log('hideAllSubmenus (versión simplificada) finalizado');
    };

    triggers.forEach(trigger => {
        if (trigger.dataset.submenuInitialized) return;

        trigger.addEventListener('click', event => {
            if (window.innerWidth > 640 || !isLongPress) {
                toggleSubmenu(event);
            }
            isLongPress = false;
        });

        if (triggerSelector === '.EDYQHV') {
            let isTouchEvent = false;

            trigger.addEventListener('pointerdown', event => {
                if (event.pointerType === 'touch') {
                    isTouchEvent = true;
                    isLongPress = false;
                    longPressTimer = setTimeout(() => {
                        isLongPress = true;
                        toggleSubmenu(event);
                    }, 500);
                }
            });

            trigger.addEventListener('pointerup', event => {
                if (isTouchEvent) {
                    clearTimeout(longPressTimer);
                }
            });

            trigger.addEventListener('pointermove', event => {
                if (isTouchEvent) {
                    clearTimeout(longPressTimer);
                    isLongPress = false;
                }
            });
            
            trigger.addEventListener('click', event => {
              if (isLongPress) {
                event.preventDefault();
                event.stopPropagation();
              }
            })
        }

        trigger.dataset.submenuInitialized = 'true';
    });

    document.addEventListener('click', event => {
        document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
            if (submenu.style.display === 'block' && !submenu.contains(event.target) && !event.target.closest(triggerSelector) && !event.target.closest('a')) {
                hideSubmenu(submenu);
            }
        });
    });

    window.addEventListener('resize', () => {
        document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
            submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);
        });
    });
}

function submenu() {
    createSubmenu('.filtrosboton', 'filtrosMenu', 'abajo');
    createSubmenu('.mipsubmenu', 'submenuperfil', 'abajo');
    createSubmenu('.HR695R7', 'opcionesrola', 'abajo');
    createSubmenu('.HR695R8', 'opcionespost', 'abajo');
    createSubmenu('.submenucolab', 'opcionescolab', 'abajo');
    createSubmenu('.EDYQHV', 'opcionespost', 'abajo');
}

window.createSubmenuDarkBackground = function () {
    let darkBackground = document.getElementById('submenu-background5322');
    if (!darkBackground) {
        // Crear el fondo oscuro si no existe
        darkBackground = document.createElement('div');
        darkBackground.id = 'submenu-background5322';
        darkBackground.style.position = 'fixed';
        darkBackground.style.top = 0;
        darkBackground.style.left = 0;
        darkBackground.style.width = '100%';
        darkBackground.style.height = '100%';
        darkBackground.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        darkBackground.style.zIndex = 1002;
        darkBackground.style.display = 'none';
        darkBackground.style.pointerEvents = 'none';
        darkBackground.style.opacity = '0';
        darkBackground.style.transition = 'opacity 0.3s ease';
        document.body.appendChild(darkBackground);

        // Agregar evento para cerrar submenús al hacer clic en el fondo oscuro
        darkBackground.addEventListener('click', () => {
            document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
                hideSubmenu(submenu);
            });
        });
    }

    darkBackground.style.display = 'block';
    setTimeout(() => {
        darkBackground.style.opacity = '1';
    }, 10);
    darkBackground.style.pointerEvents = 'auto';
};

// Eliminar el fondo oscuro
window.removeSubmenuDarkBackground = function () {
    const darkBackground = document.getElementById('submenu-background5322');
    if (darkBackground) {
        darkBackground.style.opacity = '0';
        setTimeout(() => {
            darkBackground.style.display = 'none';
            darkBackground.style.pointerEvents = 'none';
        }, 300);
    }
};

function calculatePosition(rect, submenuWidth, submenuHeight, position) {
    const {innerWidth: vw, innerHeight: vh} = window;
    let top, left;

    switch (position) {
        case 'arriba':
            top = rect.top - submenuHeight;
            left = rect.left + rect.width / 2 - submenuWidth / 2;
            break;
        case 'abajo':
            top = rect.bottom;
            left = rect.left + rect.width / 2 - submenuWidth / 2;
            break;
        case 'izquierda':
            top = rect.top + rect.height / 2 - submenuHeight / 2;
            left = rect.left - submenuWidth;
            break;
        case 'derecha':
            top = rect.top + rect.height / 2 - submenuHeight / 2;
            left = rect.right;
            break;
        case 'centro':
            top = (vh - submenuHeight) / 2;
            left = (vw - submenuWidth) / 2;
            break;
        default:
            // 'auto' o cualquier otro valor: intentar posicionar debajo del trigger
            top = rect.bottom;
            left = rect.left;
            break;
    }

    return {top, left};
}

function initializeStaticMenus() {
    // Ejemplos de uso con la nueva parametrización de posición
    createSubmenu('.chatIcono', 'bloqueConversaciones', 'abajo');
    createSubmenu('.fotoperfilsub', 'fotoperfilsub', 'abajo');
}

// Esto se reinicia cada vez que cargan nuevos posts

document.addEventListener('DOMContentLoaded', () => {
    initializeStaticMenus();
});
