# Checklist antes de lanzar a producción

## Crítico

### 1. Variables de entorno (.env en el servidor)
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false` (nunca true en producción)
- [ ] `APP_URL` = URL real (ej. `https://tudominio.com`)
- [ ] `APP_KEY` generada (`php artisan key:generate` si no existe)
- [ ] `APP_TIMEZONE=America/Guatemala` (o la que uses)
- [ ] Base de datos: `DB_*` con credenciales del servidor (MySQL/PostgreSQL en producción suele ser mejor que SQLite)

### 2. Enlace de almacenamiento
Las fotos de preregistros y los logos de agencias usan `storage/app/public`. En el servidor:
```bash
php artisan storage:link
```
Sin esto, logos y fotos no se verán.

### 3. Autenticación (importante)
**Actualmente todas las rutas web son públicas** (no hay login). Cualquiera con la URL puede ver el dashboard, paquetes, entregas, etc.
- Si la app solo se usa en una red interna (VPN/ofiicina) y lo asumes aceptable, documenta que el acceso debe restringirse por red/firewall.
- Si va a estar en internet, **deberías añadir autenticación** (Laravel Breeze, Fortify o Jetstream) y proteger las rutas con `middleware('auth')`.

---

## Recomendado

### 4. Caché (mejora rendimiento)
En el servidor, después de desplegar:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
(Tras cambios en config/rutas/vistas, volver a ejecutar o `php artisan optimize:clear`.)

### 5. HTTPS
- Usar HTTPS en producción.
- Si usas HTTPS, en `.env`: `SESSION_SECURE_COOKIE=true` (o configurar en `config/session.php`).

### 6. Base de datos
- Migraciones al desplegar: `php artisan migrate --force`
- Plan de backups (diario recomendado).

### 7. Logs y errores
- Con `APP_DEBUG=false`, los errores no se muestran al usuario (bien).
- Revisar `storage/logs` o configurar un servicio (ej. Sentry) para no perder errores.

### 8. APIs
Las rutas en `routes/api.php` están sin autenticación (“sin auth por ahora”). Si algún cliente externo usa esas APIs, valora protegerlas (token, API key, Sanctum).

---

## Opcional

- **Cola de trabajos**: Si en el futuro usas colas, configurar `QUEUE_CONNECTION` y un worker (`php artisan queue:work`).
- **Copias de seguridad**: Automatizar backup de la base de datos y de `storage/app` (fotos, logos).
- **Monitoreo**: Endpoint de salud ya existe: `GET /up` (Laravel por defecto).

---

## Resumen rápido

| Qué | Acción |
|-----|--------|
| .env producción | APP_DEBUG=false, APP_ENV=production, APP_URL y DB correctos |
| Storage | `php artisan storage:link` |
| Seguridad | Valorar añadir login y proteger rutas |
| Caché | config:cache, route:cache, view:cache |
| HTTPS | Activar y SESSION_SECURE_COOKIE si aplica |
