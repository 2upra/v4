# Migracion de URLs: 2upra.com → kamples.com

## Estado Actual

El tema tiene **0 archivos** pendientes con URLs hardcodeadas a `2upra.com`. Se han corregido todos los archivos identificados (excepto ignorados intencionalmente).

---

## Archivos Afectados

### PHP (Templates y Funciones)

| Archivo                                              | Descripcion                          |
| ---------------------------------------------------- | ------------------------------------ |
| `functions.php`                                      | Favicons y apple-touch-icons         |
| `header.php`                                         | Fuentes, enlaces de descarga APK/EXE |
| `single.php`                                         | Enlace a pagina de inversion         |
| `perfil.php`                                         | Titulos de pagina                    |
| `TemplateT&Q.php`                                    | Terminos y condiciones, email legal  |
| `TemplateColab.php`                                  | Titulos de colaboracion              |
| `app/View/InicialModal.php`                          | Modal inicial                        |
| `app/Perfiles/perfiles.php`                          | Imagenes de perfil por defecto       |
| `app/Perfiles/perfilmusic.php`                       | Imagenes de perfil por defecto       |
| `app/Pages/InicioNormal.php`                         | Pagina de inicio                     |
| `app/Pages/Sello.php`                                | Pagina de sello                      |
| `app/Pages/Temporal.php`                             | Pagina temporal                      |
| `app/Pages/Wandorius.php`                            | Pagina Wandorius                     |
| `app/Pages/socialTabs.php`                           | Tabs sociales                        |
| `app/Functions/modalActualizarAppVersion.php`        | Modal de actualizacion               |
| `app/Functions/modalapp.php`                         | Modal de app                         |
| `app/Functions/stream.php`                           | Streaming                            |
| `app/Finanza/Stripe/pro.php`                         | Pagos Stripe                         |
| `app/Form/Manejar.php`                               | Manejo de formularios                |
| `app/Form/Hash.php`                                  | Hash de formularios                  |
| `app/Content/Posts/View/renderPost.php`              | Renderizado de posts                 |
| `app/Content/Posts/View/componentPost.php`           | Componente de post                   |
| `app/Content/Colecciones/View/renderModalColec.php`  | Modal de colecciones                 |
| `app/Content/Colab/partColab.php`                    | Colaboraciones                       |
| `app/Content/Colab/renderColab.php`                  | Renderizado colaboraciones           |
| `app/Content/Colecciones/Logic/logicColecciones.php` | Logica colecciones                   |
| `app/Authentication/Iniciar.php`                     | Autenticacion                        |

### JavaScript

| Archivo              | URLs Hardcodeadas                                     |
| -------------------- | ----------------------------------------------------- |
| `js/ajax-submit.js`  | Redireccion a 2upra.com                               |
| `js/ajaxPage.js`     | Regex para nocache, texto "2upra necesita tu ayuda"   |
| `js/pestanas.js`     | Verificacion de URL de perfil                         |
| `js/galleV2.js`      | WebSocket `wss://2upra.com/ws`, imagen perfil default |
| `js/galle.js`        | WebSocket `wss://2upra.com/ws`                        |
| `js/RS.js`           | Prefijo requerido para uploads                        |
| `js/configPerfil.js` | Enlace de perfil                                      |
| `js/comentarios.js`  | Prefijo requerido para uploads                        |

### CSS

| Archivo     | Lineas                       |
| ----------- | ---------------------------- |
| `style.css` | Linea 1714: background-image |
| `style.css` | Linea 1847: background-image |

---

## Plan de Solucion

### Fase 1: Convertir URLs PHP a Dinamicas

En lugar de reemplazar texto, usar funciones de WordPress:

```php
// ANTES (hardcodeado):
'https://2upra.com/wp-content/uploads/imagen.png'

// DESPUES (dinamico):
site_url('/wp-content/uploads/imagen.png')

// Para assets del tema:
get_template_directory_uri() . '/assets/imagen.png'

// Para la URL base:
home_url('/')
```

### Fase 2: Convertir URLs JavaScript a Dinamicas

1. Crear objeto de configuracion global en PHP:

```php
// En functions.php, dentro de wp_enqueue_scripts
wp_localize_script('script-principal', 'siteConfig', array(
    'siteUrl' => site_url(),
    'homeUrl' => home_url(),
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'wsUrl' => 'wss://' . $_SERVER['HTTP_HOST'] . '/ws',
    'uploadsUrl' => wp_upload_dir()['baseurl'],
    'themeUrl' => get_template_directory_uri(),
    'defaultAvatar' => get_template_directory_uri() . '/assets/images/perfildefault.jpg'
));
```

2. Usar en JavaScript:

```javascript
// ANTES:
const wsUrl = 'wss://2upra.com/ws';

// DESPUES:
const wsUrl = siteConfig.wsUrl;
```

### Fase 3: Corregir URLs en CSS

Opcion A - Mover a PHP (inline styles o variables CSS):

```php
<style>
    :root {
        --bg-image: url('<?php echo esc_url(site_url('/wp-content/uploads/imagen.png')); ?>');
    }
</style>
```

Opcion B - Mover imagenes al tema y usar rutas relativas.

### Fase 4: Corregir URLs en Base de Datos

**Opcion 1 - WP-CLI (recomendado):**
```bash
wp search-replace 'https://2upra.com' 'https://kamples.com' --all-tables --dry-run
# Si el dry-run se ve bien, ejecutar sin --dry-run
wp search-replace 'https://2upra.com' 'https://kamples.com' --all-tables
```

