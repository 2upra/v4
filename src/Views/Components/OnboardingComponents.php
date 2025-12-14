<?php

/**
 * Componentes de onboarding (tipo de usuario y géneros)
 * 
 * @package Kamples\Views\Components
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

class OnboardingComponents
{
    /**
     * Renderiza el modal de selección de tipo de usuario
     *
     * @return string HTML del modal
     */
    public static function renderModalTipoUsuario(): string
    {
        $userId = get_current_user_id();
        $tipoUsuario = get_user_meta($userId, 'tipoUsuario', true);

        if (!empty($tipoUsuario)) {
            return '';
        }

        $fanDiv = function_exists('img')
            ? img(site_url('/wp-content/uploads/2024/11/aUZjCl0WQ_mmLypLZNGGJA.webp'))
            : site_url('/wp-content/uploads/2024/11/aUZjCl0WQ_mmLypLZNGGJA.webp');
        $artistaBg = function_exists('img')
            ? img(site_url('/wp-content/uploads/2024/11/ODuY4qpIReS8uWqwSTAQDg.webp'))
            : site_url('/wp-content/uploads/2024/11/ODuY4qpIReS8uWqwSTAQDg.webp');

        ob_start();
?>
        <div class="modal selectorModalUsuario" style="display: none;">
            <h3>Elige un tipo de usuario...</h3>
            <div class="TIPEARTISTSF">
                <div class="selectorUsuario borde" id="fanDiv">
                    <p>Fan</p>
                </div>
                <div class="selectorUsuario borde" id="artistaDiv">
                    <p>Artista</p>
                </div>
            </div>
            <style>
                #fanDiv::before {
                    background-image: url('<?php echo esc_url($fanDiv); ?>');
                }

                #artistaDiv::before {
                    background-image: url('<?php echo esc_url($artistaBg); ?>');
                }
            </style>
            <button class="botonsecundario" style="display: none;">Siguiente</button>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el modal de selección de géneros musicales
     *
     * @return string HTML del modal
     */
    public static function renderModalGeneros(): string
    {
        $userId = get_current_user_id();
        $usuarioPreferencias = get_user_meta($userId, 'usuarioPreferencias', true);

        if (!empty($usuarioPreferencias)) {
            return '';
        }

        $generos = [
            'Trap',
            'R&B',
            'Pop',
            'EDM',
            'Disco',
            'Soul',
            'Techno',
            'Cinematic',
            'Reggaeton',
            'Hip hop',
            'Drum and Bass',
            'Rock',
            'Jazz',
            'Classical',
            'Funk',
            'Blues',
            'Dubstep',
            'House',
            'Afrobeat',
            'Phonk',
            'Rap',
            'Lo-fi',
            'Chill Out',
            'Electronic'
        ];

        ob_start();
    ?>
        <div class="modal selectorGeneros" style="display: none;">
            <h3>Elige los generos que te gustan...</h3>
            <div class="GNEROBDS">
                <?php foreach ($generos as $genero): ?>
                    <div class="borde">
                        <p><?php echo esc_html($genero); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="botonsecundario">Listo</button>
        </div>
<?php
        return ob_get_clean();
    }
}
