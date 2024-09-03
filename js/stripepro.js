function stripepro() {
    var stripe = Stripe('pk_live_51M9uLoCdHJpmDkrr3ZHrVnDdA7pCZ676l1k8dKpNLSiOKG8pvKYYlCI8RaHtNqYERwpZ4qwOhdrPnLW6NgsQyX8H0019HdwAY9');

    var botonPro = document.querySelectorAll('.MQKUSE');
    botonPro.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            var userId = document.getElementById('user_id').value;

            fetch('/wp-json/avada/v1/crear_sesion_pro', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId }) 
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(sessionData) {
                if (sessionData.id) {
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