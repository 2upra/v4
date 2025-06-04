# Misión: RefactorRehacerNombreAudio_SRP

**Metadatos de la Misión:**
- **Nombre Clave:** RefactorRehacerNombreAudio_SRP
- **Archivo Principal:** app/Auto/reEditarPost.php
- **Archivos de Contexto (Generación):** app/Services/IAService.php, app/Services/Post/PostAttachmentService.php, app/Utils/Logger.php, config.php
- **Archivos de Contexto (Ejecución):** app/Services/IAService.php, app/Services/Post/PostAttachmentService.php, app/Utils/Logger.php, config.php
- **Razón (Paso 1.1):** El archivo `reEditarPost.php` necesita refactorización principalmente por la violación del Principio de Responsabilidad Única (SRP) en la función `rehacerNombreAudio`. Esta función maneja la validación de permisos, la interacción con IA, la manipulación de cadenas para generar nombres, la gestión de archivos adjuntos de WordPress, la búsqueda y renombrado de archivos en el sistema de archivos, y la actualización de metadatos y URLs en la base de datos. Esto la hace difícil de leer, mantener y probar.

Además, se observan 'magic strings' y rutas de archivo hardcodeadas (ej. `/home/asley01/MEGA/Waw/X`), lo cual es una mala práctica. Las llamadas a `error_log` están comentadas, lo que sugiere una gestión de errores incompleta o inconsistente, aunque se usa `guardarLog`. La lógica de truncado de nombres (`substr`) se aplica dos veces de forma redundante y potencialmente incorrecta, pudiendo cortar el ID único.

La refactorización implicaría:
1.  **Crear una clase o servicio**: Encapsular la lógica en una clase como `PostAudioRenamingService` dentro de `app/Services/Post/` o `app/Auto/` para mejorar la organización y permitir la inyección de dependencias.
2.  **Dividir la función principal**: Descomponer `rehacerNombreAudio` en métodos más pequeños y con responsabilidades únicas (ej., `validateUserPermissions()`, `generateAIName()`, `renameWordPressAttachments()`, `handleOriginalFileRenaming()`, `updateDatabaseUrls()`).
3.  **Gestionar dependencias**: Inyectar servicios como `IAService`, `PostAttachmentService` y `Logger` en la nueva clase en lugar de depender de funciones globales.
4.  **Configuración**: Mover rutas hardcodeadas y 'magic strings' a constantes o a un archivo de configuración.
5.  **Mejorar el logging**: Asegurar que los mensajes de error y depuración se manejen consistentemente a través de un sistema de logging centralizado.

Necesito contexto adicional para:
*   **`app/Services/IAService.php`**: Para entender cómo `generarDescripcionIA` interactúa con la IA y si se pueden pasar opciones de configuración o si tiene un contrato de interfaz claro.
*   **`app/Services/Post/PostAttachmentService.php` o `app/Services/PostService.php`**: Para ver cómo `actualizarUrlArchivo` está implementada y si las operaciones de base de datos relacionadas con adjuntos pueden centralizarse allí.
*   **`app/Utils/Logger.php`**: Para entender el sistema de logging (`guardarLog`) y asegurar una implementación consistente.
*   **`config.php`**: Para identificar si ya existen configuraciones de rutas o si se pueden añadir las rutas hardcodeadas allí.
- **Estado:** PENDIENTE

