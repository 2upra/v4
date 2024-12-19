function stripecomprabeat() {
    const botonesCompraDiv = document.querySelectorAll('.botonCompraDiv');

    if (botonesCompraDiv.length > 0) {
        botonesCompraDiv.forEach(botonDiv => {
            botonDiv.addEventListener('click', async e => {
                // Find the button element within the clicked div
                const boton = botonDiv.querySelector('.botonCompra');
                if (!boton) {
                    console.error('No se encontró el botón de compra dentro del div.');
                    return;
                }

                e.preventDefault();

                const postId = boton.dataset.post_id;

                // Obtener el contenedor del post basado en el id-post
                const postElement = document.querySelector(`li[id-post="${postId}"]`);
                if (!postElement) {
                    console.error(`No se encontró el elemento del post con id-post: ${postId}`);
                    return;
                }

                // Obtener la URL de la imagen
                const imageElement = postElement.querySelector('.post-thumbnail img');
                const imageUrl = imageElement ? imageElement.src : '';

                // Obtener el título del post
                const titleElement = postElement.querySelector(`.thePostContet[data-post-id="${postId}"] p`);
                const title = titleElement ? titleElement.textContent : '';

                // Crear el modal
                const modal = document.createElement('div');
                modal.classList.add('modal', 'bloque', 'modalpreCompra');

                const modalContent = document.createElement('div');

                const titleModal = document.createElement('h3');
                titleModal.textContent = title;

                const imageModal = document.createElement('img');
                imageModal.src = imageUrl;
                imageModal.style.maxWidth = '100%'; // Opcional: para asegurar que la imagen no sea demasiado grande

                const confirmText = document.createElement('p');
                confirmText.textContent = '¿Deseas comprar este audio?';

                const confirmButton = document.createElement('button');
                confirmButton.textContent = 'Confirmar Compra';
                confirmButton.addEventListener('click', async () => {
                    // Cerrar el modal
                    document.body.removeChild(modal);

                    const userId = boton.dataset.user_id;
                    const nonce = boton.dataset.nonce;
                    const precioText = botonDiv.querySelector('.precioCount')?.textContent;

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
                                precio: precio
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
                            await stripe.redirectToCheckout({ sessionId: sessionData.id });
                        } else {
                            console.error('Respuesta completa:', sessionData);
                            alert('Hubo un problema al procesar la compra. Por favor, inténtalo de nuevo.');
                        }
                    } catch (error) {
                        console.error('Error durante la petición:', error);
                        alert('Hubo un error al conectar con el sistema de compras. Por favor, verifica tu conexión y vuelve a intentarlo.');
                    }
                });

                const cancelButton = document.createElement('button');
                cancelButton.textContent = 'Cancelar';
                cancelButton.addEventListener('click', () => {
                    // Cerrar el modal
                    document.body.removeChild(modal);
                });

                modalContent.appendChild(titleModal);
                modalContent.appendChild(imageModal);
                modalContent.appendChild(confirmText);
                modalContent.appendChild(confirmButton);
                modalContent.appendChild(cancelButton);
                modal.appendChild(modalContent);

                // Agregar el modal al body
                document.body.appendChild(modal);
            });
        });
    }

    // Also attach the event listener to the button for direct clicks
    const botonesComprar = document.querySelectorAll('.botonCompra');
    if (botonesComprar.length > 0) {
        botonesComprar.forEach(boton => {
            boton.addEventListener('click', async e => {
                e.preventDefault();
                // Find the closest botonCompraDiv
                const botonDiv = boton.closest('.botonCompraDiv');
                if (botonDiv) {
                    // Simulate a click on the parent div
                    botonDiv.click();
                }
            });
        });
    }
}