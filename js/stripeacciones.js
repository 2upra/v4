function stripecompra() {
    // Obtener elementos del DOM dentro de la función
    var botonComprar = document.getElementById('botonComprar');
    var cantidadCompra = document.getElementById('cantidadCompra');
    var cantidadReal = document.getElementById('cantidadReal');

    // Función para formatear la cantidad con el símbolo de dólar
    function formatearCantidad(input) {
        var value = input.value.replace(/[^0-9]/g, '');
        input.value = value ? `$${value}` : '';
    }

    // Configurar evento de entrada para el campo de cantidad
    if (cantidadCompra) {
        cantidadCompra.addEventListener('input', function () {
            formatearCantidad(cantidadCompra);
            if (cantidadReal) {
                cantidadReal.value = cantidadCompra.value.replace(/[^0-9]/g, '');
            }
        });
    }

    // Configurar evento de clic para el botón de comprar
    if (botonComprar) {
        botonComprar.addEventListener('click', function (e) {
            e.preventDefault();

            var userId = document.getElementById('userID') ? document.getElementById('userID').value : null;
            var cantidad = cantidadReal ? cantidadReal.value : null;

            // Validar cantidad y userId
            if (!cantidad || isNaN(cantidad) || cantidad <= 0) {
                alert('Por favor, ingrese una cantidad válida para comprar.');
                return;
            }
            if (!userId) {
                alert('No se pudo obtener el ID de usuario, por favor verifica iniciar sesión o cambiar de navegador.');
                return;
            }

            // Realizar la solicitud fetch si la validación es exitosa
            fetch('/wp-json/avada/v1/crear_sesion_acciones', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    userId: userId,
                    cantidadCompra: cantidad
                })
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (sessionData) {
                    if (sessionData.id) {
                        // Redirigir al checkout de Stripe
                        var stripe = Stripe('pk_live_51M9uLoCdHJpmDkrr3ZHrVnDdA7pCZ676l1k8dKpNLSiOKG8pvKYYlCI8RaHtNqYERwpZ4qwOhdrPnLW6NgsQyX8H0019HdwAY9');
                        stripe.redirectToCheckout({sessionId: sessionData.id}).catch(function (error) {
                            console.error('Error en redirectToCheckout:', error);
                            alert('Hubo un problema al redirigir al checkout de Stripe.');
                        });
                    } else {
                        console.error('Respuesta completa:', sessionData);
                        alert('Hubo un problema al procesar la compra. Por favor, inténtalo de nuevo.');
                    }
                })
                .catch(function (error) {
                    console.error('Error:', error);
                    alert('Hubo un error al conectar con el sistema de compras. Por favor, verifica tu conexión y vuelve a intentarlo.');
                });
        });
    }
}
