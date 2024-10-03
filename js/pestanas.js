function mostrarPestana(id) {
    document.querySelectorAll('.tab-content .tab').forEach(tab => {
        tab.style.display = 'none';
        tab.classList.remove('active');
    });

    const targetTab = document.querySelector(id);
    if (!targetTab) {
        console.error(`No se encontró la pestaña con ID ${id}.`);
        return;
    }

    document.querySelectorAll('.tab-links li').forEach(li => li.classList.remove('active'));

    targetTab.style.display = 'block';
    targetTab.classList.add('active');

    document.querySelector(`.tab-links a[href="${id}"]`).parentNode.classList.add('active');

    document.getElementById('menuData').setAttribute('pestanaActual', id.replace('#', ''));
}

function inicializarPestanas() {
    asignarPestanas();

    const hash = window.location.hash;
    const targetId = hash && document.querySelector(hash) ? hash : '#' + document.querySelector('.tab-content .tab').id;
    mostrarPestana(targetId);

    document.querySelectorAll('.tab-links a').forEach(a => {
        a.addEventListener('click', function(e) {
            e.preventDefault();
            mostrarPestana(this.getAttribute('href'));
        });
    });
}

function asignarPestanas() {
    const menuData = document.getElementById('menuData');
    const adaptableTabs = document.getElementById('adaptableTabs');

    if (menuData && adaptableTabs) {
        adaptableTabs.innerHTML = '';

        menuData.querySelectorAll('[data-tab]').forEach((tab, index) => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            const tabName = tab.getAttribute('data-tab');

            a.href = '#' + tabName;
            a.textContent = tabName.charAt(0).toUpperCase() + tabName.slice(1);

            if (index === 0) li.classList.add('active');

            li.appendChild(a);
            adaptableTabs.appendChild(li);
        });
    }
}