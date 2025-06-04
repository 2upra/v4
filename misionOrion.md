# Misión: RefactorMultipleAudioPosts

**Metadatos de la Misión:**
- **Nombre Clave:** RefactorMultipleAudioPosts
- **Archivo Principal:** app/Auto/multiple.php
- **Archivos de Contexto (Generación):** app/Auto/automaticPost.php, app/Services/Post/PostCreationService.php, functions.php
- **Archivos de Contexto (Ejecución):** [app/Auto/automaticPost.php, app/Services/Post/PostCreationService.php, functions.php]
- **Razón (Paso 1.1):** El archivo 'multiple.php' necesita refactorización significativa. Presenta las siguientes problemáticas:

1.  **Violación del Principio de Responsabilidad Única (SRP):** Las funciones `multiplesPost`, `procesarAudiosMultiples` y `crearAutPost` realizan múltiples tareas. Por ejemplo, `procesarAudiosMultiples` es responsable de obtener metadatos, iterar, crear nuevos posts, copiar metadatos y eliminar metadatos del post original. `crearAutPost` maneja lógica de archivos, llamada a servicios externos (`automaticAudio`), inserción de posts y adjuntos.
2.  **Repetición de Código (DRY):** Hay bloques extensos y repetitivos de llamadas a `update_post_meta` y `delete_post_meta` con lógica similar en `procesarAudiosMultiples` y `crearAutPost`. La generación de claves de metadatos (`post_audio_lite_` + `$i`) también es repetitiva.
3.  **Listas de Parámetros Largas:** La función `procesarAudiosMultiples` recibe 11 parámetros, lo que dificulta la lectura, el mantenimiento y la prueba.
4.  **Proceduralismo Extenso:** El código es puramente procedural, lo que va en contra de las buenas prácticas de desarrollo moderno, especialmente en un proyecto que parece tener una estructura de servicios y clases en `app/Services`.
5.  **Números Mágicos:** El rango `2` a `30` en los bucles es un número mágico que podría ser una constante.
6.  **Manejo de Errores Básico:** Se utiliza `error_log` y `return;` sin mensajes de error específicos en algunos casos, lo que dificulta la depuración.

**Refactorización Visualizada:**
Se podría introducir una estructura orientada a objetos, con clases como `MultiplePostProcessor` o `AudioPostGenerator`. Las operaciones repetitivas de metadatos podrían encapsularse en métodos auxiliares o en un servicio dedicado (ej. `PostMetaService`). La lógica de manejo de archivos y adjuntos podría extraerse a una clase o servicio de `FileHandler` o `AttachmentService`.

**Necesidad de Contexto Adicional:**
Para una refactorización más precisa y para asegurar la integración adecuada, es crucial revisar:
*   `app/Auto/automaticPost.php`: Para entender la función `automaticAudio` y su estructura de retorno, ya que es una dependencia crítica para la creación de posts.
*   `app/Services/Post/PostCreationService.php` y/o `app/Services/PostService.php`: Para ver si ya existen servicios o patrones de creación de posts que puedan ser reutilizados o extendidos, evitando duplicidad de lógica.
*   `functions.php`: Es probable que contenga las definiciones de `adjuntarArchivoAut` y `eliminarHash`, las cuales son funciones externas utilizadas en este archivo.
- **Estado:** PENDIENTE

## Tareas de Refactorización:
---
### Tarea RF-MPP-001: Introducir clase MultipleAudioProcessor
- **ID:** RF-MPP-001
- **Estado:** PENDIENTE
- **Descripción:** Crear una nueva clase `MultipleAudioProcessor` en `app/Services/Audio/MultipleAudioProcessor.php`. Mover la lógica principal de las funciones `multiplesPost` y `procesarAudiosMultiples` a métodos de esta nueva clase, encapsulando sus responsabilidades. La clase debe recibir sus dependencias (ej. `PostCreationService`, un nuevo `PostMetaHandler`) a través de su constructor. La función original `multiplesPost` en `app/Auto/multiple.php` deberá ser modificada para actuar como un envoltorio que instancie y llame a esta nueva clase.
- **Archivos Implicados Específicos (Opcional):** [app/Auto/multiple.php, app/Services/Audio/MultipleAudioProcessor.php]
- **Intentos:** 0
---
### Tarea RF-PMH-002: Crear un servicio PostMetaHandler
- **ID:** RF-PMH-002
- **Estado:** PENDIENTE
- **Descripción:** Crear una nueva clase `PostMetaHandler` en `app/Services/Post/PostMetaHandler.php`. Esta clase será responsable de encapsular las operaciones repetitivas de `update_post_meta` y `delete_post_meta` que se encuentran en `procesarAudiosMultiples` y `crearAutPost`. Implementar métodos como `copyMeta(int $sourcePostId, int $targetPostId, array $metaKeys)` y `deleteMetaBatch(int $postId, array $metaKeys)` para promover el principio DRY. Las funciones existentes deberán ser actualizadas para utilizar este nuevo servicio.
- **Archivos Implicados Específicos (Opcional):** [app/Auto/multiple.php, app/Services/Post/PostMetaHandler.php]
- **Intentos:** 0
---
### Tarea RF-CAP-003: Refactorizar crearAutPost y extraer lógica de archivos
- **ID:** RF-CAP-003
- **Estado:** PENDIENTE
- **Descripción:** Refactorizar la función `crearAutPost` en `app/Auto/multiple.php`. Primero, modificarla para que utilice la función `crearPost` existente en `app/Services/Post/PostCreationService.php` para la inserción inicial del post. Segundo, extraer toda la lógica relacionada con el manejo y renombrado de archivos, así como la adjunción de archivos (`adjuntarArchivoAut`), a una nueva clase `AudioFileHandler` ubicada en `app/Services/Audio/AudioFileHandler.php`. Esta nueva clase deberá manejar las operaciones de archivos y retornar los IDs de los adjuntos. `crearAutPost` se convertirá en una función orquestadora que coordine el uso de `PostCreationService`, `AudioFileHandler` y `automaticAudio`.
- **Archivos Implicados Específicos (Opcional):** [app/Auto/multiple.php, app/Services/Post/PostCreationService.php, app/Services/Audio/AudioFileHandler.php, app/Auto/automaticPost.php]
- **Intentos:** 0
