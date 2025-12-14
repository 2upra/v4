# Refactorización del Tema v4 (2upra/kamples)

> **Última actualización:** 2025-12-14
> **Estado:** En progreso - Fase 1

---

## Contexto del Proyecto

Este es un tema de WordPress para una aplicación social/musical. El código fue escrito originalmente por alguien sin experiencia en programación, por lo que requiere no solo refactorización, sino también:

- Corrección de vulnerabilidades de seguridad
- Arreglo de malas implementaciones
- Organización coherente del código
- Aplicación progresiva de principios SOLID

---

## Reglas Esenciales

### 1. Cambios Progresivos
- **NUNCA** hacer cambios drásticos
- Un archivo/módulo a la vez
- Verificar funcionamiento después de cada cambio
- Si algo se rompe, revertir inmediatamente

### 2. Principios SOLID
| Principio                 | Aplicación                                     |
| ------------------------- | ---------------------------------------------- |
| **S**ingle Responsibility | Un archivo = una responsabilidad clara         |
| **O**pen/Closed           | Código extensible sin modificar el original    |
| **L**iskov Substitution   | Clases hijas intercambiables con padres        |
| **I**nterface Segregation | Interfaces pequeñas y específicas              |
| **D**ependency Inversion  | Depender de abstracciones, no implementaciones |

### 3. Nomenclatura
- Variables y funciones: `camelCase`
- Clases: `PascalCase`
- Constantes: `SCREAMING_SNAKE_CASE`
- Archivos PHP de clases: `NombreClase.php`
- Archivos PHP de funciones: `nombreModulo.php`

### 4. Comentarios Profesionales
```php
/**
 * Descripción breve de la función.
 * 
 * Descripción más detallada si es necesaria.
 *
 * @param string $parametro Descripción del parámetro.
 * @return bool Descripción del retorno.
 * @since 1.0.0
 */
```

### 5. Seguridad
- Sanitizar TODAS las entradas: `sanitize_text_field()`, `esc_html()`, etc.
- Usar nonces en formularios
- Validar permisos con `current_user_can()`
- Escapar salidas: `esc_attr()`, `esc_html()`, `esc_url()`

### 7. Logging (OBLIGATORIO)
- **PROHIBIDO** usar `error_log()`, `print_r()`, o `var_dump()`.
- **SIEMPRE** usar la clase `Logger`:
  ```php
  $logger = \Logger::obtenerInstancia();
  $logger->info('canal', 'Mensaje');
  ```
- Canales disponibles: `like`, `chat`, `auth`, `debug`, etc. (Ver `inc/Config/constants.php`)

### 6. Estructura de Archivos

```
├── inc/                     # Módulos de soporte (funciones helpers)
│   ├── Config/              # Configuración y constantes
│   ├── Logging/             # Sistema de logs (clase Logger)
│   ├── Setup/               # Inicialización (ScriptsManager)
│   ├── Core/                # Clases principales auxiliares
│   ├── Utils/               # Utilidades y helpers
│   └── Security/            # Funciones de seguridad
│
├── src/                     # CÓDIGO REFACTORIZADO (con namespaces)
│   ├── autoload.php         # Autoloader PSR-4
│   ├── Core/                # Clases base del tema
│   ├── Services/            # Servicios (lógica de negocio)
│   ├── Controllers/         # Controladores (AJAX, REST API)
│   ├── Models/              # Modelos de datos
│   └── Views/               # Componentes de vista
│
├── app/                     # CÓDIGO LEGACY (pendiente de refactorizar)
│   ├── Functions/           # 23 archivos - funciones sueltas
│   ├── Content/             # 36 archivos - contenido y posts
│   ├── Chat/                # Sistema de chat
│   ├── Finanza/             # Sistema financiero/Stripe
│   └── ...                  # Otros módulos legacy
│
└── js/                      # Scripts JavaScript (se cargan automáticamente)
```

### 7. Namespace del Tema

Las clases refactorizadas usan el namespace `Theme\V4`:

```php
namespace Theme\V4\Services;

class LikeService
{
    // Lógica de likes refactorizada
}
```

Uso:
```php
use Theme\V4\Services\LikeService;

$likeService = new LikeService();
```

---

## Roadmap de Refactorización

### Fase 1: Organizar `functions.php` ✅ **COMPLETADA**
| Paso | Descripción                                          | Estado       |
| ---- | ---------------------------------------------------- | ------------ |
| 1.1  | Crear estructura de carpetas `inc/`                  | ✅ Completado |
| 1.2  | Extraer constantes a `inc/Config/constants.php`      | ✅ Completado |
| 1.3  | Extraer funciones de logging a `inc/Logging/`        | ✅ Completado |
| 1.4  | Extraer enqueue de scripts a `inc/Setup/scripts.php` | ✅ Completado |
| 1.5  | Implementar auto-detección de scripts en /js/        | ✅ Completado |
| 1.6  | Limpiar functions.php (solo includes)                | ✅ Completado |

> **Resultado:** `functions.php` reducido de 715 líneas a 124 líneas (~83% reducción)

### Fase 2: Migrar `/app/` a `/src/` ⬅️ **ACTUAL** (PRIORIDAD)

#### Estrategia de Migración

La idea es **empezar de cero** en `/src/` con código limpio:

1. **`/app/`** = Código legacy (funciones sueltas, sin estructura)
2. **`/src/`** = Código refactorizado (clases, namespaces, SOLID)

**Proceso por cada módulo:**
1. Analizar el archivo en `/app/`
2. Crear clase equivalente en `/src/` con namespace `Theme\V4`
3. Mantener funciones wrapper en `/app/` que llamen a la nueva clase
4. Cuando todo funcione, eliminar el archivo de `/app/`

**Ejemplo de migración:**
```php
// ANTES: app/Functions/likes.php
function manejarLike() { ... }
function likeAccion($postId, $userId) { ... }

// DESPUÉS: src/Services/LikeService.php
namespace Theme\V4\Services;

class LikeService 
{
    public function manejar(): array { ... }
    public function accion(int $postId, int $userId): void { ... }
}

// WRAPPER temporal en app/Functions/likes.php
function manejarLike() {
    return (new \Theme\V4\Services\LikeService())->manejar();
}
```

#### Pasos de la Fase 2
| Paso | Descripción                                         | Estado       |
| ---- | --------------------------------------------------- | ------------ |
| 2.1  | Crear estructura `/src/` con autoloader PSR-4       | ✅ Completado |
| 2.2  | Migrar `app/Functions/likes.php` → `src/Services/`  | ✅ Completado |
| 2.3  | Migrar `app/Functions/seguir.php` → `src/Services/` | ✅ Completado |
| 2.4  | Migrar `app/Chat/` → `src/Services/ChatService.php` | 🚧 Parcial    |
| 2.5  | Migrar `app/Content/` → `src/Services/` y `Models/` | ⏳ Pendiente  |
| 2.6  | Migrar `app/Finanza/` → `src/Services/`             | ⏳ Pendiente  |
| 2.7  | Eliminar `/app/` cuando esté vacío                  | ⏳ Pendiente  |

#### Estructura de `/src/`
```
src/
├── autoload.php           # Carga automática de clases PSR-4
├── Core/                  # Clases base (abstract, interfaces, traits)
├── Services/              # Lógica de negocio (LikeService, ChatService, etc.)
├── Controllers/           # Handlers de AJAX y REST API
├── Models/                # Modelos de datos (Post, User, Coleccion, etc.)
└── Views/                 # Componentes de vista reutilizables
```


### Fase 3: Limpiar `header.php`
| Paso | Descripción                             | Estado      |
| ---- | --------------------------------------- | ----------- |
| 3.1  | Extraer CSS inline a archivos CSS       | ⏳ Pendiente |
| 3.2  | Crear componentes de menú reutilizables | ⏳ Pendiente |
| 3.3  | Separar lógica de usuario               | ⏳ Pendiente |

