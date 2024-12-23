<?

function perfilTabs()
{
    $url_path = trim(parse_url(add_query_arg([]), PHP_URL_PATH), '/');
    $url_segments = explode('/', $url_path);
    $user_slug = end($url_segments);
    $user = get_user_by('slug', $user_slug);
    $user_id = $user->ID;

    ob_start();
?>
    <div id="menuData" style="display:none;" pestanaActual="">
        <div data-tab="Perfil"></div>
        <div data-tab="tienda"></div>
        <div data-tab="imagenes"></div>
    </div>

    <div class="tabs">
        <div class="tab-content">
            <ul class="tab-links" id="adaptableTabsPerfil">
            </ul>
            <div id="Perfil" class="tab active">
                <div class="YRGFQO">
                    <div class="LRFPKL">
                        <? echo perfilBanner($user_id); ?>
                    </div>
                    <div class="JNDKWD">
                        <? echo publicaciones(['filtro' => 'nada', 'tab_id' => 'perfil', 'posts' => 12, 'user_id' => $user_id]); ?>
                    </div>
                </div>
            </div>

            <div id="Tienda" class="tab">
                <div class="YRGFQO tiendaTab">
                    <div class="JNDKWD">
                        <? echo publicaciones(['filtro' => 'tiendaPerfil', 'Tienda' => 'perfil', 'posts' => 12, 'user_id' => $user_id]); ?>
                    </div>
                </div>
            </div>

            <div id="Imagenes" class="tab">
                <div class="YRGFQO ImagenesTab">
                    <div class="JNDKWD">
                        <? echo publicaciones(['filtro' => 'imagenesPerfil', 'Imagenes' => 'perfil', 'posts' => 12, 'user_id' => $user_id]); ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
<?
    return ob_get_clean();
}
