function stripeventa() {
    var stripe = Stripe('pk_live_51M9uLoCdHJpmDkrr3ZHrVnDdA7pCZ676l1k8dKpNLSiOKG8pvKYYlCI8RaHtNqYERwpZ4qwOhdrPnLW6NgsQyX8H0019HdwAY9');

    var checkoutButtons = document.querySelectorAll('.stripe-checkout-button');
    checkoutButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            var productId = button.getAttribute('data-product-id');
            var productPrice = button.getAttribute('data-product-price');
            
            var buyerId = button.getAttribute('data-buyer-id');
            var buyerUsername = button.getAttribute('data-buyer-username');
            
            var sellerId = button.getAttribute('data-seller-id');
            var sellerUsername = button.getAttribute('data-seller-username');

            var imageUrl = button.getAttribute('data-image-url');
            var audioUrl = button.getAttribute('data-audio-url')

            fetch('/wp-json/avada/v1/crear_sesion_checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({

                    productId: productId,
                    post_price: productPrice,
                    
                    buyerId: buyerId,
                    buyerUsername: buyerUsername,
                    
                    sellerId: sellerId, 
                    sellerUsername: sellerUsername,

                    imageUrl: imageUrl,
                    audioUrl: audioUrl,
                    
                }),
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(sessionData) {
                if(sessionData.id) {
                    stripe.redirectToCheckout({ sessionId: sessionData.id })
                    .catch(function(error) {
                        console.error('Error en redirectToCheckout:', error);
                        alert('Hubo un problema al redirigir al checkout de Stripe.');
                    });
                } else {
                    console.error('Respuesta completa:', sessionData);
                    alert('Hubo un problema al procesar el pago. Por favor, inténtalo de nuevo.');
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('Hubo un error al conectar con el sistema de pagos. Por favor, verifica tu conexión y vuelve a intentarlo.');
            });
        });
    });
}