### Fase 4: Organizar Templates
| Paso | Descripción                              | Estado      |

| ---- | ---------------------------------------- | ----------- |
| 3.1  | Mover Template*.php a carpeta templates/ | ⏳ Pendiente |
| 3.2  | Estandarizar estructura de templates     | ⏳ Pendiente |

### Fase 4: Modularizar JavaScript
| Paso | Descripción                   | Estado      |
| ---- | ----------------------------- | ----------- |
| 4.1  | Auditar scripts actuales      | ⏳ Pendiente |
| 4.2  | Agrupar por funcionalidad     | ⏳ Pendiente |
| 4.3  | Implementar carga condicional | ⏳ Pendiente |

### Fase 5: Segmentar CSS
| Paso | Descripción                       | Estado      |
| ---- | --------------------------------- | ----------- |
| 5.1  | Crear variables.css               | ⏳ Pendiente |
| 5.2  | Dividir style.css por componentes | ⏳ Pendiente |
| 5.3  | Eliminar estilos duplicados       | ⏳ Pendiente |

---

## Historial de Cambios

### 2025-12-14
- **[INICIO]** Creación del documento de refactorización
- **[ANÁLISIS]** Diagnóstico inicial del proyecto:
  - `functions.php`: 715 líneas, múltiples responsabilidades
  - `header.php`: 498 líneas, CSS inline, lógica mezclada
  - `style.css`: 166KB sin segmentación
  - 56 archivos JS sin modularización
  - 18 templates en la raíz
- **[1.1]** Creada estructura de carpetas `inc/` (Core, Config, Logging, Setup, Utils, Security)
- **[1.2]** Creado `inc/Config/constants.php` - Constantes de logging y rutas centralizadas
- **[1.3]** Creado `inc/Logging/Logger.php` - Clase Logger con patrón Singleton
  - Implementación OOP del sistema de logging
  - Funciones wrapper para compatibilidad con código existente
  - Métodos tipados para cada tipo de log
- **[1.3]** Modificado `functions.php` - Eliminadas funciones de logging duplicadas (~140 líneas)
- **[1.3+]** Mejora del sistema de logging:
  - Niveles: DEBUG(0), INFO(1), WARNING(2), ERROR(3), CRITICAL(4)
  - Canales independientes con configuración granular
  - Auto-limpieza de archivos grandes
  - Canal `refactor` para seguimiento de cambios
- **[1.4]** Creado `inc/Setup/scripts.php` - ScriptsManager con auto-detección de JS
  - Los scripts de /js/ se cargan automáticamente
  - Solo se especifican excepciones (dependencias, usuarios logueados)
- **[1.5]** `functions.php` reducido de 715 a ~310 líneas (56% reducción)
- **[2.0]** Nueva estructura de carpetas:
  - `/src/` - Código refactorizado con namespaces (Theme\V4)
  - `/app/` - Código legacy pendiente de refactorizar
  - `src/autoload.php` - Autoloader PSR-4 para clases en /src/
- **[FIX]** Restauradas funciones eliminadas: `incluirArchivos`, `loadingBar`, `limpiarLogs`
- **[2.2]** Migrado sistema de likes a arquitectura OOP:
  - `src/Services/LikeService.php` - Lógica de negocio (290 líneas)
  - `src/Controllers/LikeController.php` - Handler AJAX (135 líneas)
  - `app/Functions/likes.php` - Convertido a wrappers de compatibilidad
  - Mejoras: tipado estricto, inyección de dependencias, métodos SRP
- **[2.3]** Migrado sistema de seguimiento a arquitectura OOP:
  - `src/Services/SeguirService.php` - Lógica de seguimiento (190 líneas)
  - `src/Controllers/SeguirController.php` - Handler AJAX (105 líneas)
  - `app/Functions/seguir.php` - Convertido a wrappers
