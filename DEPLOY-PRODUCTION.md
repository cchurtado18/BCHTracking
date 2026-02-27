# Checklist para subir a producción — BCH Tracking / SkyLink CH Cargo

## Lo que ya está bien

- **Configuración**: `APP_DEBUG` y `APP_ENV` usan variables de entorno (por defecto `production` y `false`).
- **Sin dd/dump en código**: No hay `dd()`, `dump()` ni `ray()` en la app.
- **Rutas web**: Panel detrás de `auth`; rutas sensibles (admin) detrás de middleware `admin`.
- **CSRF**: Laravel aplica protección CSRF en formularios web.
- **Contraseñas**: Se hashean con bcrypt; reglas de validación (mínimo 8 caracteres, confirmación).
- **.gitignore**: `.env` y `.env.production` están ignorados; no se suben secretos al repo.
- **Migraciones**: Estructura de migraciones coherente (usuarios, preregistros, consolidaciones, entregas, auditoría, etc.).

---

## Antes de subir a producción

### 1. Variables de entorno en el servidor

En el `.env` de producción (o en el panel del hosting) configura:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Generar con: php artisan key:generate
APP_KEY=base64:...

# Base de datos (MySQL/PostgreSQL recomendado en producción)
DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

# Sesión segura si usas HTTPS
SESSION_SECURE_COOKIE=true

# Logs: no dejar en debug
LOG_LEVEL=warning
```

No uses `APP_DEBUG=true` en producción (expone errores y rutas).

### 2. Base de datos

- Ejecutar migraciones: `php artisan migrate --force`
- Si usas seeders (usuarios iniciales, etc.): `php artisan db:seed --force`

### 3. Storage y archivos

- Enlazar storage público (fotos de preregistros, logos de agencias):  
  `php artisan storage:link`
- Asegurar permisos de escritura en `storage/` y `bootstrap/cache/` (ej. `775` o según tu servidor).

### 4. Caché y optimización

Después del deploy:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Si usas traducciones: `php artisan event:cache` (cuando aplique).

### 5. APIs (`routes/api.php`) — protegidas con Sanctum

Las rutas API están protegidas con **Laravel Sanctum**. Para acceder:

- **Obtener token**: `POST /api/auth/token` con `email` y `password` (y opcional `device_name`). Respuesta: `{ "token": "...", "user": {...} }`.
- **Usar token**: en cada petición enviar el header `Authorization: Bearer <token>`.
- **Desde el panel**: el usuario puede ir a **Tokens API** en el menú, crear un token con nombre (ej. "App móvil") y copiarlo (solo se muestra una vez).
- **Público (sin token)**: solo `GET /api/public/tracking/{código}` para consulta de tracking por clientes.

### 6. HTTPS

- Servir la app solo por HTTPS.
- En `.env`: `SESSION_SECURE_COOKIE=true` y `APP_URL=https://...`.

### 7. Backups

- Programar backups de la base de datos y, si aplica, del disco `storage/app/public` (fotos y logos).

### 8. Monitoreo (opcional)

- Revisar `storage/logs/laravel.log` ante errores.
- Configurar alertas o monitoreo (UptimeRobot, Sentry, etc.) si lo necesitas.

---

## Resumen

| Aspecto              | Estado |
|----------------------|--------|
| Código listo         | Sí     |
| Config por entorno   | Sí     |
| Debug desactivable   | Sí     |
| Auth en panel        | Sí     |
| APIs sin auth        | Revisar si se usan |
| Storage link         | Ejecutar en servidor |
| Migraciones          | Ejecutar en servidor |
| .env en producción  | Configurar y no commitear |

Con estos pasos, el sistema está en condiciones de subirse a producción. Lo crítico es: **APP_DEBUG=false**, **APP_URL** correcto, **storage:link**, **migrate** y, si usas las APIs, **protegerlas**.
