<?

function render_admin_report($buttons, $contents) {
    if (!current_user_can('administrator')) return '';

    ob_start(); ?>
    <div class="QUHTCR">
        <div class="iconosacciones">
            <? foreach ($buttons as $id => $button): ?>
                <button id="<?= $id ?>" style="all:unset">
                    <?= $button['icon'] ?><span><?= $button['label'] ?></span>
                </button>
            <? endforeach; ?>
        </div>
        <? foreach ($contents as $id => $content): ?>
            <div id="<?= $id ?>" class="transacciones <?= $content['extra_class'] ?>">
                <?= $content['content'] ?>
            </div>
        <? endforeach; ?>
    </div>
    <?
    return ob_get_clean();
}

function reportesAdmin() {
    $buttons = [
        'BotonListaTransacciones' => ['icon' => $GLOBALS['iconolista'], 'label' => 'Transacciones'],
        'BotonErrores' => ['icon' => $GLOBALS['iconobugs'], 'label' => 'Reportes'],
    ];

    $contents = [
        'ContenidoListaTranssacciones' => ['content' => generate_transactions_table(), 'extra_class' => ''],
        'ContenidoErrores' => ['content' => reportes(), 'extra_class' => 'reportescontenido'],
    ];

    return render_admin_report($buttons, $contents);
}

function logsAdmin() {
    $buttons = [
        'BotonLogs1' => ['icon' => $GLOBALS['iconobugs'], 'label' => 'Propios'],
        'BotonLogs2' => ['icon' => $GLOBALS['iconobugs'], 'label' => 'Wordpress'],
    ];

    $contents = [
        'ContenidoLogs1' => ['content' => do_shortcode('[mostrar_logs_para_admin]'), 'extra_class' => 'logscontenido'],
        'ContenidoLogs2' => ['content' => do_shortcode('[mostrar_logs_para_admin_w]'), 'extra_class' => 'logscontenido'],
    ];

    return render_admin_report($buttons, $contents);
}