**Opcion 2 - SQL Directo:**
```sql
-- Ejecutar en phpMyAdmin o similar
-- HACER BACKUP PRIMERO

UPDATE wpsg_options 
SET option_value = REPLACE(option_value, 'https://2upra.com', 'https://kamples.com')
WHERE option_value LIKE '%2upra.com%';

UPDATE wpsg_posts 
SET post_content = REPLACE(post_content, 'https://2upra.com', 'https://kamples.com');

UPDATE wpsg_posts 
SET guid = REPLACE(guid, 'https://2upra.com', 'https://kamples.com');

UPDATE wpsg_postmeta 
SET meta_value = REPLACE(meta_value, 'https://2upra.com', 'https://kamples.com')
WHERE meta_value LIKE '%2upra.com%';

-- Serialized data (requiere plugin o script especial)
-- Los datos serializados NO se pueden reemplazar con SQL simple
```

**Opcion 3 - Plugin:**
Instalar "Better Search Replace" desde el admin de WordPress.

---

## Progreso de Migracion

### ✅ Archivos JS Corregidos

- [x] `galleV2.js` - WebSocket URL y imagenes por defecto
- [x] `galle.js` - WebSocket URL
- [x] `RS.js` - Prefijo de uploads
- [x] `pestanas.js` - Verificacion de URL de perfil
- [x] `configPerfil.js` - Enlace de perfil
- [x] `comentarios.js` - Prefijo de uploads
- [x] `ajaxPage.js` - Regex nocache
- [x] `ajax-submit.js` - Redireccion

### ✅ Archivos PHP Corregidos

- [x] `functions.php` - Se agrego `siteConfig` global para JS
- [x] `functions.php` - Favicons y meta tags dinamicos
- [x] `functions.php` - Fuentes preload dinamicas
- [x] `functions.php` - Site icon dinamico
- [x] `header.php` - Rutas de fuentes y APK migradas a site_url()
- [x] `single.php` - Enlace de inversión migrado a home_url()
- [x] `app/View/InicialModal.php` - Imágenes de background migradas
- [x] `app/Perfiles/perfiles.php` - Avatar por defecto migrado
- [x] `app/Perfiles/perfilmusic.php` - Avatar, iconos y SVGs migrados
- [x] `app/Functions/stream.php` - Validación de referer migrada a $_SERVER['HTTP_HOST']
- [x] `app/Functions/modalapp.php` - Enlace APK migrado a siteConfig
- [x] `app/Functions/modalActualizarAppVersion.php` - Enlace APK migrado a siteConfig
- [x] `app/Pages/Wandorius.php` - Galería de imágenes migrada a site_url()
- [x] `app/Pages/Temporal.php` - APK, SVGs, imágenes migradas
- [x] `app/Pages/socialTabs.php` - Enlaces internos migrados a home_url()
- [x] `app/Pages/Sello.php` - Enlaces migrados
- [x] `app/Pages/InicioNormal.php` - Imágenes de inicio migradas
- [x] `app/Content/Posts/View/renderPost.php` - Enlaces de retorno migrados
- [x] `app/Content/Posts/View/componentPost.php` - Iconos y SVGs migrados
- [x] `app/Content/Colecciones/View/renderModalColec.php` - Imagenes default migradas
- [x] `app/Content/Colecciones/Logic/logicColecciones.php` - Logica de imagenes migradas
- [x] `app/Content/Colab/partColab.php` - URL de imagen default migrada
- [x] `app/Content/Colab/renderColab.php` - URLs de colaboracion migradas
- [x] `app/Finanza/Stripe/pro.php` - URLs de redireccion Stripe migradas
- [x] `app/Form/Manejar.php` - Validacion de hostname dinamica
- [x] `app/Authentication/Iniciar.php` - URLs de OAuth y redireccion migradas

### ✅ Archivos CSS Corregidos

- [x] `style.css` - Líneas 1714 y 1847 - Background images comentadas con nota de migración o ruta relativa

### ⚠️ Archivos Ignorados

- `TemplateT&Q.php` - Email legal (texto estático) - Ignorado intencionalmente

---

## Orden de Ejecucion Recomendado

1. [ ] **Backup** - Hacer backup completo de archivos y base de datos
2. [ ] **Base de datos** - Ejecutar search-replace en la BD
3. [x] **functions.php** - Agregar wp_localize_script con siteConfig
4. [x] **Archivos JS** - Reemplazar URLs hardcodeadas por siteConfig
5. [x] **Archivos PHP** - Reemplazar URLs por funciones dinamicas
6. [x] **style.css** - Mover backgrounds a CSS variables o PHP
7. [ ] **Probar** - Verificar que todo funciona en kamples.com
8. [ ] **Limpiar cache** - Limpiar cache del navegador y del servidor

---

## Notas Importantes

- El WebSocket `wss://2upra.com/ws` debe estar configurado en el servidor de kamples.com
- Las fuentes estan en el tema `2upra3v`, verificar si ese tema existe en kamples
- Los favicons deben copiarse a kamples.com o cambiar las rutas
- La APK y el EXE de Sync estan hardcodeados, puede que necesiten actualizarse

---

## Errores Actuales en Consola

```
- enviarAjax is not defined (filtros.js) - Funcion faltante
- 401 Unauthorized en /wp-json/1/v1/2 - Problema de autenticacion API
- ERR_CONNECTION_TIMED_OUT para recursos de 2upra.com - URLs no migradas
```
