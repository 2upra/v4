# Misión: RefactorSyncApi_Audios_001

**Metadatos de la Misión:**
- **Nombre Clave:** RefactorSyncApi_Audios_001
- **Archivo Principal:** app/Sync/api.php
- **Archivos de Contexto (Generación):** app/Services/UserService.php, app/Services/LikeService.php, app/Services/PostService.php, app/Utils/Logger.php, app/Utils/BrowserUtils.php
- **Archivos de Contexto (Ejecución):** app/Services/UserService.php, app/Services/LikeService.php, app/Services/PostService.php, app/Utils/Logger.php, app/Utils/BrowserUtils.php, app/Services/SyncService.php
- **Razón (Paso 1.1):** El archivo necesita refactorización para mejorar la separación de responsabilidades (SRP) y la mantenibilidad. Las funciones `verificarCambiosAudios` y `obtenerAudiosUsuario` contienen lógica de acceso directo a la base de datos (`$wpdb` y `get_user_meta` mezclado con consultas raw SQL) y reglas de negocio específicas (como el 'magic number' 355 para el ID de usuario). Esta lógica debería ser delegada a servicios existentes o nuevos, como `UserService`, `LikeService`, `PostService` y un potencial `SyncService` para encapsular la lógica de sincronización. Además, el uso inconsistente de `$_GET` en lugar de `$request->get_param()` y las llamadas directas a `error_log` en lugar de usar el `Logger` del proyecto son puntos a mejorar. Se necesita contexto adicional de los archivos sugeridos para entender las implementaciones actuales de los servicios y utilidades, y así poder mover la lógica de manera coherente y evitar duplicidades o rupturas de dependencias.
- **Estado:** PENDIENTE

## Tareas de Refactorización:
---
### Tarea RSYNC-001: Centralizar llamadas a log
- **ID:** RSYNC-001
- **Estado:** PENDIENTE
- **Descripción:** Reemplazar todas las llamadas directas a `error_log()` en `app/Sync/api.php` con la función `guardarLog()` proporcionada por el módulo `app/Utils/Logger.php`. Esto incluye las funciones `verificarCambiosAudios`, `obtenerAudiosUsuario`, `actualizarTimestampDescargas` y `descargarAudiosSync`.
- **Archivos Implicados Específicos (Opcional):** app/Sync/api.php, app/Utils/Logger.php
- **Intentos:** 0
---
### Tarea RSYNC-002: Estandarizar la obtención de parámetros de solicitud
- **ID:** RSYNC-002
- **Estado:** PENDIENTE
- **Descripción:** Modificar la función `verificarCambiosAudios` en `app/Sync/api.php` para que obtenga el parámetro `last_sync` utilizando `$request->get_param('last_sync')` en lugar de `$_GET['last_sync']`, asegurando consistencia con el manejo de otros parámetros de `WP_REST_Request`.
- **Archivos Implicados Específicos (Opcional):** app/Sync/api.php
- **Intentos:** 0
---
### Tarea RSYNC-003: Abstraer el manejo del ID de usuario 'magic number'
- **ID:** RSYNC-003
- **Estado:** PENDIENTE
- **Descripción:** Crear una función helper (ej. `mapSyncUserId($userId)`) que encapsule la lógica de transformar el `user_id` de `355` a `1`. Aplicar esta función al inicio de `verificarCambiosAudios` y `obtenerAudiosUsuario` en `app/Sync/api.php`. Por ahora, la función helper puede residir en `app/Sync/api.php`, con la intención de moverla a un `SyncService` en el futuro.
- **Archivos Implicados Específicos (Opcional):** app/Sync/api.php
- **Intentos:** 0
---
### Tarea RSYNC-004: Crear y utilizar `SyncService` para metadatos de sincronización
- **ID:** RSYNC-004
- **Estado:** PENDIENTE
- **Descripción:** Crear un nuevo archivo `app/Services/SyncService.php`. Mover la lógica de acceso a `user_meta` para `descargas_modificado`, `samplesGuardados_modificado`, `descargas` y `samplesGuardados` desde `app/Sync/api.php` a métodos apropiados dentro de este nuevo `SyncService` (ej. `getDescargasModificadoTimestamp($userId)`). Actualizar `verificarCambiosAudios` y `obtenerAudiosUsuario` para utilizar estos nuevos métodos del `SyncService`.
- **Archivos Implicados Específicos (Opcional):** app/Sync/api.php, app/Services/SyncService.php
- **Intentos:** 0
---
### Tarea RSYNC-005: Delegar la verificación de posts favoritos a `LikeService`
- **ID:** RSYNC-005
- **Estado:** PENDIENTE
- **Descripción:** En la función `obtenerAudiosUsuario` de `app/Sync/api.php`, delegar la consulta `$wpdb` para verificar si los posts son 'favorito' a un nuevo método en `app/Services/LikeService.php` (ej. `LikeService::getFavoritePostIdsForUser($userId, $postIds)`). Actualizar la llamada en `obtenerAudiosUsuario` para usar este nuevo método.
- **Archivos Implicados Específicos (Opcional):** app/Sync/api.php, app/Services/LikeService.php
- **Intentos:** 0
---