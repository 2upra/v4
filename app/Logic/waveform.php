<?php
// Refactor(Org): Function save_waveform_image() and its AJAX hooks moved to app/Services/AudioProcessingService.php

// Refactor(Org): Moved function reset_waveform_metas() to app/Services/AudioProcessingService.php

// Registrar las acciones AJAX.
// add_action('wp_ajax_save_waveform_image', 'save_waveform_image'); // Moved
// add_action('wp_ajax_nopriv_save_waveform_image', 'save_waveform_image'); // Moved
