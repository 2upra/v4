<?php

/**
 * Wrappers deprecados para modales de onboarding
 * 
 * @deprecated Usar Kamples\Views\Components\OnboardingComponents en su lugar.
 * @package app\deprecated
 */

use Kamples\Views\Components\OnboardingComponents;

/**
 * @deprecated Usar OnboardingComponents::renderModalTipoUsuario()
 */
if (!function_exists('modalTipoUsuario')) {
    function modalTipoUsuario(): string
    {
        return OnboardingComponents::renderModalTipoUsuario();
    }
}

/**
 * @deprecated Usar OnboardingComponents::renderModalGeneros()
 */
if (!function_exists('modalGeneros')) {
    function modalGeneros(): string
    {
        return OnboardingComponents::renderModalGeneros();
    }
}

/* 
 * Las funciones guardarTipoUsuario y guardarGenerosUsuario
 * ahora son manejadas por OnboardingController
 */
