# Guía paso a paso: Subir a producción en DigitalOcean

Desde tu máquina local hasta el servidor en DigitalOcean, en orden.

---

## PARTE 1: En tu computadora (antes de subir)

### Paso 1.1: Revisar qué NO debe ir a Git

Abre tu proyecto y verifica que en la raíz exista `.gitignore` con al menos:

```
.env
.env.backup
.env.production
/vendor
/node_modules
/public/hot
/public/build
/storage/*.key
```

**No subas nunca** el archivo `.env` (tiene contraseñas y secretos). Solo se sube `.env.example`.

---

### Paso 1.2: Tener un `.env.example` actualizado

En la raíz del proyecto debe existir `.env.example` con las variables que el servidor necesitará (sin valores secretos). Ejemplo:

```
APP_NAME="BCH Tracking"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://tu-dominio.com

LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
```

Si ya tienes `.env.example`, revísalo y añade `SESSION_SECURE_COOKIE=true` y `LOG_LEVEL=warning` si no están.

---

### Paso 1.3: Inicializar Git (si aún no es un repositorio)

En la terminal, dentro de la carpeta del proyecto:

```bash
cd /Users/carloshurtado/skylink_chcargo
git init
git add .
git status
```

Revisa que **no** aparezca `.env` en la lista. Si aparece, no lo agregues (o bórralo del staging con `git reset .env`).

---

### Paso 1.4: Hacer el primer commit

```bash
git add .
git commit -m "Preparar proyecto para producción"
```

---

### Paso 1.5: Crear el repositorio en GitHub / GitLab / Bitbucket

1. Entra a GitHub.com (o GitLab / Bitbucket).
2. Clic en **New repository**.
3. Nombre ejemplo: `skylink-chcargo` (o el que prefieras).
4. No marques "Add README" si ya tienes código local.
5. Crea el repo.

Te mostrará la URL del repositorio, por ejemplo:
- `https://github.com/TU_USUARIO/skylink-chcargo.git`

---

### Paso 1.6: Conectar tu proyecto local con el repo y subir

En la terminal (sustituye la URL por la tuya):

```bash
git remote add origin https://github.com/TU_USUARIO/skylink-chcargo.git
git branch -M main
git push -u origin main
```

Si te pide usuario y contraseña, en GitHub ahora se usa un **Personal Access Token** en lugar de la contraseña. Puedes crearlo en: GitHub → Settings → Developer settings → Personal access tokens.

---

## PARTE 2: DigitalOcean – Crear el servidor (Droplet)

### Paso 2.1: Entrar a DigitalOcean

