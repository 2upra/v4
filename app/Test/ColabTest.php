<?php

// Contains test functions related to Collaborations.

// Refactor(Exec): Moved function colabTest() from app/Content/Colab/renderColab.php
function colabTest()
{
    ob_start();
?>
    <div class="IBPDFF">
        <div>
            <div>Colab pendientes</div>
            <? echo publicaciones(['post_type' => 'colab', 'filtro' => 'colabPendiente', 'posts' => 20]); ?>
        </div>
        <div>

        </div>
    </div>
<?
    return ob_get_clean();
}
