function meta() {
    const meta = 1000;
    const recaudado = 632;
    const porcentaje = (recaudado / meta) * 100;

    const progressBar = document.querySelector('.progress-barA1');

    if (!progressBar) {
        return;
    }

    progressBar.style.width = porcentaje + '%';
}

