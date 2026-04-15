# Pasos para subir a producción — BCH Tracking

Tu código ya está en GitHub: **https://github.com/cchurtado18/BCHTracking**

---

## ¿Qué tipo de servidor vas a usar?

### A) VPS (DigitalOcean, Linode, Vultr, etc.) — recomendado

Tienes un servidor Linux (Ubuntu) donde tú instalas todo. Sigue la guía completa:

- **Guía detallada:** [DEPLOY-DIGITALOCEAN.md](DEPLOY-DIGITALOCEAN.md)

**Resumen rápido:**

1. **Crear el servidor** (Droplet en DigitalOcean: Ubuntu 22.04, 1 GB RAM está bien para empezar).
2. **Conectarte por SSH:** `ssh root@LA_IP_DEL_SERVIDOR`
3. **En el servidor, instalar:** PHP 8.2, Composer, Nginx, MySQL (todo está en la guía).
4. **Clonar tu proyecto:**
   ```bash
   mkdir -p /var/www/bch-tracking
   cd /var/www/bch-tracking
   git clone https://github.com/cchurtado18/BCHTracking.git .
   ```
5. **Configurar Laravel:**
   ```bash
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   nano .env   # Poner APP_URL, DB_DATABASE, DB_USERNAME, DB_PASSWORD, etc.
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   chown -R www-data:www-data /var/www/bch-tracking
   chmod -R 775 storage bootstrap/cache
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
6. **Configurar Nginx** para que apunte a `/var/www/bch-tracking/public` (ver DEPLOY-DIGITALOCEAN.md Parte 5).
7. **Cuando tengas dominio:** apuntar el dominio a la IP del servidor y usar Certbot para HTTPS.

---

### B) Hosting compartido (cPanel, etc.)

Si tu hosting tiene **Git** y **PHP/Composer**:

1. En el panel, crea la base de datos MySQL y anota: nombre, usuario, contraseña, host.
2. Clona el repo en la carpeta que sirve la web (o sube el código por Git que ofrezca el panel):
   ```bash
   git clone https://github.com/cchurtado18/BCHTracking.git .
   ```
3. Por SSH o terminal del hosting:
   ```bash
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   # Editar .env con APP_URL, DB_*, etc.
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
4. En el panel, asegúrate de que el **document root** sea la carpeta **`public`** del proyecto (no la raíz).

Si el hosting no tiene SSH ni Composer, tendrías que subir los archivos a mano (o usar FTP después de hacer `composer install` en local y subir también `vendor`) y configurar el .env en el panel. Es más engorroso; un VPS suele ser más sencillo para Laravel.

---

## Checklist mínimo en cualquier servidor

| Paso | Comando / acción |
|------|-------------------|
| Código | `git clone https://github.com/cchurtado18/BCHTracking.git` (o desplegar desde GitHub) |
| Dependencias | `composer install --no-dev --optimize-autoloader` |
| Entorno | `cp .env.example .env` y editar con APP_URL, DB_*, APP_DEBUG=false |
| Clave | `php artisan key:generate` |
| Base de datos | `php artisan migrate --force` |
| Fotos/logos | `php artisan storage:link` |
| Permisos | `chmod -R 775 storage bootstrap/cache` y dueño correcto (www-data o el usuario del servidor web) |
| Caché | `php artisan config:cache` y `route:cache` / `view:cache` |
| Web | Punto de entrada = carpeta **public** |

---

## Después del primer deploy: actualizar el servidor

Cuando hagas cambios en tu Mac y los subas a GitHub:

```bash
# En tu Mac
git add .
git commit -m "Descripción del cambio"
git push
```

En el **servidor**:

```bash
cd /var/www/bch-tracking   # o la ruta donde está el proyecto
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Si cambias algo en el frontend (Vite): en local `npm run build` y sube los archivos de `public/build`, o en el servidor `npm ci && npm run build` si tienes Node instalado.
