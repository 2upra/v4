<?php

namespace Kamples\Services\Feed;

use Kamples\Services\Feed\FeedService;

/**
 * Servicio del algoritmo de recomendacion de posts (Fachada).
 * 
 * Calcula puntuaciones personalizadas para cada post basandose en:
 * - Usuarios seguidos
 * - Intereses del usuario
 * - Likes e interacciones
 * - Similitud de contenido
 * - Decaimiento temporal
 *
 * @since 1.0.0
 */
class AlgoritmoService
{
    private static ?AlgoritmoService $instancia = null;
    private ?\Logger $logger = null;
    private AlgoritmoPuntuacionService $puntuacionService;
    private AlgoritmoSimilitudService $similitudService;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->puntuacionService = AlgoritmoPuntuacionService::obtenerInstancia();
        $this->similitudService = AlgoritmoSimilitudService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Calcula el feed personalizado para un usuario.
     *
     * @param int $userId ID del usuario
     * @param string $identifier Identificador de busqueda
     * @param int|null $similarTo ID del post de referencia para similitud
     * @param string|null $tipoUsuario Tipo de usuario (Fan, Artista)
     * @return array Puntuaciones de posts ordenadas
     */
    public function calcularFeedPersonalizado(
        int $userId,
        string $identifier = '',
        ?int $similarTo = null,
        ?string $tipoUsuario = null
    ): array {
        $tiempoInicio = microtime(true);
        $this->log('debug', "Inicio calcularFeedPersonalizado - Usuario: $userId");

        $feedService = FeedService::obtenerInstancia();
        $datos = $feedService->obtenerDatosFeedConCache($userId);

        if (empty($datos)) {
            return [];
        }

        $usuario = $this->obtenerUsuario($userId);
        if (empty($usuario)) {
            return [];
        }

        $vistas = $this->obtenerYProcesarVistasPosts($userId);
        $esAdmin = $this->esUsuarioAdmin($usuario);
        $decaimiento = $this->calcularDecaimiento($datos);

        $puntos = $this->calcularPuntosBatch(
            $datos,
            $esAdmin,
            $vistas,
            $identifier,
            $similarTo,
            $userId,
            $decaimiento,
            $tipoUsuario
        );

        if (empty($puntos)) {
            return [];
        }

        $puntos = $this->ordenarYLimitarPuntos($puntos);

        $duracion = microtime(true) - $tiempoInicio;
        $this->log('debug', "Fin calcularFeedPersonalizado - Duracion: {$duracion}s");

        return $puntos;
    }

    /**
     * Calcula puntos para un lote de posts.
     */
    private function calcularPuntosBatch(
        array $datos,
        bool $esAdmin,
        array $vistas,
        string $identifier,
        ?int $similarTo,
        int $userId,
        array $decaimiento,
        ?string $tipoUsuario
    ): array {
        if (!isset($datos['author_results'])) {
            return [];
        }

        $actual = current_time('timestamp');
        $puntos = [];

        foreach ($datos['author_results'] as $id => $post) {
            try {
                $pFinal = $this->calcularPuntosParaPost(
                    (int)$id,
                    $post,
                    $datos,
                    $esAdmin,
                    $vistas,
                    $identifier,
                    $similarTo,
                    $actual,
                    $decaimiento,
                    $tipoUsuario
                );

                if (is_numeric($pFinal) && $pFinal > 0) {
                    $puntos[$id] = max($pFinal, 0);
                }
            } catch (\Exception $e) {
                $this->log('error', "Error calculando puntos para post $id: " . $e->getMessage());
                continue;
            }
        }

        return $puntos;
    }

