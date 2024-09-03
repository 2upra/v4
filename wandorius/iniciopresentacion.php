<?php
function inicio()
{
    ob_start();
?>

    <div class="XX1" id="XX1">

        <p class="XXT1">Distribuye tu música en todas partes</p>
        <p class="XXT2">Sello discográfico gratuito de Phonk/Lo-Fi para artistas emergentes</p>
        <div class="XX12">
            <button class="XXB1 boton-registro" id="botonregistro">Regístrate</button>
            <button class="XXB1 XXB3 boton-sesion" id="botonsesion">Iniciar Sesión</button>
        </div>
        <p class="XXT3">Tu música en todas las tiendas y playlists gratis</p>

        <div class="CGUNVP" id="modalregistro">
            <?php echo registrar_usuario() ?>
        </div>
        <div class="EJRINA" id="modalsesion">
            <?php echo iniciar_sesion() ?>
        </div>

        <div id="fondonegro"></div>

        <div class="XXI!">
            <?php echo $GLOBALS['spotify']; ?>
            <?php echo $GLOBALS['apple']; ?>
            <?php echo $GLOBALS['instagram']; ?>
            <?php echo $GLOBALS['facebook']; ?>
            <?php echo $GLOBALS['tiktok']; ?>
            <?php echo $GLOBALS['soundcloud']; ?>
            <?php echo $GLOBALS['tidal']; ?>
            <?php echo $GLOBALS['amazonmusic']; ?>
            <?php echo $GLOBALS['deezer']; ?>
            <?php echo $GLOBALS['youtube']; ?>
        </div>
    </div>

    <div class="XX1 XX2" style="display: none;">
        <?php
        $images = [
            [
                'url' => 'https://2upra.com/wp-content/uploads/2024/05/0177.png',
                'alt' => 'Recursos gratuitos',
                'title' => 'Recursos gratuitos para artistas',
                'description' => 'Accede a una biblioteca exclusiva de samples, plugins y sample packs de alta calidad, actualizados semanalmente. Descarga y utiliza estos recursos sin costo para impulsar tu creatividad musical.'
            ],
            [
                'url' => 'https://2upra.com/wp-content/uploads/2024/05/fsfs5.png',
                'alt' => 'Colaboraciones',
                'title' => 'Fomenta la colaboración',
                'description' => 'Publica tus proyectos musicales y conecta con otros artistas talentosos. Encuentra colaboradores para llevar tus ideas al siguiente nivel y crea música excepcional.'
            ],
            [
                'url' => 'https://2upra.com/wp-content/uploads/2024/05/asfsdf4.png',
                'alt' => 'Vende tus trabajos',
                'title' => 'Monetiza tu música',
                'description' => 'Publica tus beats y composiciones en nuestra plataforma con comisiones bajas y sin límites de subida. Alcanza un público global y genera ingresos por tu talento musical.'
            ],
            [
                'url' => 'https://2upra.com/wp-content/uploads/2024/05/adsfadsf4.png',
                'alt' => 'Sello emergente',
                'title' => 'Sello discográfico emergente',
                'description' => 'Te apoyamos en la distribución, promoción y alcance de tu música. Nuestro sello emergente te brinda las herramientas y el apoyo necesarios para que tu música llegue a un público más amplio.'
            ]
        ];

        foreach ($images as $image):
            $optimized_url = optimizeImageUrl($image['url'], 'medium', 50, 'all');
        ?>
            <div class="XXDD">
                <div class="spaceimagen">
                    <img src="<?php echo esc_url($optimized_url); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
                </div>
                <h3 class="XXD1"><?php echo esc_html($image['title']); ?></h3>
                <p class="XXD2"><?php echo esc_html($image['description']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>


    <div class="XX1 XX2 XX7" style="display: n;">
        <div class="XXDD XX9">
            <div class="XX10">
                <button class="XXB1 XXB2">Descargar</button>
                <h3 class="XXD1">Descarga nuestra app</h3>
                <p class="XXD2">Lleva tu creatividad musical a todas partes con nuestra app móvil. Accede a recursos
                    exclusivos, conecta con otros artistas y monetiza tu música directamente desde tu dispositivo.
                    ¡Descárgala ahora y descubre todo lo que puedes lograr!</p>
            </div>
            <div class="spaceimagen XX8">
                <img src="https://2upra.com/wp-content/uploads/2024/05/asdfar4.png" alt="Descargar App">
            </div>
        </div>
    </div>

<?php
    return ob_get_clean();
}
