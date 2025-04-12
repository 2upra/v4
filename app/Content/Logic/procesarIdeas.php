<?php


function manejarIdea($args, $paged)
{
    // Obtener el ID del usuario para diferenciar la caché por usuario
    $user_id = get_current_user_id();

    // Crear una clave de caché única basada en el usuario y la paginación
    $cache_key = 'idea_' . $user_id . '_' . md5(json_encode($args) . '_paged_' . $paged);

    // Intentar obtener datos desde la caché
    $cached_data = obtenerCache($cache_key);
    if ($cached_data !== false) {
        guardarLog("Cargando ideas desde la caché para el usuario {$user_id}");
        return $cached_data;
    }

    guardarLog("Cargando más ideas desde la base de datos para el usuario {$user_id}");
    // Refactor(Org): Función procesarIdeas() movida a app/Services/IdeaService.php
    $query_args = procesarIdeas($args, $paged);
    if (!$query_args) {
        ////error_log("[manejarIdea] Error al procesar ideas.");
        return false;
    }

    // Guardamos la clave de la caché en una lista asociada al usuario, para facilitar su eliminación
    $cache_master_key = 'cache_idea_user_' . $user_id;
    $cache_keys = obtenerCache($cache_master_key) ?: [];
    $cache_keys[] = $cache_key;
    guardarCache($cache_master_key, $cache_keys, 300); // Guardar lista de claves de caché

    // Guardar los resultados en la caché con una expiración de 1 día
    guardarCache($cache_key, $query_args, 300);

    return $query_args;
}

// Refactor(Org): Función procesarIdeas() movida a app/Services/IdeaService.php

// Refactor(Org): Función movida a app/AlgoritmoPost/calcularPuntos.php
