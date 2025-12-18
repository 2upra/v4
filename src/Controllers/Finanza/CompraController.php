<?php

namespace Kamples\Controllers\Finanza;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;

/**
 * Controlador de compra de beats/samples.
 * 
 * Maneja la compra de contenido y su registro para compradores y vendedores.
 *
 * @since 1.0.0
 */
class CompraController
{
    private \Logger $logger;

    public function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Registra los endpoints REST de compras.
     */
    public function registrarEndpoints(): void
    {
        register_rest_route('kamples/v1', '/stripe/compra/crear-sesion', [
            'methods' => 'POST',
            'callback' => [$this, 'crearSesion'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('kamples/v1', '/stripe/compra/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'webhook'],
            'permission_callback' => '__return_true',
        ]);

        /* Endpoints legacy */
        register_rest_route('stripe/v1', '/crear_sesion_compra', [
            'methods' => 'POST',
            'callback' => [$this, 'crearSesion'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('stripe/v1', '/stripe_webhook_compra', [
            'methods' => 'POST',
            'callback' => [$this, 'webhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Crea una sesión de Stripe para compra de beat/sample.
     */
    public function crearSesion(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            if (!isset($_ENV['STRIPEKEY'])) {
                return $this->errorResponse('La clave de Stripe no está configurada', 500);
            }

            Stripe::setApiKey($_ENV['STRIPEKEY']);
            $data = $request->get_json_params();

            $userId = sanitize_text_field($data['userId'] ?? '');
            $postId = sanitize_text_field($data['postId'] ?? '');
            $precio = floatval($data['precio'] ?? 0);

            if (!$userId || !$postId || $precio <= 0) {
                return $this->errorResponse('Parámetros inválidos', 400);
            }

            $lineItems = [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => 'Compra de beat&sample'],
                    'unit_amount' => intval($precio * 100),
                ],
                'quantity' => 1,
            ]];

            $metadata = [
                'transaction_type' => 'comprabeat',
                'user_id' => $userId,
                'post_id' => $postId,
                'monto' => $precio,
            ];

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'metadata' => $metadata,
                'mode' => 'payment',
                'success_url' => home_url(''),
                'cancel_url' => home_url(''),
            ]);

            return new \WP_REST_Response(['id' => $session->id], 200);
        } catch (\Exception $e) {
            $this->logger->error('stripe', 'Error al crear sesión de compra: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Webhook para procesar compra de beat/sample.
     */
    public function webhook(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            if (!isset($_ENV['HOOKCOMPRA'])) {
                $this->logger->error('stripe', 'Clave de webhook HOOKCOMPRA no configurada');
                return $this->errorResponse('Configuración de webhook inválida', 500);
            }

            $payload = @file_get_contents('php://input');
            $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

            $event = Webhook::constructEvent($payload, $sigHeader, $_ENV['HOOKCOMPRA']);

            if ($event->type === 'checkout.session.completed') {
                $session = $event->data->object;
                $metadata = $session->metadata;

                if ($metadata->transaction_type === 'comprabeat') {
                    $this->procesarCompra($session, $metadata);
                }
            }

            return new \WP_REST_Response('Webhook recibido correctamente', 200);
        } catch (\Exception $e) {
            $this->logger->error('stripe', 'Error en webhook de compra: ' . $e->getMessage());
            return $this->errorResponse('Webhook fallido', 400);
        }
    }

    /**
     * Procesa una compra exitosa registrando los datos en comprador y vendedor.
     */
    private function procesarCompra(object $session, object $metadata): void
    {
        $userId = intval($metadata->user_id ?? 0);
        $postId = intval($metadata->post_id ?? 0);
        $monto = floatval($metadata->monto ?? 0);
        $sessionId = $session->id;

        if (!$userId || !$postId || $monto <= 0) {
            $this->logger->error('stripe', 'Metadatos inválidos en compra');
            return;
        }

        $fechaCompra = date('Y-m-d H:i:s');

        $compraData = [
            'fecha' => $fechaCompra,
            'monto' => $monto,
            'session_id' => $sessionId,
            'post_id' => $postId
        ];

        /* Registrar compra para el comprador */
        $comprasUsuario = get_user_meta($userId, 'compraBeat', true);
        $comprasUsuario = is_array($comprasUsuario) ? $comprasUsuario : [];
        $comprasUsuario[] = $compraData;
        update_user_meta($userId, 'compraBeat', $comprasUsuario);

        /* Registrar venta para el autor */
        $postAuthorId = get_post_field('post_author', $postId);
        if ($postAuthorId) {
            $ventaData = [
                'fecha' => $fechaCompra,
                'monto' => $monto,
                'session_id' => $sessionId,
                'comprador_id' => $userId,
            ];

            $ventasAutor = get_user_meta($postAuthorId, 'ventaBeat', true);
            $ventasAutor = is_array($ventasAutor) ? $ventasAutor : [];
            $ventasAutor[] = $ventaData;
            update_user_meta($postAuthorId, 'ventaBeat', $ventasAutor);

            $this->logger->info('stripe', "Compra registrada: usuario $userId, post $postId, autor $postAuthorId");
        }
    }

    private function errorResponse(string $mensaje, int $status): \WP_REST_Response
    {
        $this->logger->error('stripe', $mensaje);
        return new \WP_REST_Response(['error' => $mensaje], $status);
    }
}
