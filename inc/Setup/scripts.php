<?php

/**
 * Gestión de scripts y estilos del tema.
 * 
 * Este archivo centraliza el enqueue de todos los scripts JS y CSS.
 * Los scripts se detectan automáticamente de la carpeta /js/.
 * Solo se especifican manualmente los que tienen configuración especial.
 *
 * @package Theme_V4
 * @subpackage Setup
 * @since 1.0.0
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

/**
 * Clase ScriptsManager - Gestiona el enqueue de scripts y estilos.
 * 
 * Los scripts se cargan automáticamente desde /js/.
 * La configuración especial se define solo para scripts que la necesitan.
 */
class ScriptsManager
{
    /**
     * Instancia única de la clase.
     *
     * @var ScriptsManager|null
     */
    private static $instancia = null;

    /**
     * Versión global de scripts para cache busting.
     *
     * @var string
     */
    private $versionGlobal = '0.2.386';

    /**
     * Modo desarrollo activo.
     *
     * @var bool
     */
    private $modoDesarrollo;

    /**
     * Scripts que SOLO se cargan para usuarios logueados.
     * Si un script no está aquí, se carga para todos.
     *
     * @var array
     */
    private $scriptsUsuarioLogueado = [
        'galleV2',
        'likes',
        'descargas',
        'RS',
        'progreso',
        'configPerfil',
        'stripeAccion',
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

    /**
     * Configuración especial de scripts.
     * Solo se definen aquí los scripts que tienen dependencias u otras configuraciones.
     * Formato: 'handle' => ['deps' => ['dependencia1'], 'version' => '1.0.0']
     *
     * @var array
     */
    private $configuracionEspecial = [
        'wavejs' => [
            'deps' => ['jquery', 'wavesurfer'],
        ],
    ];

    /**
     * Scripts a excluir de la carga automática.
     * Útil para scripts que no deben cargarse o que se manejan de otra forma.
     *
     * @var array
     */
    private $scriptsExcluidos = [
        'config-user', // Vacío o de configuración
        'fan',         // Vacío
        'wavesDos',    // Vacío
    ];

    /**
     * Constructor privado (Singleton).
     */
    private function __construct()
    {
        $this->modoDesarrollo = defined('LOCAL') && LOCAL;
    }

    /**
     * Obtener instancia única.
     *
     * @return ScriptsManager
     */
    public static function obtenerInstancia(): ScriptsManager
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializar el sistema de scripts.
     *
     * @return void
     */
    public function inicializar(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'encolarScripts']);
        add_action('wp_enqueue_scripts', [$this, 'encolarScriptVH']);
    }

    /**
     * Encolar todos los scripts del tema.
     *
     * @return void
     */
    public function encolarScripts(): void
    {
        $this->encolarDependenciasExternas();
        $this->encolarScriptsAutomaticos();
        $this->configurarLocalizaciones();
    }

    /**
     * Encolar dependencias externas (CDN).
     *
     * @return void
     */
    private function encolarDependenciasExternas(): void
    {
        wp_enqueue_script('wavesurfer', 'https://unpkg.com/wavesurfer.js', [], '7.8.11', true);

        if (is_user_logged_in()) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
            wp_enqueue_script(
                'chartjs-adapter-date-fns',
                'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns',
                ['chart-js'],
                null,
                true
            );
        }
    }

    /**
     * Detectar y encolar automáticamente todos los scripts de /js/.
     *
     * @return void
     */
    private function encolarScriptsAutomaticos(): void
    {
        $rutaJs = get_template_directory() . '/js/';
        $urlJs = get_template_directory_uri() . '/js/';
        $usuarioLogueado = is_user_logged_in();

        // Obtener todos los archivos .js en la carpeta
        $archivosJs = glob($rutaJs . '*.js');

        if (empty($archivosJs)) {
            return;
        }

        foreach ($archivosJs as $archivoRuta) {
            $nombreArchivo = basename($archivoRuta, '.js');

            // Verificar exclusiones
            if (in_array($nombreArchivo, $this->scriptsExcluidos)) {
                continue;
            }

            // Verificar si requiere usuario logueado
            if (!$usuarioLogueado && in_array($nombreArchivo, $this->scriptsUsuarioLogueado)) {
                continue;
            }

            // Obtener configuración (dependencias, etc.)
            $dependencias = $this->obtenerDependencias($nombreArchivo);
            $version = $this->obtenerVersion();

            // Encolar el script
            wp_enqueue_script(
                $nombreArchivo,
                $urlJs . $nombreArchivo . '.js',
                $dependencias,
                $version,
                true
            );
        }
    }

    /**
     * Obtener dependencias para un script.
     *
     * @param string $handle Nombre del script.
     * @return array
     */
    private function obtenerDependencias(string $handle): array
    {
        if (isset($this->configuracionEspecial[$handle]['deps'])) {
            return $this->configuracionEspecial[$handle]['deps'];
        }
        return [];
    }

    /**
     * Obtener versión del script (con cache busting en desarrollo).
     *
     * @return string
     */
    private function obtenerVersion(): string
    {
        if ($this->modoDesarrollo) {
            return $this->versionGlobal . '.' . mt_rand();
        }
        return $this->versionGlobal;
    }

    /**
     * Configurar localizaciones de scripts.
     *
     * @return void
     */
    private function configurarLocalizaciones(): void
    {
        $this->configurarLocalizacionAjaxPage();
        $this->configurarLocalizacionSiteConfig();

        if (is_user_logged_in()) {
            $this->configurarLocalizacionesUsuarioLogueado();
        }

        $this->configurarLocalizacionesAdicionales();
    }

    /**
     * Localización para ajaxPage.
     *
     * @return void
     */
    private function configurarLocalizacionAjaxPage(): void
    {
        wp_localize_script('ajaxPage', 'ajaxPage', [
            'logeado' => is_user_logged_in()
        ]);
    }

    /**
     * Configuración global de URLs para JavaScript.
     *
     * @return void
     */
    private function configurarLocalizacionSiteConfig(): void
    {
        $uploadDir = wp_upload_dir();

        wp_localize_script('ajaxPage', 'siteConfig', [
            'siteUrl'       => site_url(),
            'homeUrl'       => home_url(),
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'restUrl'       => rest_url(),
            'wsUrl'         => 'wss://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/ws',
            'uploadsUrl'    => $uploadDir['baseurl'],
            'uploadsPath'   => trailingslashit(site_url()) . 'wp-content/uploads',
            'themeUrl'      => get_template_directory_uri(),
            'defaultAvatar' => get_template_directory_uri() . '/assets/images/perfildefault.jpg'
        ]);
    }

    /**
     * Localizaciones para usuario logueado.
     *
     * @return void
     */
    private function configurarLocalizacionesUsuarioLogueado(): void
    {
        $nonce = wp_create_nonce('wp_rest');

        wp_localize_script('galleV2', 'galleV2', [
            'nonce'  => $nonce,
            'apiUrl' => esc_url_raw(rest_url('galle/v2/guardarMensaje/')),
            'emisor' => get_current_user_id()
        ]);
    }

    /**
     * Localizaciones adicionales para AJAX.
     *
     * @return void
     */
    private function configurarLocalizacionesAdicionales(): void
    {
        $ajaxUrl = admin_url('admin-ajax.php');
        $audioClave = $_ENV['AUDIOCLAVE'] ?? '';

        wp_add_inline_script('genericAjax', 'const wpAdminUrl = "' . admin_url() . '";', 'before');

        $localizaciones = [
            'subida' => [
                'my_ajax_object',
                ['ajax_url' => $ajaxUrl]
            ],
            'social-post-script' => [
                'my_ajax_object',
                ['ajax_url' => $ajaxUrl, 'social_post_nonce' => wp_create_nonce('social-post-nonce')]
            ],
            'my-ajax-script' => [
                'ajax_params',
                ['ajax_url' => $ajaxUrl]
            ],
            'reproductor' => [
                'audioSettings',
                ['nonce' => wp_create_nonce('wp_rest')]
            ],
            'wavejs' => [
                'audioSettings',
                [
                    'nonce'         => wp_create_nonce('wp_rest'),
                    'encryptionKey' => $audioClave,
                    'key'           => $audioClave,
                    'restUrl'       => rest_url()
                ]
            ],
            'form-script' => [
                'wpData',
                ['isAdmin' => current_user_can('administrator')]
            ],
        ];

        foreach ($localizaciones as $handle => $data) {
            wp_localize_script($handle, $data[0], $data[1]);
        }
    }

    /**
     * Encolar script para calcular altura correcta (--vh).
     *
     * @return void
     */
    public function encolarScriptVH(): void
    {
        wp_register_script('script-base', '');
        wp_enqueue_script('script-base');

        $scriptInline = <<<EOD
        function setVHVariable() {
            var vh;
            if (window.visualViewport) {
                vh = window.visualViewport.height * 0.01;
            } else {
                vh = window.innerHeight * 0.01;
            }
            document.documentElement.style.setProperty('--vh', vh + 'px');
        }

        document.addEventListener('DOMContentLoaded', function() {
            setVHVariable();

            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', setVHVariable);
            } else {
                window.addEventListener('resize', setVHVariable);
            }
        });
