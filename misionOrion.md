# Misión: RefactorAutomaticPostService

**Metadatos de la Misión:**
- **Nombre Clave:** RefactorAutomaticPostService
- **Archivo Principal:** app/Auto/automaticPost.php
- **Archivos de Contexto (Generación):** app/Services/AudioProcessingService.php, app/Services/IAService.php, app/Services/Post/PostCreationService.php, app/Utils/Logger.php, app/Services/FileHashService.php
- **Archivos de Contexto (Ejecución):** app/Services/AudioProcessingService.php, app/Services/IAService.php, app/Services/Post/PostCreationService.php, app/Utils/Logger.php, app/Services/FileHashService.php
- **Razón (Paso 1.1):** El archivo 'automaticPost.php' necesita refactorización significativa. Las funciones son globales y largas, violando el Principio de Responsabilidad Única (SRP). Por ejemplo, `autProcesarAudio` maneja validación, procesamiento de audio con FFmpeg, movimiento de archivos y registro de errores. Hay una repetición considerable en el manejo de errores (llamadas a `eliminarHash`, `autLog`, `manejarArchivoFallido`). Rutas de directorio y comandos de FFmpeg están hardcodeados, lo que dificulta la portabilidad y configuración. La función `automaticAudio` construye un prompt de IA muy largo y complejo, lo que dificulta su mantenimiento y escalabilidad, y su manejo de la salida JSON de la IA es defensivo pero podría ser más robusto. Además, se utiliza `0777` para la creación de directorios, lo cual es una preocupación de seguridad. 

La refactorización implicaría: 
1.  **Encapsulación:** Mover la lógica a clases dedicadas, posiblemente dentro de `app/Services/` para alinearse con la estructura del proyecto. Se podría crear un `AutomaticPostService` o extender `AudioProcessingService`. 
2.  **SRP:** Descomponer las funciones grandes en métodos más pequeños y específicos (ej., `validateAudioFile`, `stripAudioMetadata`, `createLiteVersion`, `handleFailedFile`, `generateAIAudioDescription`). 
3.  **Configuración:** Externalizar rutas hardcodeadas (FFmpeg, directorios de verificación) a un archivo de configuración o constantes. 
4.  **Manejo de Errores:** Centralizar y estandarizar el registro y manejo de errores para reducir la duplicación de código. 
5.  **Seguridad:** Corregir los permisos de `mkdir` a algo más restrictivo (ej., `0755`).

Se necesita contexto adicional para una refactorización detallada, ya que el archivo depende de funciones no definidas en él (`autLog`, `eliminarHash`, `obtenerFileIDPorURL`, `crearAutPost`, `procesarArchivoAudioPython`, `generarDescripcionIA`, `iaLog`, `actualizarUrlArchivo`). Conocer la implementación de estas funciones en los archivos sugeridos (`AudioProcessingService.php`, `IAService.php`, `PostCreationService.php`, `Logger.php`, `FileHashService.php`) es crucial para integrar el código refactorizado de manera coherente, evitar duplicidades y asegurar la compatibilidad con la lógica existente.
- **Estado:** PENDIENTE

## Tareas de Refactorización:
---
### Tarea AP-TSK-001: Encapsular autProcesarAudio en AutomaticPostService
- **ID:** AP-TSK-001
- **Estado:** PENDIENTE
- **Descripción:** Crear una nueva clase `AutomaticPostService` en `app/Services/`. Mover la lógica completa de la función global `autProcesarAudio` a un método público dentro de esta clase, por ejemplo, `processAutomaticAudio(string $rutaOriginalOne): void`. En esta etapa, el enfoque es la encapsulación; no es necesario refactorizar la lógica interna del método, pero se deben preparar las llamadas a funciones externas (`autLog`, `eliminarHash`, `obtenerFileIDPorURL`, `crearAutPost`, `manejarArchivoFallido`) para una posible inyección de dependencias o mapeo.
- **Archivos Implicados Específicos (Opcional):** app/Services/AutomaticPostService.php
- **Intentos:** 0
---
### Tarea AP-TSK-002: Encapsular automaticAudio en AutomaticPostService
- **ID:** AP-TSK-002
- **Estado:** PENDIENTE
- **Descripción:** Mover la lógica completa de la función global `automaticAudio` a un nuevo método público dentro de la clase `AutomaticPostService`, por ejemplo, `generateAutomaticAudioDescription(string $rutaArchivo, ?string $nombre_archivo = null, ?string $carpeta = null, ?string $carpeta_abuela = null): array|false`. Asegurar que las llamadas a funciones externas (`procesarArchivoAudioPython`, `generarDescripcionIA`, `iaLog`) estén correctamente mapeadas o preparadas para inyección de dependencias. No refactorizar la lógica interna del método ni la construcción del prompt en esta tarea.
- **Archivos Implicados Específicos (Opcional):** Ninguno
- **Intentos:** 0
---
### Tarea AP-TSK-003: Refactorizar Manejo de Errores y Permisos de Directorio
- **ID:** AP-TSK-003
- **Estado:** PENDIENTE
- **Descripción:** 
  1.  Introducir un método privado o protegido `handleProcessingFailure(string $rutaArchivo, string $motivo, ?int $fileId = null): void` dentro de `AutomaticPostService`. Este método encapsulará las llamadas a `eliminarHash`, `autLog`, y `manejarArchivoFallido`.
  2.  Modificar el método `processAutomaticAudio` (anteriormente `autProcesarAudio`) para que use este nuevo método `handleProcessingFailure` en lugar de las llamadas repetidas a las funciones de manejo de errores.
  3.  Mover la lógica de `manejarArchivoFallido` al interior de `AutomaticPostService` (o integrarla completamente en `handleProcessingFailure`) y actualizar la llamada a `mkdir` para usar permisos `0755` en lugar de `0777`.
- **Archivos Implicados Específicos (Opcional):** Ninguno
- **Intentos:** 0
---
### Tarea AP-TSK-004: Externalizar Rutas Hardcodeadas y Comandos FFmpeg
- **ID:** AP-TSK-004
- **Estado:** PENDIENTE
- **Descripción:** Identificar todas las rutas hardcodeadas para FFmpeg (`/usr/bin/ffmpeg`) y el directorio de verificación (`/home/asley01/MEGA/Waw/Verificar/`) dentro de `AutomaticPostService`. Introducir constantes de clase o propiedades para estas rutas y reemplazar las cadenas hardcodeadas con estas nuevas constantes/propiedades en todos los métodos de `AutomaticPostService`.
- **Archivos Implicados Específicos (Opcional):** Ninguno
- **Intentos:** 0