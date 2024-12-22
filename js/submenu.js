/*

Mira, esto es un toque en la version movil, el problema es que cuando toco un submenu que no es EDYQHV, en version movil, debería abrirse sin problema, pero se abre y se cierra al instante cualquier submenu, y solo funciona si lo mantengo presionado

no se cual es el problema
👇 eventosMenu: Evento 'pointerdown' detectado
submenu.js?ver=0.2.267:36 📱 eventosMenu: Dispositivo móvil detectado
submenu.js?ver=0.2.267:51 ☝️ eventosMenu: Evento 'pointerup' detectado
submenu.js?ver=0.2.267:54 ⏱️ eventosMenu: Temporizador de presionar prolongado limpiado
submenu.js?ver=0.2.267:64 ➡️ eventosMenu: Manejando 'pointerup' para otros submenús
submenu.js?ver=0.2.267:103 🔄 handleSubmenuToggle: Iniciando manejo de toggle de submenú
submenu.js?ver=0.2.267:135 🔑 getSubmenuId: Obteniendo ID de submenú
submenu.js?ver=0.2.267:142 🆔 getSubmenuId: ID de submenú para otros: submenuperfil-default
submenu.js?ver=0.2.267:105 🆔 handleSubmenuToggle: ID de submenú obtenido: submenuperfil-default
submenu.js?ver=0.2.267:111 🔍 handleSubmenuToggle: Submenú encontrado: <div class=​"A1806241 mobile-submenu" id=​"submenuperfil-default" style=​"position:​ fixed;​ z-index:​ 1006;​ display:​ none;​ visibility:​ visible;​ top:​ 174px;​ left:​ 35.5px;​">​…​</div>​
submenu.js?ver=0.2.267:119 📍 handleSubmenuToggle: Posición del submenú establecida: abajo
submenu.js?ver=0.2.267:122 📱 handleSubmenuToggle: Clase 'mobile-submenu' alternada
submenu.js?ver=0.2.267:128 👁️ handleSubmenuToggle: Mostrando submenú
submenu.js?ver=0.2.267:148 👁️ showSubmenu: Mostrando submenú
submenu.js?ver=0.2.267:150 📏 showSubmenu: Ancho de la ventana: 356, Alto de la ventana: 566
submenu.js?ver=0.2.267:161 ⚙️ showSubmenu: Estilos iniciales aplicados al submenú
submenu.js?ver=0.2.267:165 📏 showSubmenu: Ancho del submenú: 285, Alto del submenú: 218
submenu.js?ver=0.2.267:168 📐 showSubmenu: Rectángulo del disparador: DOMRect {x: 294.09375, y: 517.5, width: 26, height: 39, top: 517.5, …}
submenu.js?ver=0.2.267:171 📱 showSubmenu: Posicionando submenú en móvil
submenu.js?ver=0.2.267:190 ✅ showSubmenu: Submenú visible
submenu.js?ver=0.2.267:193 🆔 showSubmenu: Prefijo de ID de submenú: submenuperfil
submenu.js?ver=0.2.267:196 🌓 showSubmenu: Fondo oscuro creado
submenu.js?ver=0.2.267:199 🚫 showSubmenu: Scroll deshabilitado
submenu.js?ver=0.2.267:202 👁️ showSubmenu: Submenú establecido como abierto: <div class=​"A1806241 mobile-submenu" id=​"submenuperfil-default" style=​"position:​ fixed;​ z-index:​ 1006;​ display:​ none;​ visibility:​ visible;​ top:​ 174px;​ left:​ 35.5px;​">​…​</div>​
submenu.js?ver=0.2.267:206 🙈 hideSubmenu: Ocultando submenú
submenu.js?ver=0.2.267:209 ✅ hideSubmenu: Submenú ocultado: <div class=​"A1806241 mobile-submenu" id=​"submenuperfil-default" style=​"position:​ fixed;​ z-index:​ 1006;​ display:​ none;​ visibility:​ visible;​ top:​ 174px;​ left:​ 35.5px;​">​…​</div>​
submenu.js?ver=0.2.267:211 🚫 hideSubmenu: Variable openSubmenu reseteada
*/

