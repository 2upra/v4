function stripe_suscripcion() {
    var stripe = Stripe('pk_live_51M9uLoCdHJpmDkrr3ZHrVnDdA7pCZ676l1k8dKpNLSiOKG8pvKYYlCI8RaHtNqYERwpZ4qwOhdrPnLW6NgsQyX8H0019HdwAY9');

    const subscribeButtons = document.querySelectorAll('.ITKSUG');
    subscribeButtons.forEach(function(subscribeButton) {
        subscribeButton.addEventListener('click', function(e) {
            e.preventDefault(); // Previene el comportamiento por defecto del botón en caso de que se trate de un formulario


            fetch('https://2upra.com/wp-json/avada/v1/suscripcion_stripe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    offeringUserId: offeringUserId,
                    offeringUserLogin: offeringUserLogin,
                    offeringUserEmail: offeringUserEmail,
                    subscriberUserId: subscriberUserId,
                    subscriberUserLogin: subscriberUserLogin,
                    subscriberUserEmail: subscriberUserEmail,
                    priceId: priceId,
                    successUrl: redirectUrl + '?session_id={CHECKOUT_SESSION_ID}',
                    cancelUrl: redirectUrl,
                }),
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data && data.sessionId) {
                    window.location.href = data.checkoutUrl;
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
}