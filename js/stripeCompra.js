function stripecomprabeat() {
    const botonesComprar = document.querySelectorAll('.botonCompra');
  
    if (botonesComprar.length > 0) {
      botonesComprar.forEach((boton) => {
        boton.addEventListener('click', async (e) => { // Use async for cleaner error handling
          e.preventDefault();
  
          const postId = boton.dataset.post_id;
          const userId = boton.dataset.user_id;
          const nonce = boton.dataset.nonce;
          const precio = boton.parentElement.querySelector('.precioCount')?.textContent; // Use optional chaining to handle cases where '.precioCount' isn't present
  
          console.log('postId:', postId);
          console.log('userId:', userId);
          console.log('nonce:', nonce);
          console.log('precio:', precio);
  
          try {
            const response = await fetch('/wp-json/stripe/v1/crear_sesion_compra', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                postId: postId,
                userId: userId,
                nonce: nonce,
                cantidadCompra: precio,
              }),
            });
             if (!response.ok) { // Check for HTTP error status
                const errorText = await response.text()
                console.error('HTTP error:', response.status, errorText);
                alert(`Hubo un problema con la compra. Error ${response.status}`);
                return;
              }
  
            const sessionData = await response.json();
  
            if (sessionData && sessionData.id) {
              const stripe = Stripe('pk_test_51M9uLoCdHJpmDkrrDSkwUwvKMDR9safvjDMBgICSGEbQ6NYx3QEGzG0VUpi7rOhB0crc45l9xkxI6BtgI9EUlcJ700Md8GZRwz');
              await stripe.redirectToCheckout({ sessionId: sessionData.id });
            } else {
              console.error('Respuesta completa:', sessionData);
              alert('Hubo un problema al procesar la compra. Por favor, inténtalo de nuevo.');
            }
          } catch (error) {
            console.error('Error durante la petición:', error);
            alert(
              'Hubo un error al conectar con el sistema de compras. Por favor, verifica tu conexión y vuelve a intentarlo.'
            );
          }
        });
      });
    }
  }
  