let submenuIdPrefixes = [];
let openSubmenu = null;
let longPressTimer;
let isLongPress = false;
let isTouchEvent = false;

function createSubmenu(triggerSelector, submenuIdPrefix, position = 'auto') {
    const triggers = document.querySelectorAll(triggerSelector);
    registrarIdMenu(submenuIdPrefix);
    triggers.forEach(trigger => {
        if (trigger.dataset.submenuInitialized) return;
        eventosMenu(trigger, triggerSelector, submenuIdPrefix, position);
        trigger.dataset.submenuInitialized = 'true';
    });
    cerrarMenu(triggerSelector, submenuIdPrefix);
    resizeMovilMenu(submenuIdPrefix);
}

function registrarIdMenu(submenuIdPrefix) {
    console.log('📌 registrarIdMenu: Iniciando registro de ID de submenú');
    if (!submenuIdPrefixes.includes(submenuIdPrefix)) {
        console.log(`➕ registrarIdMenu: Agregando '${submenuIdPrefix}' a la lista de prefijos de submenús`);
        submenuIdPrefixes.push(submenuIdPrefix);
        console.log(`✅ registrarIdMenu: '${submenuIdPrefix}' agregado exitosamente`);
    } else {
        console.log(`❗ registrarIdMenu: '${submenuIdPrefix}' ya existe en la lista de prefijos`);
    }
}

function eventosMenu(trigger, triggerSelector, submenuIdPrefix, position) {
    console.log('👂 eventosMenu: Configurando eventos para el disparador', trigger);

    trigger.addEventListener('pointerdown', event => {
        console.log("👇 eventosMenu: Evento 'pointerdown' detectado");
        if (window.innerWidth <= 640 && event.pointerType === 'touch') {
            console.log('📱 eventosMenu: Dispositivo móvil detectado');
            isTouchEvent = true;
            isLongPress = false;
            if (triggerSelector === '.EDYQHV') {
                console.log('👆 eventosMenu: Iniciando temporizador de presionar prolongado para .EDYQHV');
                longPressTimer = setTimeout(() => {
                    isLongPress = true;
                    console.log('🕒 eventosMenu: Presionar prolongado detectado en .EDYQHV');
                    handleSubmenuToggle(event, trigger, triggerSelector, submenuIdPrefix, position);
                }, 500);
            }
        }
    });

    trigger.addEventListener('touchend', event => {
        console.log("☝️ eventosMenu: Evento 'touchend' detectado");
        if (isTouchEvent) {
            clearTimeout(longPressTimer);
            console.log('⏱️ eventosMenu: Temporizador de presionar prolongado limpiado');

            if (triggerSelector !== '.EDYQHV') {
                console.log("➡️ eventosMenu: Manejando 'touchend' para otros submenús");
                handleSubmenuToggle(event, trigger, triggerSelector, submenuIdPrefix, position);
            }
        }
        isLongPress = false;
    });

    trigger.addEventListener('click', event => {
        console.log("🖱️ eventosMenu: Evento 'click' detectado");
        if (window.innerWidth > 640) {
            console.log('💻 eventosMenu: Comportamiento normal en escritorio');
            handleSubmenuToggle(event, trigger, triggerSelector, submenuIdPrefix, position);
        } else if (triggerSelector === '.EDYQHV' && isLongPress) {
            console.log('❌ eventosMenu: Previniendo clics en .EDYQHV si fue un presionar prolongado');
            event.preventDefault();
            event.stopPropagation();
        }
    });

    trigger.addEventListener('contextmenu', event => {
        console.log("🖱️ eventosMenu: Evento 'contextmenu' detectado");
        if (window.innerWidth > 640 && triggerSelector === '.EDYQHV') {
            console.log('💻 eventosMenu: Previniendo contextmenu en .EDYQHV en escritorio');
            event.preventDefault();
            handleSubmenuToggle(event, trigger, triggerSelector, submenuIdPrefix, position);
        }
    });
}

