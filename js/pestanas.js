function mostrarPestana(id) {
    const pestañas = document.querySelectorAll('.tab-content .tab');
    if (pestañas.length === 0) {
        console.warn('No hay pestañas disponibles para mostrar.');
        return;
    }

    pestañas.forEach(tab => {
        tab.style.display = 'none';
        tab.classList.remove('active');
    });

    const targetTab = document.querySelector(id);
    if (!targetTab) {
        console.error(`No se encontró la pestaña con ID ${id}.`);
        return;
    }

    // Remover clase 'active' de todos los enlaces de pestañas
    const enlaces = document.querySelectorAll('.tab-links li');
    if (enlaces.length > 0) {
        enlaces.forEach(li => li.classList.remove('active'));
    }

    targetTab.style.display = 'block';
    targetTab.classList.add('active');

    // Añadir clase 'active' al enlace correspondiente si existe
    const enlaceActivo = document.querySelector(`.tab-links a[href="${id}"]`);
    if (enlaceActivo && enlaceActivo.parentNode) {
        enlaceActivo.parentNode.classList.add('active');
    }

    const menuData = document.getElementById('menuData');
    if (menuData) {
        menuData.setAttribute('pestanaActual', id.replace('#', ''));
    }
}

function inicializarPestanas() {
    asignarPestanas();

    const pestañasExistentes = document.querySelectorAll('.tab-content .tab');
    const hash = window.location.hash;
    let targetId = '';

    if (hash && document.querySelector(hash)) {
        targetId = hash;
    } else if (pestañasExistentes.length > 0) {
        targetId = '#' + pestañasExistentes[0].id;
    }

    if (targetId) {
        mostrarPestana(targetId);
    }

    const enlaces = document.querySelectorAll('.tab-links a');
    if (enlaces.length > 0) {
        enlaces.forEach(a => {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                detenerCarga(); // Evita mostrar contenido que se estaba cargando en la otra pestaña que se acaba de abrir
                mostrarPestana(this.getAttribute('href'));
            });
        });
    }
}

function asignarPestanas() {
    const menuData = document.getElementById('menuData');
    const adaptableTabs = document.getElementById('adaptableTabs');

    if (menuData && adaptableTabs) {
        adaptableTabs.innerHTML = '';

        const tabs = menuData.querySelectorAll('[data-tab]');
        if (tabs.length === 0) {
            console.warn('No se encontraron elementos con [data-tab] para asignar pestañas.');
            return;
        }

        tabs.forEach((tab, index) => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            const tabName = tab.getAttribute('data-tab');

            a.href = '#' + tabName;
            a.textContent = tabName.charAt(0).toUpperCase() + tabName.slice(1);

            if (index === 0) li.classList.add('active');

            li.appendChild(a);
            adaptableTabs.appendChild(li);
        });
    } else {
        console.warn('Elementos #menuData o #adaptableTabs no encontrados en el DOM.');
    }
}
