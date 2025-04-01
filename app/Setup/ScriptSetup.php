<?php

function scriptsOrdenados()
{
    $global_version = '0.2.386';
    $dev_mode = defined('LOCAL') && LOCAL;
    //$error_log = [];

    $scripts_only_for_logged_in_users = [
        'galleV2',
        'likes',
        'descargas',
        'RS',
        'progreso',
        'configPerfil',
        'stripeAccion',
        'likes',
        'autorows',
        'stripepro',
        'subida',
        'hashs',
        'ajax-submit',
        'formsscript',
        'notificaciones',
        'colec',
        'contarVistaPost',
        'seguir',
        'inversores',
        'genericAjax',
        'comentarios',
        'stripeCompra',
        'task',
        'notas',
    ];

    $script_handles = [
        'gloria'            => '1.0.1',
        'ajaxPage'          => '5.0.11',
        'autorows'          => '1.0.1',
        'busqueda'          => '1.0.1',
        'stripeAccion'      => '1.0.6',
        'reproductor'       => '2.1.2',
        'stripepro'         => '1.0.8',
        'progreso'          => '1.0.23',
        'modal'             => '1.0.22',
        'alert'             => '1.0.4',
        'submenu'           => '1.2.15',
        'descargas'         => '2.0.1',
        'pestanas'          => '1.1.10',
        'tagify'            => '2.0.1',
        'configPerfil'      => '1.0.14',
        'diferido-post'     => '4.0.0',
        'registro'          => '1.0.12',
        'colab'             => '1.0.2',
        'grained'           => '1.0.3',
        'RS'                => '1.0.1',
        'tagsPosts'         => '1.0.1',
        'hashs'             => '1.0.1',
        'background'        => '1.0.1',
        'ajax-submit'       => '2.1.38',
        'formscript'        => '1.1.11',
        'genericAjax'       => '2.1.13',
        'wavejs'            => ['2.0.20', ['jquery', 'wavesurfer']],
        'inversores'        => '1.0.4',
        'likes'             => '2.0.1',
        'seguir'            => '2.0.1',
        'galleV2'           => '2.0.1',
        'cambiarVistas'     => '1.0.1',
        'contarVistaPost'   => '1.0.1',
        'notificaciones'    => '1.0.1',
        'comentarios'       => '1.0.1',
        'colec'             => '1.0.1',
        'stripeCompra'      => '1.0.1',
        'tooltips'          => '1.0.1',
        'masonary'          => '1.0.1',
        'taskEnter'         => '1.0.1',
        'taskmove'          => '1.0.1',
        'taskSesiones'      => '1.0.1',
        'task'              => '1.0.1',
        'icons'             => '1.0.1',
        'notas'             => '1.0.1',
        'filtros'           => '1.0.1',
    ];

    wp_enqueue_script('wavesurfer', 'https://unpkg.com/wavesurfer.js', [], '7.8.11', true);
    // Registro de la configuracion inicial
    //$error_log[] = "Modo de desarrollo activado: " . ($dev_mode ? 'Si' : 'No');
    //$error_log[] = "Version global de scripts: " . $global_version;

    foreach ($script_handles as $handle => $data) {
        $version = $global_version;
        $deps = is_array($data) && isset($data[1]) ? $data[1] : [];

        if (!is_user_logged_in() && in_array($handle, $scripts_only_for_logged_in_users)) {
            //$error_log[] = "Usuario no logueado, omitiendo script: " . $handle;
            continue;
        }

        if ($dev_mode) {
            $version .= '.' . mt_rand();
        }

        $script_path = get_template_directory() . "/js/{$handle}.js";
        $script_url = get_template_directory_uri() . "/js/{$handle}.js";

        // Registro de cada script antes de verificar su existencia
        //$error_log[] = "Intentando cargar script: " . $handle . " desde " . $script_path;

        if (!file_exists($script_path)) {
            //$error_log[] = "Error: El archivo " . $handle . ".js no existe en la ruta: " . $script_path;
            continue;
        }

        wp_enqueue_script($handle, $script_url, $deps, $version, true);
        //$error_log[] = "Script " . $handle . " encolado correctamente con version: " . $version;
    }


    // Scripts adicionales y localizaciones
    if (is_user_logged_in()) {
        $nonce = wp_create_nonce('wp_rest');
        wp_localize_script('galleV2', 'galleV2', [
            'nonce'     => $nonce,
            'apiUrl'    => esc_url_raw(rest_url('galle/v2/guardarMensaje/')),
            'emisor'    => get_current_user_id()
        ]);
        //$error_log[] = "Script galleV2 localizado con nonce y apiUrl para usuario logueado.";

        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
        wp_enqueue_script('chartjs-adapter-date-fns', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns', ['chart-js'], null, true);
        //$error_log[] = "Scripts chart-js y chartjs-adapter-date-fns encolados para usuario logueado.";

        // Localizar el nonce para task.js
        wp_localize_script('task', 'task_vars', array(
            'borrar_tarea_nonce' => wp_create_nonce('borrar_tarea_nonce')
        ));
    }

    wp_localize_script('ajaxPage', 'ajaxPage', ['logeado' => is_user_logged_in()]);
    //$error_log[] = "Script ajaxPage localizado.";

    // Localizar datos para genericAjax.js, incluyendo el nonce para guardarReporte
    wp_localize_script('genericAjax', 'genericAjaxData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'guardarReporteNonce' => wp_create_nonce('guardar_reporte_nonce')
    ]);

    wp_add_inline_script('genericAjax', 'const wpAdminUrl = "' . admin_url() . '";', 'before');
    //$error_log[] = "Script en linea para genericAjax anadido con wpAdminUrl.";

    // Localizacion de scripts adicionales
    $ajax_url = admin_url('admin-ajax.php');
    $script_localizations = [
        'subida'                => ['my_ajax_object', ['ajax_url' => $ajax_url]],
        'social-post-script'    => ['my_ajax_object', ['ajax_url' => $ajax_url, 'social_post_nonce' => wp_create_nonce('social-post-nonce')]],
        'my-ajax-script'        => ['ajax_params', ['ajax_url' => $ajax_url]],
        'reproductor'           => ['audioSettings', ['nonce' => wp_create_nonce('wp_rest')]],
        'wavejs'                => ['audioSettings', ['nonce' => wp_create_nonce('wp_rest'), 'encryptionKey' => $_ENV['AUDIOCLAVE'], 'key' => $_ENV['AUDIOCLAVE'], 'restUrl' => rest_url()]],
        'form-script'           => ['wpData', ['isAdmin' => current_user_can('administrator')]],
    ];

    foreach ($script_localizations as $handle => $data) {
        wp_localize_script($handle, $data[0], $data[1]);
        //$error_log[] = "Script " . $handle . " localizado con exito.";
    }

    // Registro de errores
    if (!empty($error_log)) {
        $log_message = "Detalles de scriptsOrdenados:\n" . implode("\n", $error_log) . "\n";
        //error_log($log_message);
    }
}
add_action('wp_enqueue_scripts', 'scriptsOrdenados');
