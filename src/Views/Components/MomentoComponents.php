<?php

/**
 * Componentes de vista para Momentos
 * 
 * Contiene métodos para renderizar momentos (stories).
 *
 * @package Kamples\Views\Components
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

class MomentoComponents
{
    /**
     * Renderiza la sección completa de momentos
     * 
     * @return string HTML
     */
    public function momentos(): string
    {
        ob_start();
?>
        <?php echo $this->publicarMomento(); ?>
        <?php
        if (function_exists('publicaciones')) {
            echo publicaciones(['filtro' => 'momento', 'tab_id' => 'Samples', 'posts' => 12]);
        }
        ?>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el botón para publicar un momento
     * 
     * @return string HTML
     */
    public function publicarMomento(): string
    {
        ob_start();
    ?>
        <div class="publicarMomento">
            <?php echo isset($GLOBALS['momentoIcon']) ? $GLOBALS['momentoIcon'] : ''; ?>
        </div>
<?php
        return ob_get_clean();
    }
}
