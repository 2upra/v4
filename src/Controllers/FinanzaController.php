<?php

namespace Kamples\Controllers;

use Kamples\Services\FinanzaService;

/**
 * Controlador de operaciones financieras.
 * 
 * Maneja los endpoints REST para Stripe y AJAX para compras y suscripciones.
 *
 * @since 1.0.0
 */
class FinanzaController
{
    private FinanzaService $finanzaService;
    private \Logger $logger;

    public function __construct()
    {
        $this->finanzaService = FinanzaService::obtenerInstancia();
        $this->logger = \Logger::obtenerInstancia();
        $this->registrarEndpoints();
        $this->registrarCronJobs();
    }

    /**
     * Registra los endpoints REST de Stripe.
     */
    private function registrarEndpoints(): void
    {
        add_action('rest_api_init', function () {
            /* 
             * Endpoints de acciones/donaciones
             */
            register_rest_route('kamples/v1', '/stripe/acciones/crear-sesion', [
                'methods' => 'POST',
                'callback' => [$this, 'crearSesionAcciones'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('kamples/v1', '/stripe/acciones/webhook', [
                'methods' => 'POST',
                'callback' => [$this, 'webhookAcciones'],
                'permission_callback' => '__return_true',
            ]);

            /* 
             * Endpoints de compra de beats
             */
            register_rest_route('kamples/v1', '/stripe/compra/crear-sesion', [
                'methods' => 'POST',
                'callback' => [$this, 'crearSesionCompra'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('kamples/v1', '/stripe/compra/webhook', [
                'methods' => 'POST',
                'callback' => [$this, 'webhookCompra'],
                'permission_callback' => '__return_true',
            ]);

            /* 
             * Endpoints de suscripción PRO
             */
            register_rest_route('kamples/v1', '/stripe/pro/crear-sesion', [
                'methods' => 'POST',
                'callback' => [$this, 'crearSesionPro'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('kamples/v1', '/stripe/pro/webhook', [
                'methods' => 'POST',
                'callback' => [$this, 'webhookPro'],
                'permission_callback' => '__return_true',
            ]);

            /* 
             * Endpoints legacy (compatibilidad)
             */
            register_rest_route('avada/v1', '/crear_sesion_acciones', [
                'methods' => 'POST',
                'callback' => [$this, 'crearSesionAcciones'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('avada/v1', '/stripe_webhook_acciones', [
                'methods' => 'POST',
                'callback' => [$this, 'webhookAcciones'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('stripe/v1', '/crear_sesion_compra', [
                'methods' => 'POST',
                'callback' => [$this, 'crearSesionCompra'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('stripe/v1', '/stripe_webhook_compra', [
                'methods' => 'POST',
                'callback' => [$this, 'webhookCompra'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('avada/v1', '/crear_sesion_pro', [
                'methods' => 'POST',
                'callback' => [$this, 'crearSesionPro'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('avada/v1', '/stripe_webhook_pro', [
                'methods' => 'POST',
                'callback' => [$this, 'webhookPro'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    /**
     * Registra los cron jobs para acciones mensuales.
     */
    private function registrarCronJobs(): void
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

    /* 
     * Handlers de sesiones de Stripe
     */

    /**
     * Crea una sesión de Stripe para compra de acciones.
     */
    public function crearSesionAcciones(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            if (!$this->verificarStripeKey()) {
                return $this->errorResponse('La clave de Stripe no está configurada', 500);
            }

            \Stripe\Stripe::setApiKey($_ENV['STRIPEKEY']);
            $data = $request->get_json_params();
            $userId = sanitize_text_field($data['userId'] ?? '');
            $cantidadCompra = floatval($data['cantidadCompra'] ?? 0);

            if (!$userId || $cantidadCompra <= 0) {
                return $this->errorResponse('Parámetros inválidos proporcionados', 400);
            }

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => ['name' => 'Compra de Acciones'],
                        'unit_amount' => intval($cantidadCompra * 100),
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'transaction_type' => 'compra_acciones',
                    'user_id' => $userId,
                ],
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
    public function webhookAcciones(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $event = $this->construirEvento($request, 'HOOKACCIONES');
            if ($event instanceof \WP_REST_Response) {
                return $event;
            }

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

    /**
     * Crea una sesión de Stripe para compra de beat/sample.
     */
    public function crearSesionCompra(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            if (!$this->verificarStripeKey()) {
                return $this->errorResponse('La clave de Stripe no está configurada', 500);
            }

            \Stripe\Stripe::setApiKey($_ENV['STRIPEKEY']);
            $data = $request->get_json_params();

            $userId = sanitize_text_field($data['userId'] ?? '');
            $postId = sanitize_text_field($data['postId'] ?? '');
            $precio = floatval($data['precio'] ?? 0);

            if (!$userId || !$postId || $precio <= 0) {
                return $this->errorResponse('Parámetros inválidos', 400);
            }

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => ['name' => 'Compra de beat&sample'],
                        'unit_amount' => intval($precio * 100),
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'transaction_type' => 'comprabeat',
                    'user_id' => $userId,
                    'post_id' => $postId,
                    'monto' => $precio,
                ],
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
    public function webhookCompra(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $event = $this->construirEvento($request, 'HOOKCOMPRA');
            if ($event instanceof \WP_REST_Response) {
                return $event;
            }

            if ($event->type === 'checkout.session.completed') {
                $session = $event->data->object;
                $metadata = $session->metadata;

                if ($metadata->transaction_type === 'comprabeat') {
                    $userId = intval($metadata->user_id ?? 0);
                    $postId = intval($metadata->post_id ?? 0);
                    $monto = floatval($metadata->monto ?? 0);
                    $sessionId = $session->id;

                    if (!$userId || !$postId || $monto <= 0) {
                        return $this->errorResponse('Metadatos inválidos', 400);
                    }

                    $fechaCompra = date('Y-m-d H:i:s');

                    $compraData = [
                        'fecha' => $fechaCompra,
                        'monto' => $monto,
                        'session_id' => $sessionId,
                        'post_id' => $postId
                    ];

                    $comprasUsuario = get_user_meta($userId, 'compraBeat', true);
                    $comprasUsuario = is_array($comprasUsuario) ? $comprasUsuario : [];
                    $comprasUsuario[] = $compraData;
                    update_user_meta($userId, 'compraBeat', $comprasUsuario);

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
            }

            return new \WP_REST_Response('Webhook recibido correctamente', 200);

        } catch (\Exception $e) {
            $this->logger->error('stripe', 'Error en webhook de compra: ' . $e->getMessage());
            return $this->errorResponse('Webhook fallido', 400);
        }
    }

    /**
     * Crea una sesión de Stripe para suscripción PRO.
     */
    public function crearSesionPro(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            if (!$this->verificarStripeKey()) {
                return $this->errorResponse('La clave de Stripe no está configurada', 500);
            }

            \Stripe\Stripe::setApiKey($_ENV['STRIPEKEY']);
            $body = $request->get_json_params();
            $userId = isset($body['user_id']) ? intval($body['user_id']) : 0;

            if (!$userId) {
                return $this->errorResponse('Usuario no autenticado o ID no proporcionado', 401);
            }

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => 'price_1PBgGfCdHJpmDkrrHorFUNaV',
                    'quantity' => 1
                ]],
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
    public function webhookPro(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            if (!$this->verificarStripeKey()) {
                return $this->errorResponse('La clave de Stripe no está configurada', 500);
            }

            \Stripe\Stripe::setApiKey($_ENV['STRIPEKEY']);

            if (!isset($_ENV['HOOKPRO'])) {
                return $this->errorResponse('La clave de webhook no está configurada', 500);
            }

            $event = \Stripe\Webhook::constructEvent(
                $request->get_body(),
                $request->get_header('stripe-signature'),
                $_ENV['HOOKPRO']
            );

            if ($event['type'] === 'checkout.session.completed') {
                $session = $event['data']['object'];

                if ($session['mode'] === 'subscription') {
                    $subscription = \Stripe\Subscription::retrieve($session['subscription']);

                    foreach ($subscription->items->data as $item) {
                        if ($item->price->id === 'price_1PBgGfCdHJpmDkrrHorFUNaV') {
                            $userId = $session['client_reference_id'];
                            if (!empty($userId)) {
                                update_user_meta($userId, 'user_pro', true);
                                $this->logger->info('stripe', "Usuario $userId actualizado a PRO");
                            }
                            break;
                        }
                    }
                }
            }

            return new \WP_REST_Response(['status' => 'success'], 200);

        } catch (\Exception $e) {
            $this->logger->error('stripe', 'Error en webhook PRO: ' . $e->getMessage());

            $statusCode = $e instanceof \Stripe\Exception\SignatureVerificationException ? 400 : 500;
            return $this->errorResponse('Error en webhook', $statusCode);
        }
    }

    /* 
     * Métodos auxiliares
     */

    private function verificarStripeKey(): bool
    {
        return isset($_ENV['STRIPEKEY']);
    }

    private function construirEvento(\WP_REST_Request $request, string $hookEnvKey)
    {
        if (!isset($_ENV[$hookEnvKey])) {
            $this->logger->error('stripe', "Clave de webhook $hookEnvKey no configurada");
            return $this->errorResponse('Configuración de webhook inválida', 500);
        }

        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        return \Stripe\Webhook::constructEvent($payload, $sigHeader, $_ENV[$hookEnvKey]);
    }

    private function errorResponse(string $mensaje, int $status): \WP_REST_Response
    {
        $this->logger->error('stripe', $mensaje);
        return new \WP_REST_Response(['error' => $mensaje], $status);
    }
}

new FinanzaController();
