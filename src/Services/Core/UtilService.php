<?php

namespace Kamples\Services\Core;

/**
 * Servicio de utilidades generales.
 * 
 * Contiene funciones helper usadas en todo el tema.
 *
 * @since 1.0.0
 */
class UtilService
{
    private static ?UtilService $instancia = null;

    /**
     * Obtiene la instancia singleton del servicio.
     */
    public static function obtenerInstancia(): UtilService
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Normaliza texto eliminando acentos y caracteres especiales.
     *
     * @param string $texto Texto a normalizar.
     * @return string Texto normalizado.
     */
    public function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');

        $reemplazos = [
            '/[áàäâã]/u' => 'a',
            '/[éèëê]/u' => 'e',
            '/[íìïî]/u' => 'i',
            '/[óòöôõ]/u' => 'o',
            '/[úùüû]/u' => 'u',
            '/[ñ]/u' => 'n',
        ];

        foreach ($reemplazos as $patron => $reemplazo) {
            $texto = preg_replace($patron, $reemplazo, $texto);
        }

        $texto = preg_replace('/[^a-z0-9\s]+/u', '', $texto);

        return $texto;
    }

    /**
     * Genera un resumen de puntos para logging del algoritmo.
     *
     * @param int $userId ID del usuario.
     * @param array $resumenPuntos Array de puntos por post ID.
     */
    public function logResumenDePuntos(int $userId, array $resumenPuntos): void
    {
        $logger = \Logger::obtenerInstancia();
        $logger->info('algoritmo', "Feed personalizado calculado para el usuario ID: {$userId}. Total de posts: " . count($resumenPuntos));

        $resumenFormateado = [];
        foreach ($resumenPuntos as $postId => $puntos) {
            $resumenFormateado[] = "{$postId}:{$puntos}";
        }

        $logger->info('algoritmo', 'Resumen de puntos - ' . implode(', ', $resumenFormateado));
    }

    /**
     * Calcula tiempo relativo para notificaciones.
     *
     * @param string $fecha Fecha en formato compatible con DateTime.
     * @return string Tiempo relativo en español.
     */
    public function tiempoRelativo(string $fecha): string
    {
        $zonaHorariaUsuario = $_COOKIE['usuario_zona_horaria'] ?? 'UTC';

        try {
            $fechaNotificacionUTC = new \DateTime($fecha, new \DateTimeZone('UTC'));
            $fechaNotificacion = $fechaNotificacionUTC->setTimezone(new \DateTimeZone($zonaHorariaUsuario));
            $ahora = new \DateTime('now', new \DateTimeZone($zonaHorariaUsuario));
            $diferencia = $ahora->getTimestamp() - $fechaNotificacion->getTimestamp();
        } catch (\Exception $e) {
            return 'Fecha desconocida';
        }

        if ($diferencia < 60) {
            return 'Justo ahora';
        } elseif ($diferencia < 3600) {
            $minutos = (int) round($diferencia / 60);
            return "hace {$minutos} minutos";
        } elseif ($diferencia < 86400) {
            $horas = (int) round($diferencia / 3600);
            return "hace {$horas} horas";
        } elseif ($diferencia < 604800) {
            $dias = (int) round($diferencia / 86400);
            return "hace {$dias} días";
        } elseif ($diferencia < 2419200) {
            $semanas = (int) round($diferencia / 604800);
            return "hace {$semanas} semanas";
        } elseif ($diferencia < 29030400) {
            $meses = (int) round($diferencia / 2419200);
            return "hace {$meses} meses";
        } else {
            $anios = (int) round($diferencia / 29030400);
            return "hace {$anios} años";
        }
    }

    /**
     * Obtiene la zona horaria del usuario actual.
     *
     * @return string Zona horaria del usuario o 'UTC' por defecto.
     */
    public function obtenerZonaHorariaUsuario(): string
    {
        return $_COOKIE['usuario_zona_horaria'] ?? 'UTC';
    }
}
