<?php

namespace Kamples\Controllers\Finanza;

use Kamples\Services\Finanza\FinanzaService;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;

/**
 * Controlador de acciones/donaciones.
 * 
 * Maneja la compra de acciones y su registro.
 *
 * @since 1.0.0
 */
class AccionesController
{
    private FinanzaService $finanzaService;
    private \Logger $logger;

    public function __construct()
    {
        $this->finanzaService = FinanzaService::obtenerInstancia();
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Registra los endpoints REST de acciones.
     */
    public function registrarEndpoints(): void
    {
        register_rest_route('kamples/v1', '/stripe/acciones/crear-sesion', [
            'methods' => 'POST',
            'callback' => [$this, 'crearSesion'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('kamples/v1', '/stripe/acciones/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'webhook'],
            'permission_callback' => '__return_true',
        ]);

        /* Endpoints legacy */
        register_rest_route('avada/v1', '/crear_sesion_acciones', [
            'methods' => 'POST',
            'callback' => [$this, 'crearSesion'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('avada/v1', '/stripe_webhook_acciones', [
            'methods' => 'POST',
            'callback' => [$this, 'webhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Registra los cron jobs para acciones.
     */
    public function registrarCronJobs(): void
    {
        add_filter('cron_schedules', function ($schedules) {
            $schedules['monthly'] = [
                'interval' => 30 * 24 * 60 * 60,
                'display' => __('Una vez al mes'),
            ];
            return $schedules;
        });

        add_action('wp', function () {
            if (!wp_next_scheduled('kamples_evento_mensual_acciones')) {
                wp_schedule_event(time(), 'monthly', 'kamples_evento_mensual_acciones');
            }

            if (!wp_next_scheduled('kamples_evento_historial_acciones')) {
                wp_schedule_event(time(), 'hourly', 'kamples_evento_historial_acciones');
            }
        });

        add_action('kamples_evento_mensual_acciones', function () {
            $this->finanzaService->sumarAccionesMensual(true);
        });

        add_action('kamples_evento_historial_acciones', function () {
            $this->finanzaService->registrarHistorialAcciones();
        });
    }

    /**
     * Crea una sesión de Stripe para compra de acciones.
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
            $cantidadCompra = floatval($data['cantidadCompra'] ?? 0);

            if (!$userId || $cantidadCompra <= 0) {
                return $this->errorResponse('Parámetros inválidos proporcionados', 400);
            }

            $lineItems = [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => 'Compra de Acciones'],
                    'unit_amount' => intval($cantidadCompra * 100),
                ],
                'quantity' => 1,
            ]];

            $metadata = [
                'transaction_type' => 'compra_acciones',
                'user_id' => $userId,
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
            $this->logger->error('stripe', 'Error al crear sesión de acciones: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Webhook para procesar compra de acciones.
     */
    public function webhook(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            if (!isset($_ENV['HOOKACCIONES'])) {
                $this->logger->error('stripe', 'Clave de webhook HOOKACCIONES no configurada');
                return $this->errorResponse('Configuración de webhook inválida', 500);
            }

            $payload = @file_get_contents('php://input');
            $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

            $event = Webhook::constructEvent($payload, $sigHeader, $_ENV['HOOKACCIONES']);

            if ($event->type === 'checkout.session.completed') {
                $session = $event->data->object;
                $metadata = $session->metadata;

                if ($metadata->transaction_type === 'compra_acciones' || $metadata->transaction_type === 'compra') {
                    $compras = get_user_meta($metadata->user_id, 'compras_acciones', true) ?: [];
                    $compras[] = [
                        'cantidad' => $session->amount_total / 100,
                        'fecha' => current_time('mysql')
                    ];
                    update_user_meta($metadata->user_id, 'compras_acciones', $compras);

                    $this->logger->info('stripe', "Compra de acciones registrada para usuario {$metadata->user_id}");
                }
            }

            return new \WP_REST_Response('Webhook recibido correctamente', 200);
        } catch (\Exception $e) {
            $this->logger->error('stripe', 'Error en webhook de acciones: ' . $e->getMessage());
            return $this->errorResponse('Webhook fallido', 400);
        }
    }

    private function errorResponse(string $mensaje, int $status): \WP_REST_Response
    {
        $this->logger->error('stripe', $mensaje);
        return new \WP_REST_Response(['error' => $mensaje], $status);
    }
}
