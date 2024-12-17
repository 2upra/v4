function stripecomprabeat() {
    const botonesComprar = document.querySelectorAll('.botonCompra');

    if (botonesComprar.length > 0) {
        botonesComprar.forEach(boton => {
            boton.addEventListener('click', async e => {
                e.preventDefault();

                const postId = boton.dataset.post_id;
                const userId = boton.dataset.user_id;
                const nonce = boton.dataset.nonce;
                const precioText = boton.parentElement.querySelector('.precioCount')?.textContent;

                // Convertir precioText a número. Usar 0 si no se puede convertir.
                const precio = Number(precioText) || 0;

                console.log('postId:', postId);
                console.log('userId:', userId);
                console.log('nonce:', nonce);
                console.log('precio:', precio);

                try {
                    const response = await fetch('/wp-json/stripe/v1/crear_sesion_compra', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            postId: postId,
                            userId: userId,
                            nonce: nonce,
                            precio: precio //  <- CAMBIO: Usar 'precio'
                        })
                    });
                    if (!response.ok) {
                        // Check for HTTP error status
                        const errorText = await response.text();
                        console.error('HTTP error:', response.status, errorText);
                        alert(`Hubo un problema con la compra. Error ${response.status}`);
                        return;
                    }

                    const sessionData = await response.json();

                    if (sessionData && sessionData.id) {
                        const stripe = Stripe('pk_live_51M9uLoCdHJpmDkrr3ZHrVnDdA7pCZ676l1k8dKpNLSiOKG8pvKYYlCI8RaHtNqYERwpZ4qwOhdrPnLW6NgsQyX8H0019HdwAY9');
                        await stripe.redirectToCheckout({sessionId: sessionData.id});
                    } else {
                        console.error('Respuesta completa:', sessionData);
                        alert('Hubo un problema al procesar la compra. Por favor, inténtalo de nuevo.');
                    }
                } catch (error) {
                    console.error('Error durante la petición:', error);
                    alert('Hubo un error al conectar con el sistema de compras. Por favor, verifica tu conexión y vuelve a intentarlo.');
                }
            });
        });
    }
}
