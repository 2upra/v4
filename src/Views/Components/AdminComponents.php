<?php

/**
 * Componentes de vista para el panel de administración
 * 
 * @package Kamples\Views\Components
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

class AdminComponents
{
    /**
     * Renderiza un panel de reporte para administradores
     *
     * @param array $buttons Botones del panel
     * @param array $contents Contenidos del panel
     * @return string HTML del panel
     */
    public static function renderAdminReport(array $buttons, array $contents): string
    {
        if (!current_user_can('administrator')) {
            return '';
        }

        ob_start();
?>
        <div class="QUHTCR">
            <div class="iconosacciones">
                <?php foreach ($buttons as $id => $button): ?>
                    <button id="<?php echo esc_attr($id); ?>" style="all:unset">
                        <?php echo $button['icon']; ?><span><?php echo esc_html($button['label']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php foreach ($contents as $id => $content): ?>
                <div id="<?php echo esc_attr($id); ?>" class="transacciones <?php echo esc_attr($content['extra_class'] ?? ''); ?>">
                    <?php echo $content['content']; ?>
                </div>
            <?php endforeach; ?>
        </div>
<?php
        return ob_get_clean();
    }

    /**
     * Renderiza el panel de reportes de admin (transacciones y reportes)
     *
     * @return string HTML del panel
     */
    public static function renderReportesAdmin(): string
    {
        $buttons = [
            'BotonListaTransacciones' => [
                'icon' => $GLOBALS['iconolista'] ?? '',
                'label' => 'Transacciones'
            ],
            'BotonErrores' => [
                'icon' => $GLOBALS['iconobugs'] ?? '',
                'label' => 'Reportes'
            ],
        ];

        $contents = [
            'ContenidoListaTranssacciones' => [
                'content' => function_exists('generate_transactions_table') ? generate_transactions_table() : '',
                'extra_class' => ''
            ],
            'ContenidoErrores' => [
                'content' => function_exists('reportes') ? reportes() : '',
                'extra_class' => 'reportescontenido'
            ],
        ];

        return self::renderAdminReport($buttons, $contents);
    }

    /**
     * Renderiza el panel de logs de admin
     *
     * @return string HTML del panel
     */
    public static function renderLogsAdmin(): string
    {
        $buttons = [
            'BotonLogs1' => [
                'icon' => $GLOBALS['iconobugs'] ?? '',
                'label' => 'Propios'
            ],
            'BotonLogs2' => [
                'icon' => $GLOBALS['iconobugs'] ?? '',
                'label' => 'Wordpress'
            ],
        ];

        $contents = [
            'ContenidoLogs1' => [
                'content' => do_shortcode('[mostrar_logs_para_admin]'),
                'extra_class' => 'logscontenido'
            ],
            'ContenidoLogs2' => [
                'content' => do_shortcode('[mostrar_logs_para_admin_w]'),
                'extra_class' => 'logscontenido'
            ],
        ];

        return self::renderAdminReport($buttons, $contents);
    }
}