function handleSubmenuToggle(event, trigger, triggerSelector, submenuIdPrefix, position) {
    console.log('🔄 handleSubmenuToggle: Iniciando manejo de toggle de submenú');
    const submenuId = getSubmenuId(trigger, triggerSelector, submenuIdPrefix);
    console.log(`🆔 handleSubmenuToggle: ID de submenú obtenido: ${submenuId}`);
    const submenu = document.getElementById(submenuId);
    if (!submenu) {
        console.error('❌ handleSubmenuToggle: Submenú no encontrado:', submenuId);
        return;
    }
    console.log('🔍 handleSubmenuToggle: Submenú encontrado:', submenu);

    if (openSubmenu && openSubmenu !== submenu) {
        console.log('🙈 handleSubmenuToggle: Ocultando submenú abierto previamente');
        hideSubmenu(openSubmenu);
    }

    submenu._position = position;
    console.log(`📍 handleSubmenuToggle: Posición del submenú establecida: ${position}`);

    submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);
    console.log("📱 handleSubmenuToggle: Clase 'mobile-submenu' alternada");

    if (submenu.style.display === 'block') {
        console.log('🙈 handleSubmenuToggle: Ocultando submenú');
        hideSubmenu(submenu);
    } else {
        console.log('👁️ handleSubmenuToggle: Mostrando submenú');
        showSubmenu(event, trigger, submenu, position);
    }
    if (window.innerWidth <= 640) {
        event.stopPropagation(); // Evita que el evento 'click' se propague en móviles
    }
}

function getSubmenuId(trigger, triggerSelector, submenuIdPrefix) {
    console.log('🔑 getSubmenuId: Obteniendo ID de submenú');
    let submenuId;
    if (triggerSelector === '.EDYQHV') {
        submenuId = `${submenuIdPrefix}-${trigger.getAttribute('id-post')}`;
        console.log(`🆔 getSubmenuId: ID de submenú para .EDYQHV: ${submenuId}`);
    } else {
        submenuId = `${submenuIdPrefix}-${trigger.dataset.postId || trigger.id || 'default'}`;
        console.log(`🆔 getSubmenuId: ID de submenú para otros: ${submenuId}`);
    }
    return submenuId;
}

function showSubmenu(event, trigger, submenu, position) {
    console.log('👁️ showSubmenu: Mostrando submenú');
    const {innerWidth: vw, innerHeight: vh} = window;
    console.log(`📏 showSubmenu: Ancho de la ventana: ${vw}, Alto de la ventana: ${vh}`);

    if (submenu.parentNode !== document.body) {
        console.log('🔄 showSubmenu: Moviendo submenú al body');
        document.body.appendChild(submenu);
    }

    submenu.style.position = 'fixed';
    submenu.style.zIndex = 1006;
    submenu.style.display = 'block';
    submenu.style.visibility = 'hidden';
    console.log('⚙️ showSubmenu: Estilos iniciales aplicados al submenú');

    let submenuWidth = submenu.offsetWidth;
    let submenuHeight = submenu.offsetHeight;
    console.log(`📏 showSubmenu: Ancho del submenú: ${submenuWidth}, Alto del submenú: ${submenuHeight}`);

    const rect = trigger.getBoundingClientRect();
    console.log('📐 showSubmenu: Rectángulo del disparador:', rect);

    if (vw <= 640) {
        console.log('📱 showSubmenu: Posicionando submenú en móvil');
        submenu.style.top = `${(vh - submenuHeight) / 2}px`;
        submenu.style.left = `${(vw - submenuWidth) / 2}px`;
    } else {
        console.log('💻 showSubmenu: Posicionando submenú en escritorio');
        let {top, left} = calculatePosition(rect, submenuWidth, submenuHeight, position);
        console.log(`📐 showSubmenu: Posición calculada: top: ${top}, left: ${left}`);

        if (top + submenuHeight > vh) top = vh - submenuHeight;
        if (left + submenuWidth > vw) left = vw - submenuWidth;
        if (top < 0) top = 0;
        if (left < 0) left = 0;
        console.log(`📐 showSubmenu: Posición ajustada: top: ${top}, left: ${left}`);

        submenu.style.top = `${top}px`;
        submenu.style.left = `${left}px`;
    }

    submenu.style.visibility = 'visible';
    console.log('✅ showSubmenu: Submenú visible');

    const submenuIdPrefix = submenu.id.split('-')[0];
    console.log(`🆔 showSubmenu: Prefijo de ID de submenú: ${submenuIdPrefix}`);

    createSubmenuDarkBackground(submenuIdPrefix);
    console.log('🌓 showSubmenu: Fondo oscuro creado');

    document.body.classList.add('no-scroll');
    console.log('🚫 showSubmenu: Scroll deshabilitado');

    openSubmenu = submenu;
    console.log('👁️ showSubmenu: Submenú establecido como abierto:', openSubmenu);
}

