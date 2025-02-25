function stripecompra() {
    const btnComprar = document.getElementById('botonComprar');
    const cantCompra = document.getElementById('cantidadCompra');
    const cantReal = document.getElementById('cantidadReal');
    const msjCant = document.querySelector('.ETXLXB');

    function log(msg) {
        console.log(`stripecompra: ${msg}`);
    }

    function formatearCant(input) {
        const valor = input.value.replace(/[^0-9]/g, '');
        input.value = valor ? `$${valor}` : '';
    }

    if (cantCompra) {
        cantCompra.addEventListener('input', () => {
            formatearCant(cantCompra);
            if (cantReal) {
                cantReal.value = cantCompra.value.replace(/[^0-9]/g, '');
            }
        });
    }

    function titilarBorde(input) {
        const parpadeos = 6;
        const velParpadeo = 200;

        function alternarBorde(count) {
            if (count > 0) {
                input.style.border = input.style.border === '2px solid white' ? '2px solid transparent' : '2px solid white';
                setTimeout(() => {
                    alternarBorde(count - 1);
                }, velParpadeo);
            } else {
                input.style.border = '';
            }
        }

        alternarBorde(parpadeos);
    }

    function manejarErrorCant() {
        if (msjCant) {
            msjCant.style.display = 'block';
        }
        if (cantCompra) {
            titilarBorde(cantCompra);
        }
        log('Cantidad inválida.'); // Log del error
    }

    if (btnComprar) {
        btnComprar.addEventListener('click', e => {
            e.preventDefault();

            const usrId = document.getElementById('userID') ? document.getElementById('userID').value : null;
            const cant = cantReal ? cantReal.value : null;

            if (!cant || isNaN(cant) || cant <= 0) {
                manejarErrorCant();
                return;
            }
            if (!usrId) {
                alert('No se pudo obtener el ID de usuario, por favor verifica iniciar sesión o cambiar de navegador.');
                log('userID es null.'); // Log del error.
                return;
            }

            log(`Iniciando fetch. userId: ${usrId}, cantidad: ${cant}`); // Log antes del fetch

            fetch('/wp-json/avada/v1/crear_sesion_acciones', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    userId: usrId,
                    cantidadCompra: cant
                })
            })
                .then(response => {
                    log(`Respuesta inicial recibida. Status: ${response.status}`); // Log de la respuesta inicial
                    if (!response.ok) {
                        // Importante, verifica si la respuesta es OK.
                        log(`Respuesta NO OK. Status: ${response.status}`);
                        throw new Error(`HTTP error! status: ${response.status}`); // Lanza error para ser capturado en el .catch
                    }
                    return response.json();
                })
                .then(sessionData => {
                    log(`Respuesta JSON parseada: ${JSON.stringify(sessionData)}`); // Log del JSON parseado.

                    if (sessionData.id) {
                        const stripe = Stripe('pk_live_51M9uLoCdHJpmDkrr3ZHrVnDdA7pCZ676l1k8dKpNLSiOKG8pvKYYlCI8RaHtNqYERwpZ4qwOhdrPnLW6NgsQyX8H0019HdwAY9');
                        stripe.redirectToCheckout({sessionId: sessionData.id}).catch(error => {
                            console.error('Error en redirectToCheckout:', error);
                            log(`Error en redirectToCheckout: ${error}`); // Log específico para redirectToCheckout.
                            alert('Hubo un problema al redirigir al checkout de Stripe.');
                        });
                    } else {
                        console.error('Respuesta completa:', sessionData);
                        log(`Respuesta de sesión sin ID: ${JSON.stringify(sessionData)}`);
                        alert('Hubo un problema al procesar la compra. Por favor, inténtalo de nuevo.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    log(`Error en fetch: ${error}`); // Log del error en el fetch.
                    alert('Hubo un error al conectar con el sistema de compras. Por favor, verifica tu conexión y vuelve a intentarlo.');
                });
        });
    }
}