## Tareas de Refactorización:
---
### Tarea RF-ClassService_001: Crear PostAudioRenamingService Class
- **ID:** RF-ClassService_001
- **Estado:** COMPLETADA
- **Descripción:** Crear una nueva clase PHP `PostAudioRenamingService` en `app/Services/Post/` para encapsular la lógica refactorizada de `rehacerNombreAudio`. Definir su constructor para aceptar dependencias (ej., `IAService`, `PostAttachmentService`, `Logger`). Inicialmente, crear un método público `renameAudio(int $postId, string $audioFilePath)` que contendrá la lógica principal.
- **Archivos Implicados Específicos (Opcional):** app/Services/Post/PostAudioRenamingService.php
- **Intentos:** 0
---
### Tarea RF-ValidatePerms_002: Extraer Validación de Permisos y Archivo
- **ID:** RF-ValidatePerms_002
- **Estado:** COMPLETADA
- **Descripción:** Mover la verificación de permisos de usuario (`user_can`) y la comprobación inicial de existencia del archivo (`file_exists`) de `rehacerNombreAudio` a un nuevo método privado, por ejemplo, `validatePermissionsAndFileExists(int $userId, string $audioFilePath)` dentro de `PostAudioRenamingService`. Este método debe devolver `true` en caso de éxito o `false` en caso de fallo, registrando los errores apropiadamente. Actualizar `rehacerNombreAudio` para llamar a este nuevo método.
- **Archivos Implicados Específicos (Opcional):** app/Auto/reEditarPost.php, app/Services/Post/PostAudioRenamingService.php
- **Intentos:** 0
---
### Tarea RF-GenerateAIName_003: Extraer Generación y Limpieza de Nombre por IA
- **ID:** RF-GenerateAIName_003
- **Estado:** FALLIDA_TEMPORALMENTE
- **Descripción:** Extraer la lógica responsable de construir el prompt de IA, llamar a `generarDescripcionIA`, y limpiar/truncar el nombre generado (`nombre_generado`, `nombre_generado_limpio`, `nombre_final_con_id`) en un nuevo método privado, por ejemplo, `generateUniqueAudioName(string $originalFileName, string $postContent)` dentro de `PostAudioRenamingService`. Este método debe devolver el nombre final único o `null` en caso de fallo. Asegurar que `IAService` sea inyectado y utilizado.
- **Archivos Implicados Específicos (Opcional):** app/Auto/reEditarPost.php, app/Services/Post/PostAudioRenamingService.php, app/Services/IAService.php
- **Intentos:** 1
---
### Tarea RF-RenameWPAttachments_004: Extraer Renombrado de Adjuntos de WordPress
- **ID:** RF-RenameWPAttachments_004
- **Estado:** PENDIENTE
- **Descripción:** Mover la lógica para obtener `attachment_id_audio` y `attachment_id_audio_lite`, y llamar a `renombrar_archivo_adjunto` para ambos, a un nuevo método privado, por ejemplo, `renameWordPressAttachments(int $postId, string $newName)` dentro de `PostAudioRenamingService`. Asegurar que `PostAudioRenamingService` utilice la función `renombrarArchivoAdjunto` de `app/Services/Post/PostAttachmentService.php`. La función global `renombrar_archivo_adjunto` en `app/Auto/reEditarPost.php` debe ser eliminada después de su migración. Este método debe devolver `true` en caso de éxito o `false` en caso de fallo.
- **Archivos Implicados Específicos (Opcional):** app/Auto/reEditarPost.php, app/Services/Post/PostAudioRenamingService.php, app/Services/Post/PostAttachmentService.php
- **Intentos:** 0
---
### Tarea RF-HandleOriginalFile_005: Gestionar Renombrado de Archivo Original y Actualización de URL
- **ID:** RF-HandleOriginalFile_005
- **Estado:** PENDIENTE
- **Descripción:** Extraer la lógica para manejar `rutaOriginal`, buscar en subcarpetas (`buscarArchivoEnSubcarpetas`), renombrar el archivo original en el servidor, actualizar la meta `rutaOriginal`, y actualizar `idHash_audioId` en la base de datos (`actualizarUrlArchivo`) en un nuevo método privado, por ejemplo, `handleOriginalFileAndDatabaseUpdates(int $postId, string $oldAudioPath, string $newAudioName)` dentro de `PostAudioRenamingService`. Externalizar la ruta hardcodeada `/home/asley01/MEGA/Waw/X` utilizada en `buscarArchivoEnSubcarpetas` a una constante de clase o a un archivo de configuración (ej. `config.php`) y actualizar su uso. Asegurar que `Logger` sea utilizado para las llamadas a `guardarLog`. Este método debe devolver `true` en caso de éxito o `false` en caso de fallo.
- **Archivos Implicados Específicos (Opcional):** app/Auto/reEditarPost.php, app/Services/Post/PostAudioRenamingService.php, app/Services/Post/PostAttachmentService.php, app/Utils/Logger.php, config.php
- **Intentos:** 0