- Ve a [digitalocean.com](https://www.digitalocean.com) e inicia sesión.

---

### Paso 2.2: Crear un Droplet

1. Clic en **Create** → **Droplets**.
2. **Imagen**: elige **Ubuntu 22.04 (LTS)**.
3. **Plan**: para empezar, **Basic** y el plan más barato (ej. 1 GB RAM). Luego puedes subir si hace falta.
4. **Datacenter**: el más cercano a tus usuarios (ej. Nueva York o San Francisco si son de América).
5. **Autenticación**:
   - Opción A: **SSH key** (recomendado). Si ya tienes una en tu Mac, añádela (pega la clave pública).
   - Opción B: **Password**. Te enviarán la contraseña por email.
6. Nombre del Droplet: ej. `bch-tracking`.
7. Clic en **Create Droplet**.

Anota la **IP pública** del Droplet (ej. `164.92.xxx.xxx`).

---

### Paso 2.3: Conectarte al servidor por SSH

Desde tu Mac (en la terminal):

```bash
ssh root@LA_IP_QUE_TE_DIO_DIGITALOCEAN
```

Si usaste contraseña, la pegas cuando te la pida. Si usaste SSH key, entrarás sin contraseña.

Ya estás dentro del servidor (el prompt cambiará a algo como `root@bch-tracking:~#`).

---

## PARTE 3: Instalar todo en el servidor (Ubuntu)

Ejecuta estos bloques **uno tras otro** en el servidor (como `root` o con `sudo` si usas otro usuario).

### Paso 3.1: Actualizar el sistema

```bash
apt update && apt upgrade -y
```

---

### Paso 3.2: Instalar PHP 8.2 y extensiones para Laravel

```bash
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd
```

Comprueba:

```bash
php -v
```

Debe mostrar PHP 8.2.x.

---

### Paso 3.3: Instalar Composer

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
composer --version
```

---

### Paso 3.4: Instalar Nginx

```bash
apt install -y nginx
systemctl enable nginx
systemctl start nginx
```

---

### Paso 3.5: Instalar MySQL (base de datos)

```bash
apt install -y mysql-server
systemctl enable mysql
systemctl start mysql
```

Seguridad básica de MySQL:

```bash
mysql_secure_installation
```

- Contraseña root: pon una segura y guárdala.
- Las preguntas (remote root login, anonymous user, test database) puedes responder **Y** para eliminar riesgos y **Y** para recargar privilegios.

Crear base de datos y usuario para Laravel:

```bash
mysql -u root -p
```

Dentro de MySQL (sustituye `TU_PASSWORD_SEGURA` y si quieres el nombre de la base):

```sql
CREATE DATABASE bch_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bch_user'@'localhost' IDENTIFIED BY 'TU_PASSWORD_SEGURA';
GRANT ALL PRIVILEGES ON bch_tracking.* TO 'bch_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Anota: **base de datos** = `bch_tracking`, **usuario** = `bch_user`, **contraseña** = la que pusiste.

---

### Paso 3.6: (Opcional) Instalar Node.js si usas compilación frontend

Si en local usas `npm run build` para Vite/CSS/JS:

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
node -v
npm -v
```

Si tu proyecto ya tiene los archivos compilados en `public/build`, puedes no instalar Node y subir esos archivos por Git.

---

## PARTE 4: Clonar el proyecto y configurarlo

### Paso 4.1: Crear usuario para la app (recomendado)

No es obligatorio pero es buena práctica no usar `root` para la app:

```bash
adduser deploy
usermod -aG www-data deploy
```

Si prefieres usar `root` de momento, puedes saltar al Paso 4.2 y usar `root` donde ponga `deploy`.

---

### Paso 4.2: Carpeta donde vivirá Laravel

Ejemplo: `/var/www/bch-tracking`. Ajusta el nombre si quieres.

```bash
mkdir -p /var/www/bch-tracking
cd /var/www/bch-tracking
```

Si creaste usuario `deploy`:

```bash
chown deploy:www-data /var/www/bch-tracking
```

---

### Paso 4.3: Clonar el repositorio

Sustituye la URL por la de tu repo. Si es privado, necesitarás configurar acceso (SSH key del servidor en GitHub o token).

```bash
cd /var/www/bch-tracking
git clone https://github.com/TU_USUARIO/skylink-chcargo.git .
```

Si da error de permisos, hazlo como `root` y luego:

```bash
chown -R deploy:www-data /var/www/bch-tracking
```

---

### Paso 4.4: Instalar dependencias de PHP (sin dev)

```bash
cd /var/www/bch-tracking
composer install --no-dev --optimize-autoloader
```

---

### Paso 4.5: Copiar `.env.example` a `.env`

```bash
cp .env.example .env
```

---

### Paso 4.6: Editar el archivo `.env` en el servidor

```bash
nano .env
```

Configura al menos esto (ajusta valores reales):

```env
APP_NAME="BCH Tracking"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://tu-dominio.com

LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bch_tracking
DB_USERNAME=bch_user
DB_PASSWORD=LA_CONTRASEÑA_QUE_PUSISTE_EN_MYSQL

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
```

- **APP_URL**: aquí pon tu dominio real (ej. `https://tracking.midominio.com`). Si aún no tienes dominio, de momento puedes poner `http://LA_IP_DEL_DROPLET`.
- Guardar: `Ctrl+O`, Enter, salir: `Ctrl+X`.

---

### Paso 4.7: Generar la clave de la aplicación

```bash
cd /var/www/bch-tracking
php artisan key:generate
```

Verás algo como `APP_KEY=base64:...` añadido al `.env`.

---

### Paso 4.8: Ejecutar migraciones

```bash
php artisan migrate --force
```

Se crearán todas las tablas (users, preregistrations, etc.).

---

### Paso 4.9: Enlace simbólico para storage (fotos, logos)

```bash
php artisan storage:link
```

---

### Paso 4.10: (Opcional) Compilar frontend si usas Vite

Solo si en tu repo **no** está la carpeta `public/build` o quieres compilar en el servidor:

```bash
npm ci
npm run build
```

Si ya subiste `public/build` por Git, no hace falta.

---

### Paso 4.11: Permisos para Laravel

```bash
chown -R www-data:www-data /var/www/bch-tracking
chmod -R 775 storage bootstrap/cache
```

Si usas usuario `deploy`:

```bash
chown -R deploy:www-data /var/www/bch-tracking
chmod -R 775 storage bootstrap/cache
```

---

### Paso 4.12: Caché de configuración y rutas

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## PARTE 5: Configurar Nginx

### Paso 5.1: Crear el archivo de sitio de Nginx

```bash
nano /etc/nginx/sites-available/bch-tracking
```

Pega esta configuración (sustituye `tu-dominio.com` por tu dominio o deja la IP):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tu-dominio.com www.tu-dominio.com;
    root /var/www/bch-tracking/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }
}
```

Guarda (`Ctrl+O`, Enter) y sal (`Ctrl+X`).

---

### Paso 5.2: Activar el sitio y recargar Nginx

```bash
ln -s /etc/nginx/sites-available/bch-tracking /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx
```

---

### Paso 5.3: Probar con la IP (si aún no tienes dominio)

En el navegador abre: `http://LA_IP_DEL_DROPLET`

