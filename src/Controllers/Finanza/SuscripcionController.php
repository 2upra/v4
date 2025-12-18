<?php

namespace Kamples\Controllers\Finanza;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Stripe\Subscription;

/**
 * Controlador de suscripciones PRO.
 * 
 * Maneja la suscripción a planes premium.
 *
 * @since 1.0.0
 */
class SuscripcionController
{
    private const PRICE_ID_PRO = 'price_1PBgGfCdHJpmDkrrHorFUNaV';

    private \Logger $logger;

    public function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Registra los endpoints REST de suscripción.
     */
    public function registrarEndpoints(): void
    {
        register_rest_route('kamples/v1', '/stripe/pro/crear-sesion', [
            'methods' => 'POST',
            'callback' => [$this, 'crearSesion'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('kamples/v1', '/stripe/pro/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'webhook'],
            'permission_callback' => '__return_true',
        ]);

        /* Endpoints legacy */
        register_rest_route('avada/v1', '/crear_sesion_pro', [
            'methods' => 'POST',
            'callback' => [$this, 'crearSesion'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('avada/v1', '/stripe_webhook_pro', [
            'methods' => 'POST',
            'callback' => [$this, 'webhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Crea una sesión de Stripe para suscripción PRO.
     */
    public function crearSesion(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            if (!isset($_ENV['STRIPEKEY'])) {
                return $this->errorResponse('La clave de Stripe no está configurada', 500);
            }

            Stripe::setApiKey($_ENV['STRIPEKEY']);
            $body = $request->get_json_params();
            $userId = isset($body['user_id']) ? intval($body['user_id']) : 0;

            if (!$userId) {
                return $this->errorResponse('Usuario no autenticado o ID no proporcionado', 401);
            }

            $lineItems = [[
                'price' => self::PRICE_ID_PRO,
                'quantity' => 1
            ]];

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'subscription',
                'success_url' => home_url('/'),
                'cancel_url' => home_url('/'),
                'client_reference_id' => $userId,
            ]);

            return new \WP_REST_Response(['id' => $session->id], 200);
        } catch (\Exception $e) {
            $this->logger->error('stripe', 'Error al crear sesión PRO: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Webhook para procesar suscripción PRO.
     */
    public function webhook(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            if (!isset($_ENV['STRIPEKEY'])) {
                return $this->errorResponse('La clave de Stripe no está configurada', 500);
            }

            Stripe::setApiKey($_ENV['STRIPEKEY']);

            if (!isset($_ENV['HOOKPRO'])) {
                return $this->errorResponse('La clave de webhook no está configurada', 500);
            }

            $event = Webhook::constructEvent(
                $request->get_body(),
                $request->get_header('stripe-signature'),
                $_ENV['HOOKPRO']
            );

            if ($event['type'] === 'checkout.session.completed') {
                $session = $event['data']['object'];

                if ($session['mode'] === 'subscription') {
                    $this->procesarSuscripcion($session);
                }
            }

            return new \WP_REST_Response(['status' => 'success'], 200);
        } catch (\Exception $e) {
            $this->logger->error('stripe', 'Error en webhook PRO: ' . $e->getMessage());

            $statusCode = $e instanceof \Stripe\Exception\SignatureVerificationException ? 400 : 500;
            return $this->errorResponse('Error en webhook', $statusCode);
        }
    }

    /**
     * Procesa una suscripción exitosa activando el usuario PRO.
     */
    private function procesarSuscripcion(array $session): void
    {
        $subscription = Subscription::retrieve($session['subscription']);

        foreach ($subscription->items->data as $item) {
            if ($item->price->id === self::PRICE_ID_PRO) {
                $userId = $session['client_reference_id'];
                if (!empty($userId)) {
                    update_user_meta($userId, 'user_pro', true);
                    $this->logger->info('stripe', "Usuario $userId actualizado a PRO");
                }
                break;
            }
        }
    }

    private function errorResponse(string $mensaje, int $status): \WP_REST_Response
    {
        $this->logger->error('stripe', $mensaje);
        return new \WP_REST_Response(['error' => $mensaje], $status);
    }
}
