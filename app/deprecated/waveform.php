<?php

/**
 * Wrappers deprecados para funciones de waveform.
 * 
 * @deprecated Usar Kamples\Services\WaveformService en su lugar.
 * @see \Kamples\Services\WaveformService
 */

use Kamples\Services\WaveformService;
use Kamples\Controllers\WaveformController;

/* Registrar hooks del controlador */

$waveformController = new WaveformController();
$waveformController->registrarHooks();

/**
 * @deprecated Usar WaveformController::guardarWaveform() vía AJAX.
 */
function save_waveform_image()
{
    $waveformController = new WaveformController();
    $waveformController->guardarWaveform();
}

/**
 * @deprecated Usar WaveformService::resetearTodasLasWaveforms()
 */
function reset_waveform_metas()
{
    $waveformService = new WaveformService();
    return $waveformService->resetearTodasLasWaveforms();
}
