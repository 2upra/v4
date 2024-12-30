function ordenarListaMasonry(lista) {
    if (!lista) return;

    const todosElementos = lista.querySelectorAll('li');
    todosElementos.forEach(elemento => elemento.classList.remove('organizado'));

    const elementos = lista.querySelectorAll('li:not(.organizado)');
    const numColumnas = 2;
    const gap = 10;
    const anchoLista = lista.offsetWidth;
    const anchoColumna = Math.round((anchoLista - gap * (numColumnas - 1)) / numColumnas) - 2;

    let alturasColumnas = new Array(numColumnas).fill(0);

    elementos.forEach(elemento => {
        const colIndex = alturasColumnas.indexOf(Math.min(...alturasColumnas));

        // Aplicar el gap a todos los elementos excepto al primero de cada columna
        if (alturasColumnas[colIndex] !== 0) {
            alturasColumnas[colIndex] += gap;
        }

        elemento.style.position = 'absolute';
        elemento.style.top = alturasColumnas[colIndex] + 'px';
        elemento.style.left = (colIndex * (anchoColumna + gap)) + 'px';
        elemento.style.width = anchoColumna + 'px';

        // Actualizar la altura de la columna
        alturasColumnas[colIndex] += elemento.offsetHeight;

        elemento.classList.add('organizado');
    });

    lista.style.position = 'relative';
    lista.style.height = Math.max(...alturasColumnas) + 'px';
}

function iniciarMasonry() {
    const listas = document.querySelectorAll('ul.masonary');
    listas.forEach(lista => {
        ordenarListaMasonry(lista);

        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.type === 'childList') {
                    ordenarListaMasonry(lista);
                }
            });
        });

        observer.observe(lista, { childList: true, subtree: true });
    });
}

window.addEventListener('load', iniciarMasonry);
window.addEventListener('resize', iniciarMasonry);