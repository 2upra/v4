function meta() {
    const meta = 5000;
    const recaudado = 612;
    const porcentaje = (recaudado / meta) * 100;

    document.querySelector('.progress-barA1').style.width = porcentaje + '%';
}


