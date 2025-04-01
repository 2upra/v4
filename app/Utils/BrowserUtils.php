<?php

//CALCULAR ALTURA CORRECTA CON SCRIPT
function innerHeight()
{
    wp_register_script('script-base', '');
    wp_enqueue_script('script-base');
    $script_inline = <<<'EOD'
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
    wp_add_inline_script('script-base', $script_inline);
}

add_action('wp_enqueue_scripts', 'innerHeight');