function hideSubmenu(submenu) {
    console.log('🙈 hideSubmenu: Ocultando submenú');
    if (submenu) {
        submenu.style.display = 'none';
        console.log('✅ hideSubmenu: Submenú ocultado:', submenu);
        openSubmenu = null;
        console.log('🚫 hideSubmenu: Variable openSubmenu reseteada');
    }

    removeSubmenuDarkBackground();
    console.log('⚪ hideSubmenu: Fondo oscuro eliminado');

    const activeSubmenus = Array.from(document.querySelectorAll(`[id^="${submenuIdPrefixes[0]}-"]`)).filter(menu => menu.style.display === 'block');
    console.log(`🔍 hideSubmenu: Submenús activos encontrados: ${activeSubmenus.length}`);

    if (activeSubmenus.length === 0) {
        console.log('🔄 hideSubmenu: Restaurando scroll');
        document.body.classList.remove('no-scroll');
    }
}

function cerrarMenu(triggerSelector, submenuIdPrefix) {
    console.log('🚪 cerrarMenu: Configurando evento para cerrar menús');
    document.addEventListener('click', event => {
        console.log("🖱️ cerrarMenu: Evento 'click' detectado en el documento");
        document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
            console.log('🔍 cerrarMenu: Revisando submenú:', submenu);
            if (submenu.style.display === 'block' && !submenu.contains(event.target) && !event.target.closest(triggerSelector) && !event.target.closest('a')) {
                console.log('🙈 cerrarMenu: Ocultando submenú:', submenu);
                hideSubmenu(submenu);
            }
        });
    });
}

function resizeMovilMenu(submenuIdPrefix) {
    console.log('🔄 resizeMovilMenu: Configurando evento de redimensionamiento');
    window.addEventListener('resize', () => {
        console.log("↔️ resizeMovilMenu: Evento 'resize' detectado");
        document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
            console.log("📱 resizeMovilMenu: Alternando clase 'mobile-submenu' en:", submenu);
            submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);
        });
    });
}

window.hideAllSubmenus = function () {
    console.log('🔄 Ejecutando hideAllSubmenus (versión simplificada)');
    submenuIdPrefixes.forEach(prefix => {
        console.log(`🔍 Buscando submenús con el prefijo '${prefix}-'`);
        const allSubmenus = document.querySelectorAll(`[id^="${prefix}-"]`);
        if (allSubmenus.length === 0) {
            console.log(`❗ No se encontraron submenús con el prefijo '${prefix}-'.`);
        } else {
            console.log(`✔️ Se encontraron ${allSubmenus.length} submenús con el prefijo '${prefix}-':`, allSubmenus);
            allSubmenus.forEach((submenu, index) => {
                console.log(`🙈 Ocultando submenú ${index + 1} con prefijo '${prefix}-' (ID: ${submenu.id}):`, submenu);
                hideSubmenu(submenu);
            });
        }
    });
    console.log('✅ hideAllSubmenus (versión simplificada) finalizado');
};

function submenu() {
    createSubmenu('.filtrosboton', 'filtrosMenu', 'abajo');
    createSubmenu('.mipsubmenu', 'submenuperfil', 'abajo');
    createSubmenu('.HR695R7', 'opcionesrola', 'abajo');
    createSubmenu('.HR695R8', 'opcionespost', 'abajo');
    createSubmenu('.submenucolab', 'opcionescolab', 'abajo');
    createSubmenu('.EDYQHV', 'opcionespost', 'abajo');
}

window.createSubmenuDarkBackground = function (submenuIdPrefix) {
    // Añade el parámetro submenuIdPrefix
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
            // Ahora submenuIdPrefix está disponible aquí
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