EOD;

        wp_add_inline_script('script-base', $scriptInline);
    }

    /**
     * Añadir un script a la lista de exclusión.
     *
     * @param string $handle Nombre del script a excluir.
     * @return void
     */
    public function excluirScript(string $handle): void
    {
        if (!in_array($handle, $this->scriptsExcluidos)) {
            $this->scriptsExcluidos[] = $handle;
        }
    }

    /**
     * Añadir un script a la lista de solo usuarios logueados.
     *
     * @param string $handle Nombre del script.
     * @return void
     */
    public function requerirLogin(string $handle): void
    {
        if (!in_array($handle, $this->scriptsUsuarioLogueado)) {
            $this->scriptsUsuarioLogueado[] = $handle;
        }
    }

    /**
     * Configurar dependencias para un script.
     *
     * @param string $handle       Nombre del script.
     * @param array  $dependencias Lista de dependencias.
     * @return void
     */
    public function configurarDependencias(string $handle, array $dependencias): void
    {
        if (!isset($this->configuracionEspecial[$handle])) {
            $this->configuracionEspecial[$handle] = [];
        }
        $this->configuracionEspecial[$handle]['deps'] = $dependencias;
    }
}

ScriptsManager::obtenerInstancia()->inicializar();

/**
 * @deprecated Usar ScriptsManager::obtenerInstancia()
 */
function scriptsOrdenados()
{
    // Mantenida por compatibilidad - ScriptsManager se encarga ahora
}

