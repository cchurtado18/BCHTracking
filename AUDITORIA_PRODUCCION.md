# Auditoría pre-producción — BCH Tracking / Skylink CH Cargo

**Fecha:** Febrero 2025  
**Objetivo:** Identificar riesgos y tareas pendientes antes de subir a producción.

---

## Crítico (debe corregirse antes de producción)

### 1. API sin autenticación

**Rutas en `routes/api.php`** (preregistrations, consolidations, packages, agencies, deliveries, nic/consolidations) **no tienen middleware de autenticación**. Cualquiera puede:

- Crear/editar preregistros, consolidaciones, agencias, clientes.
- Procesar paquetes, subir fotos, escanear entregas, etc.

**Recomendación:**

- Si la API es solo para uso interno (móvil/app con login), proteger todas las rutas con `auth:sanctum` o `auth:api` y que el cliente envíe token.
- Si algunos endpoints deben ser públicos (ej. tracking público), dejar solo esos sin auth y proteger el resto.
- Añadir **rate limiting** a la API (ej. `throttle:60,1` por minuto) para evitar abuso.

### 2. Variables de entorno en producción

Asegurarse de que en el servidor **nunca** se use:

- `APP_DEBUG=true` → debe ser **`false`** en producción (evita exponer stack traces y datos sensibles).
- `APP_ENV=local` → debe ser **`APP_ENV=production`**.

Y que estén definidos:

- `APP_KEY` (generado con `php artisan key:generate`).
- `APP_URL` con la URL real (ej. `https://tu-dominio.com`).

### 3. Sesión y cookies con HTTPS

Si la app se sirve por **HTTPS** (recomendado en producción):

- En `.env`: `SESSION_SECURE_COOKIE=true` para que la cookie de sesión solo se envíe por HTTPS.
- Opcional: `SESSION_DOMAIN` si usas subdominios.

Si no se configura, la cookie puede enviarse por HTTP y ser vulnerable a robo.

---

## Importante (recomendado antes de producción)

### 4. Base de datos

- **No** dejar credenciales por defecto (`DB_USERNAME=root`, `DB_PASSWORD=`). Usar un usuario con permisos mínimos y contraseña fuerte.
- Si usas MySQL/PostgreSQL en producción, verificar que `DB_*` en `.env` apunten al servidor correcto y que el puerto/firewall permitan la conexión.

### 5. Logs y nivel de log

- En producción suele usarse `LOG_LEVEL=warning` o `error` para no llenar disco con `debug`.
- `LOG_CHANNEL=stack` con `LOG_STACK=single` está bien; si el tráfico crece, valorar `daily` para rotar por día.

### 6. Cola y trabajos

- Si usas `QUEUE_CONNECTION=database`, en producción debe estar corriendo un worker:  
  `php artisan queue:work --tries=3` (o con Supervisor/systemd).
- Si no usas colas, las notificaciones/emails síncronos pueden hacer las peticiones lentas.

### 7. Enlace de almacenamiento

- Ejecutar en el servidor: **`php artisan storage:link`** para que `public/storage` apunte a `storage/app/public` y se sirvan correctamente fotos y archivos subidos.

### 8. Permisos de directorios

En el servidor, típicamente:

- `storage` y `bootstrap/cache`: escribibles por el usuario con el que corre PHP (ej. `chmod -R 775 storage bootstrap/cache`).
- `.env`: solo lectura para el usuario de la app (no accesible desde el navegador).

---

## Menor / buena práctica

### 9. Caché de configuración

En producción, después de cambiar `.env` o config:

- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`

Reduce I/O y evita que cambios en `.env` no se reflejen por caché antigua.

### 10. Request de foto no usado

`App\Http\Requests\StorePreregistrationPhotoRequest` tiene `authorize()` en `false` y `rules()` vacío. **No se usa** en `PreregistrationController::uploadPhoto` (ahí se valida con `$request->validate(...)`). No bloquea nada, pero conviene o bien usarlo (authorize true + reglas de validación) o eliminarlo para no generar confusión.

### 11. .env.example

- Actualizar `.env.example` con las variables que producción va a usar (sin valores sensibles), por ejemplo:  
  `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, etc.
- Así quien despliegue sabe qué debe configurar.

### 12. Rate limiting en login

- Breeze ya aplica límite de intentos de login (p. ej. 5 intentos) vía `LoginRequest`. No hace falta cambiar nada si no quieres; solo asegurarse de no desactivarlo.

---

## Resumen de comprobaciones

| Área              | Estado / Nota                                              |
|-------------------|------------------------------------------------------------|
| APP_DEBUG         | Debe ser `false` en prod                                   |
| APP_ENV           | Debe ser `production`                                      |
| APP_KEY           | Debe existir                                               |
| APP_URL           | Debe ser la URL real (HTTPS recomendado)                   |
| Sesión segura     | Configurar SESSION_SECURE_COOKIE con HTTPS                 |
| API               | Crítico: actualmente sin auth; definir y proteger          |
| Base de datos     | Credenciales fuertes y usuario con permisos mínimos        |
| Logs              | LOG_LEVEL adecuado (warning/error en prod)                  |
| Cola              | Si se usa, worker en ejecución                             |
| storage:link      | Ejecutado en el servidor                                   |
| Permisos          | storage y bootstrap/cache escribibles                      |
| Caché de config   | config/route/view cache en prod                            |
| dd/dump           | No se encontraron en el código                            |
| SQL injection     | Uso de raw seguro (solo COALESCE, etc.)                    |
| Subida de archivos| Validación de foto (image, mimes, max) en controlador      |

---

## Checklist rápido antes de subir

1. [ ] `.env` en servidor: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` y `APP_URL` correctos.
2. [ ] Si hay HTTPS: `SESSION_SECURE_COOKIE=true`.
3. [ ] Decidir y aplicar autenticación (y rate limit) en `routes/api.php`.
4. [ ] Base de datos con usuario/contraseña seguros.
5. [ ] `php artisan storage:link` ejecutado.
6. [ ] Permisos de `storage` y `bootstrap/cache` correctos.
7. [ ] Si usas cola: worker corriendo.
8. [ ] `php artisan config:cache` (y route/view cache si aplica).
9. [ ] Probar login, preregistro, tracking público y una ruta API protegida (si ya la tienes).

Si quieres, el siguiente paso puede ser proponer cambios concretos en `routes/api.php` (middleware y rutas públicas) según cómo uses la API (solo web, solo app con token, etc.).
