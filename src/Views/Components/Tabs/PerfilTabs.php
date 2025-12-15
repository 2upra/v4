<?php

namespace Kamples\Views\Components\Tabs;

/**
 * Componentes de tabs para perfiles de usuario.
 *
 * @since 2.0.0
 */
class PerfilTabs
{
    /**
     * Renderiza los tabs del perfil de usuario.
     * 
     * @param int|null $userId ID del usuario (opcional, se obtiene de la URL)
     * @return string HTML de los tabs
     */
    public static function render(?int $userId = null): string
    {
        if ($userId === null) {
            $urlPath = trim(parse_url(add_query_arg([]), PHP_URL_PATH), '/');
            $urlSegments = explode('/', $urlPath);
            $userSlug = end($urlSegments);
            $user = get_user_by('slug', $userSlug);
            $userId = $user ? $user->ID : 0;
        }

        if (!$userId) {
            return '';
        }

        ob_start();
?>
        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="Perfil"></div>
            <div data-tab="tienda"></div>
            <div data-tab="imagenes"></div>
        </div>

        <div class="tabs">
            <div class="tab-content">
                <ul class="tab-linksPerfil" id="adaptableTabsPerfil"></ul>

                <div id="Perfil" class="tab active">
                    <div class="YRGFQO">
                        <div class="LRFPKL">
                            <?php echo function_exists('perfilBanner') ? perfilBanner($userId) : ''; ?>
                        </div>
                        <div class="JNDKWD">
                            <?php echo function_exists('publicaciones') ? publicaciones(['filtro' => 'nada', 'tab_id' => 'perfil', 'posts' => 12, 'user_id' => $userId]) : ''; ?>
                        </div>
                    </div>
                </div>

                <div id="Tienda" class="tab">
                    <div class="YRGFQO tiendaTab">
                        <div class="JNDKWD">
                            <?php echo function_exists('publicaciones') ? publicaciones(['filtro' => 'tiendaPerfil', 'Tienda' => 'perfil', 'posts' => 12, 'user_id' => $userId]) : ''; ?>
                        </div>
                    </div>
                </div>

                <div id="Imagenes" class="tab">
                    <div class="YRGFQO ImagenesTab">
                        <div class="JNDKWD">
                            <?php echo function_exists('publicaciones') ? publicaciones(['filtro' => 'imagenesPerfil', 'Imagenes' => 'perfil', 'posts' => 12, 'user_id' => $userId]) : ''; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
