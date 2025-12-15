<?php

namespace Kamples\Controllers\Finanza;

/**
 * Controlador principal de operaciones financieras.
 * 
 * Fachada que orquesta los sub-controladores de Stripe:
 * - AccionesController: donaciones y acciones
 * - CompraController: compra de beats/samples
 * - SuscripcionController: suscripciones PRO
 *
 * @since 1.0.0
 * @see AccionesController
 * @see CompraController
 * @see SuscripcionController
 */
class FinanzaController
{
    private AccionesController $accionesController;
    private CompraController $compraController;
    private SuscripcionController $suscripcionController;

    public function __construct()
    {
        $this->accionesController = new AccionesController();
        $this->compraController = new CompraController();
        $this->suscripcionController = new SuscripcionController();

        $this->registrarEndpoints();
        $this->registrarCronJobs();
    }

    /**
     * Registra todos los endpoints REST de finanzas.
     */
    private function registrarEndpoints(): void
    {
        add_action('rest_api_init', function () {
            $this->accionesController->registrarEndpoints();
            $this->compraController->registrarEndpoints();
            $this->suscripcionController->registrarEndpoints();
        });
    }

    /**
     * Registra los cron jobs de finanzas.
     */
    private function registrarCronJobs(): void
    {
        $this->accionesController->registrarCronJobs();
    }
}

new FinanzaController();