    /**
     * Calcula puntos para un post individual.
     */
    private function calcularPuntosParaPost(
        int $postId,
        object $postData,
        array $datos,
        bool $esAdmin,
        array $vistasPosts,
        string $identifier,
        ?int $similarTo,
        int $actualTimestamp,
        array $decaimientoF,
        ?string $tipoUsuario
    ): float {
        $autorId = $postData->post_author;
        $postDate = $postData->post_date;

        $postTimestamp = is_string($postDate) ? strtotime($postDate) : $postDate;
        $diasPubli = (int)floor(($actualTimestamp - $postTimestamp) / (3600 * 24));
        $factorTiempo = $decaimientoF[$diasPubli] ?? $this->getDecayFactor($diasPubli);

        /* Puntos por usuarios seguidos */
        $siguiendo = $datos['siguiendo'] ?? [];
        $pUsuario = in_array($autorId, $siguiendo) ? 20 : 0;

        /* Puntos por intereses */
        $pIntereses = $this->puntuacionService->calcularPuntosIntereses($postId, $datos);

        /* Puntos por identifier (busqueda) */
        $pIdentifier = 0;
        if (!empty($identifier)) {
            $pIdentifier = $this->puntuacionService->calcularPuntosIdentifier($postId, $identifier, $datos);
        }

        /* Puntos por similaridad */
        $pSimilarTo = 0;
        if (!empty($similarTo)) {
            $pSimilarTo = $this->similitudService->calcularPuntosSimilarTo($postId, $similarTo, $datos);
        }

        /* Puntos por likes */
        $puntosLikes = $this->puntuacionService->calcularPuntosLikes($postId, $datos);

        /* Verificar estados del post */
        $metaData = $datos['meta_data'] ?? [];
        $metaVerificado = isset($metaData[$postId]['Verificado']) && ($metaData[$postId]['Verificado'] === '1');
        $metaPostAut = isset($metaData[$postId]['postAut']) && ($metaData[$postId]['postAut'] === '1');

        /* Puntos por target de audiencia */
        $pArtistaFan = $this->puntuacionService->calcularPuntosTarget($postId, $datos, $tipoUsuario, $similarTo);

        /* Calcular puntos finales base */
        $pFinal = $this->puntuacionService->calcularPuntosFinales(
            $pUsuario,
            $pIntereses + $pSimilarTo + $pArtistaFan,
            $puntosLikes,
            $metaVerificado,
            $metaPostAut,
            $esAdmin
        );

        $pFinal += $pIdentifier;

        /* Reduccion por vistas */
        if (isset($vistasPosts[$postId])) {
            $v = $vistasPosts[$postId]['count'];
            $rPuntos = $v * 10;
            $pFinal -= $rPuntos;
        }

        /* Aplicar aleatoriedad y factor de tiempo */
        $aleatoriedad = mt_rand(0, 20);
        $ajusteExtra = mt_rand(-50, 50);
        $pFinal = ($pFinal * (1 + ($aleatoriedad / 100))) * $factorTiempo;
        $pFinal += $ajusteExtra;

        return $pFinal;
    }

    /**
     * Obtiene los datos de un usuario.
     */
    private function obtenerUsuario(int $userId)
    {
        $usuario = get_userdata($userId);
        if (!$usuario || !is_object($usuario)) {
            return [];
        }
        return $usuario;
    }

    /**
     * Verifica si el usuario es administrador.
     */
    private function esUsuarioAdmin($usuario): bool
    {
        return in_array('administrator', (array)$usuario->roles);
    }

    /**
     * Pre-calcula los factores de decaimiento temporal para los posts.
     */
    private function calcularDecaimiento(array $datos): array
    {
        $actual = current_time('timestamp');
        $decaimiento = [];

        if (!isset($datos['author_results'])) {
            return $decaimiento;
        }

        foreach ($datos['author_results'] as $post) {
            $fecha = is_string($post->post_date) ? strtotime($post->post_date) : $post->post_date;
            $dias = (int)floor(($actual - $fecha) / (3600 * 24));

            if (!isset($decaimiento[$dias])) {
                $decaimiento[$dias] = $this->getDecayFactor($dias);
            }
        }

        return $decaimiento;
    }

    /**
     * Ordena y limita los puntos al maximo permitido.
     */
    private function ordenarYLimitarPuntos(array $puntos): array
    {
        if (!empty($puntos)) {
            arsort($puntos);
            $limite = defined('POSTINLIMIT') ? POSTINLIMIT : 1000;
            $puntos = array_slice($puntos, 0, $limite, true);
        }
        return $puntos;
    }

    /**
     * Obtiene el factor de decaimiento para un numero de dias.
     */
    public function getDecayFactor(int $days, bool $useDecay = false): float
    {
        static $decaimiento = [];
        static $useDecayStatic = false;

        if ($useDecay) {
            $useDecayStatic = $useDecay;
        }

        if (!$useDecayStatic) {
            return 1.0;
        }

        if (empty($decaimiento)) {
            for ($d = 0; $d <= 365; $d++) {
                $decaimiento[$d] = pow(0.99, $d);
            }
        }

        $days = min(max(0, $days), 365);
        return $decaimiento[$days];
    }

    /**
     * Obtiene y procesa las vistas de posts del usuario.
     */
    private function obtenerYProcesarVistasPosts(int $userId): array
    {
        if (!function_exists('obtenerVistasPosts')) {
            return [];
        }

        $vistasPosts = obtenerVistasPosts($userId);
        $resultado = [];

        if (!empty($vistasPosts)) {
            foreach ($vistasPosts as $postId => $viewData) {
                $resultado[$postId] = [
                    'count' => $viewData['count'],
                    'last_view' => date('Y-m-d H:i:s', $viewData['last_view']),
                ];
            }
        }

        return $resultado;
    }

    /**
     * Recalcula feed similar (delega a AlgoritmoCronService).
     */
    public function recalcularSimilarToFeed(): void
    {
        $cronService = AlgoritmoCronService::obtenerInstancia();
        $cronService->recalcularSimilarToFeed();
    }

    private function log(string $nivel, string $mensaje): void
    {
        if ($this->logger) {
            $this->logger->$nivel('algoritmo', $mensaje);
        }
    }
}
