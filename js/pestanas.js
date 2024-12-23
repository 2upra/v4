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

    // Actualizar la URL y el título de la página
    window.location.hash = id;
    const tabName = id.substring(1); // Eliminar el #
    document.title = tabName.charAt(0).toUpperCase() + tabName.slice(1);
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
        // Actualizar la URL si no hay hash pero hay pestañas
        window.location.hash = targetId;
    }

    if (targetId) {
        mostrarPestana(targetId);
    }

    // Modificación: Seleccionar los enlaces de ambos contenedores
    const enlaces = document.querySelectorAll('#adaptableTabs a, #adaptableTabsPerfil a');
    if (enlaces.length > 0) {
        enlaces.forEach(a => {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                mostrarPestana(this.getAttribute('href'));
            });
        });
    }
}

function asignarPestanas() {
    const menuData = document.getElementById('menuData');
    const adaptableTabs = document.getElementById('adaptableTabs');
    const adaptableTabsPerfil = document.getElementById('adaptableTabsPerfil');
    const perfilTab = document.querySelector('[data-tab="Perfil"]');

    if (menuData) {
        // Modificación: Determinar qué contenedor usar
        const targetContainer = perfilTab ? adaptableTabsPerfil : adaptableTabs;

        if (targetContainer) {
            targetContainer.innerHTML = '';

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
                targetContainer.appendChild(li);
            });
        } else {
            console.warn('Contenedor de pestañas no encontrado en el DOM.');
        }
    } else {
        console.warn('Elemento #menuData no encontrado en el DOM.');
    }
}

