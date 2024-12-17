function stripecomprabeat() {
    var botonesComprar = document.querySelectorAll('.botonCompra'); // Selecciona los botones con la clase 'botonCompra'

    if (botonesComprar.length > 0) {
        // Verifica si hay botones con la clase
        botonesComprar.forEach(function (boton) {
            // Itera sobre cada botón
            boton.addEventListener('click', function (e) {
                e.preventDefault();

                // Obtiene los valores de los atributos data-* del botón
                const postId = this.dataset.post_id;
                const userId = this.dataset.user_id;
                const nonce = this.dataset.nonce;
                const precio = this.parentElement.querySelector('.precioCount').textContent;

                console.log('postId:', postId);
                console.log('userId:', userId);
                console.log('nonce:', nonce);
                console.log('precio:', precio);

                // Realizar la solicitud fetch si la validación es exitosa
                fetch('/wp-json/stripe/v1/crear_sesion_compra', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        postId: postId, // Envia el ID del post
                        userId: userId, // Envia el ID del usuario
                        nonce: nonce, // Envia el nonce
                        cantidadCompra: precio // Envía el precio como cantidad
                    })
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (sessionData) {
                        if (sessionData.id) {
                            // Redirigir al checkout de Stripe
                            var stripe = Stripe('pk_test_51M9uLoCdHJpmDkrrDSkwUwvKMDR9safvjDMBgICSGEbQ6NYx3QEGzG0VUpi7rOhB0crc45l9xkxI6BtgI9EUlcJ700Md8GZRwz');
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
        });
    }
}