Deberías ver la aplicación (login o la página que tengas por defecto). Si ves "502 Bad Gateway", revisa que PHP-FPM esté corriendo: `systemctl status php8.2-fpm`.

---

## PARTE 6: Dominio y HTTPS (cuando tengas dominio)

### Paso 6.1: Apuntar el dominio al Droplet

En tu registrador de dominios (donde compraste el dominio):

- Crea un registro **A** (o **A record**):
  - Nombre: `@` (o el subdominio, ej. `tracking`)
  - Valor / Apunta a: **LA_IP_DE_TU_DROPLET**
  - TTL: 300 o por defecto

Si usas subdominio (ej. `tracking.midominio.com`), el nombre del registro sería `tracking`.

Espera unos minutos (hasta 48 h en casos raros) a que propague.

---

### Paso 6.2: Instalar Certbot y obtener SSL (HTTPS)

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d tu-dominio.com -d www.tu-dominio.com
```

Sigue las preguntas (email, aceptar términos). Certbot configurará HTTPS y renovación automática.

Después, en tu `.env` asegúrate de tener:

```env
APP_URL=https://tu-dominio.com
SESSION_SECURE_COOKIE=true
```

Y vuelve a cachear config:

```bash
cd /var/www/bch-tracking
php artisan config:cache
```

---

## PARTE 7: Primer usuario y verificación

### Paso 7.1: Crear el primer usuario (si no tienes seeder)

Opción A – Desde la app: si tienes ruta de registro pública, entra y regístrate. Luego en la base de datos (o con un seeder) asígnale `is_admin = 1` si debe ser admin.

Opción B – Desde el servidor con Tinker:

```bash
cd /var/www/bch-tracking
php artisan tinker
```

Dentro de Tinker (sustituye email y contraseña):

```php
\App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@tu-dominio.com',
    'password' => bcrypt('TuContraseñaSegura'),
    'is_admin' => true,
]);
exit
```

---

### Paso 7.2: Comprobar que todo funciona

- Entra a `https://tu-dominio.com` (o `http://IP` si no tienes dominio aún).
- Inicia sesión con el usuario que creaste.
- Revisa: panel, preregistros, paquetes, entregas, tokens API.
- Prueba subir una foto o crear un preregistro para confirmar permisos de `storage`.

---

## Resumen rápido (solo comandos en el servidor)

Después de tener el Droplet con PHP, Composer, Nginx y MySQL instalados:

```bash
cd /var/www/bch-tracking
git clone https://github.com/TU_USUARIO/skylink-chcargo.git .
composer install --no-dev --optimize-autoloader
cp .env.example .env
nano .env   # rellenar APP_URL, DB_*, etc.
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data /var/www/bch-tracking
chmod -R 775 storage bootstrap/cache
```

Luego configurar Nginx (Parte 5) y, cuando tengas dominio, Certbot (Parte 6).

Si en algún paso concreto te sale un error, copia el mensaje y el paso donde estás y lo vemos.