- **[2.4]** Migrado Core del Chat:
  - `src/Services/ChatService.php` - Gestión de tokens y seguridad
  - `src/Controllers/ChatController.php` - Endpoints REST y AJAX
  - `app/Chat/api.php` y `auxiliares.php` - Refactorizados a wrappers
- **[FIX]** Estandarización de logs: Reemplazado `error_log` por `Logger::obtenerInstancia()` en todos los servicios y controladores nuevos.

---

## Problemas de Seguridad Identificados

> Esta sección se actualizará conforme se encuentren vulnerabilidades.

| Archivo | Problema               | Severidad | Estado |
| ------- | ---------------------- | --------- | ------ |
| -       | Pendiente de auditoría | -         | ⏳      |

---

## Sistema de Logging

### Niveles de Log
| Nivel    | Valor | Uso                                               |
| -------- | ----- | ------------------------------------------------- |
| DEBUG    | 0     | Información detallada para desarrollo             |
| INFO     | 1     | Información general del flujo de la aplicación    |
| WARNING  | 2     | Situaciones inesperadas pero no críticas          |
| ERROR    | 3     | Errores que requieren atención                    |
| CRITICAL | 4     | Fallos graves que pueden causar caída del sistema |
| OFF      | 5     | Desactivar logs completamente                     |

### Canales Disponibles
| Canal        | Nivel por defecto | Solo Admin | Descripción                    |
| ------------ | ----------------- | ---------- | ------------------------------ |
| stream       | INFO              | No         | Streaming de audio             |
| seo          | INFO              | No         | Eventos de SEO                 |
| audio        | DEBUG             | No         | Procesamiento de audio         |
| rendimiento  | INFO              | No         | Métricas de rendimiento        |
| chat         | DEBUG             | Sí         | Sistema de chat                |
| stripe       | WARNING           | Sí         | Pagos y errores de Stripe      |
| automatico   | INFO              | No         | Posts automáticos              |
| guardar      | INFO              | No         | Operaciones de guardado        |
| algoritmo    | DEBUG             | Sí         | Algoritmo de recomendación     |
| ajaxPost     | DEBUG             | Sí         | Peticiones AJAX de posts       |
| ia           | INFO              | Sí         | Operaciones de IA              |
| post         | DEBUG             | Sí         | Operaciones de posts           |
| **refactor** | INFO              | No         | Seguimiento de refactorización |

### Uso del Logger

```php
// API moderna (recomendada)
$logger = Logger::obtenerInstancia();
$logger->info('refactor', 'Migrado módulo X a inc/Setup/');
$logger->warning('stripe', 'Reintentando conexión', ['intento' => 3]);
$logger->error('ia', 'Fallo en procesamiento', ['error' => $e->getMessage()]);

// Funciones wrapper (compatibilidad)
refactorLog('Cambio realizado en functions.php');
iaLog('Procesando solicitud');
```

### Configuración de Canales
Para modificar la configuración de un canal, editar `inc/Config/constants.php`:

```php
'ia' => [
    'enabled'   => true,        // Activar/desactivar canal
    'level'     => LOG_LEVEL_INFO, // Nivel mínimo para loggear
    'adminOnly' => true,        // Solo registrar para admins
],
```

---

## Ideas y Mejoras Propuestas

> Espacio para documentar ideas de mejora encontradas durante la refactorización.

1. ~~**Sistema de Logging**: Implementar clase Logger con niveles (debug, info, warning, error)~~ ✅ Completado
2. **Autoloader**: Implementar PSR-4 autoloading para clases
3. **Inyección de Dependencias**: Considerar un contenedor simple para servicios
4. **Configuración Externa**: Mover configuración a archivo .env o base de datos

---

## Notas Importantes

- El CSS se refactorizará **al final** cuando todo el código esté organizado
- Cada cambio debe ser testeado antes de continuar
- Documentar cualquier comportamiento extraño encontrado
- Si una función parece hacer demasiado, probablemente lo hace
- **Usar `refactorLog()` para registrar cambios importantes durante la refactorización**

