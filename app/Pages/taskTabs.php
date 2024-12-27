<?

function taskTabs()
{
    ob_start();
?>

    <div id="menuData" style="display:none;" pestanaActual="">
        <div data-tab="Tareas"></div>
    </div>

    <div class="tabs">
        <div class="tab-content">
            <div class="BPLBDE UP">
                <div class="tab INICIO S4K7I3" id="Tareas">
                    <div class="BPLBDE">
                        <div class="tareasDiv">
                            <? echo formTarea() ?>
                        </div>
                        <? echo publicaciones(['post_type' => 'tarea', 'filtro' => 'tarea', 'posts' => 12]); ?>
                    </div>
                </div>

            </div>
        </div>

    <?
    return ob_get_clean();
}
