# Paso 1 detallado: Subir el proyecto a Git (GitHub)

Todo se hace **en tu computadora**, dentro de la carpeta del proyecto. Abre la **Terminal** (Mac) o **Power Shell / CMD** (Windows) y sigue en orden.

---

## 1.1 Abrir la carpeta del proyecto en la terminal

```bash
cd /Users/carloshurtado/skylink_chcargo
```

Comprueba que estás en la raíz del proyecto (debe existir el archivo `artisan`):

```bash
ls artisan
```

Si ves `artisan` listado, estás en el sitio correcto.

---

## 1.2 Verificar que `.env` no se subirá a Git

El archivo `.env` tiene contraseñas y la clave de la app. **No debe subirse nunca.**

Comprueba que está en `.gitignore`:

```bash
grep "\.env" .gitignore
```

Deberías ver una línea que diga `.env`. Si no existe `.gitignore` o no tiene `.env`, avisa y lo añadimos.

Comprueba que Git no está siguiendo `.env` (solo si ya hiciste `git init` antes):

```bash
git status .env 2>/dev/null || echo "Git aún no inicializado o .env ignorado"
```

Si en algún momento ves `.env` en la lista de "Changes to be committed", **no lo agregues**.

---

## 1.3 (Opcional) Revisar qué archivos se van a subir

Antes de hacer el primer commit, puedes ver qué incluirá Git:

```bash
git status
```

Si Git aún no está inicializado, dirá algo como "not a git repository". En ese caso sigue al paso 1.4.

Si **ya** habías hecho `git init` antes, verás lista de archivos "untracked". Revisa que **no** aparezca:
- `.env`
- `vendor/`
- `node_modules/`

Esas carpetas/archivos deben estar en `.gitignore` y no salir en la lista. Si salen, no los agregues con `git add` (o exclúyelos).

---

## 1.4 Inicializar el repositorio Git (solo si es la primera vez)

Si nunca has ejecutado `git init` en esta carpeta:

```bash
git init
```

Verás: `Initialized empty Git repository in .../skylink_chcargo/.git/`

Si ya tenías un repo (existe la carpeta `.git`), **no** ejecutes `git init` de nuevo; pasa al paso 1.5.

---

## 1.5 Agregar todos los archivos al “staging”

Esto prepara todo el proyecto para el primer commit (respetando lo que está en `.gitignore`):

```bash
git add .
```

No deberías ver ningún mensaje. Si quieres ver qué quedó preparado:

```bash
git status
```

Verás muchos archivos en verde como "new file" o "modified". **No** debe aparecer `.env` ni la carpeta `vendor/`.

---

## 1.6 Hacer el primer commit (guardar en Git de forma local)

```bash
git commit -m "Preparar proyecto para producción - BCH Tracking"
```

El mensaje puede ser otro, por ejemplo: `"Deploy inicial"` o `"Subir a producción"`.

Deberías ver algo como: `XX files changed, XXXX insertions(+)` y `create mode 100644 ...` para muchos archivos.

Si Git te pide configurar nombre y email (solo la primera vez en esta máquina):

```bash
git config --global user.email "tu-email@ejemplo.com"
git config --global user.name "Tu Nombre"
```

Y luego repite el `git commit -m "..."`.

---

## 1.7 Crear el repositorio en GitHub

1. Entra en **https://github.com** e inicia sesión.
2. Arriba a la derecha, clic en el **+** → **New repository**.
3. **Repository name:** por ejemplo `skylink-chcargo` o `bch-tracking`.
4. **Description:** opcional, ej. "Sistema de tracking y preregistros BCH".
5. Elige **Private** o **Public**.
6. **No** marques "Add a README file", "Add .gitignore" ni "Choose a license" (ya tienes código).
7. Clic en **Create repository**.

En la página que sale después, GitHub te muestra la URL del repo. Cópiala. Será algo como:

- **HTTPS:** `https://github.com/TU_USUARIO/skylink-chcargo.git`
- **SSH:** `git@github.com:TU_USUARIO/skylink-chcargo.git`

Usa la que prefieras (HTTPS es más simple si no tienes llave SSH en GitHub).

---

## 1.8 Conectar tu carpeta local con el repositorio de GitHub

Sustituye `TU_USUARIO` y `skylink-chcargo` por tu usuario y nombre del repo real:

```bash
git remote add origin https://github.com/TU_USUARIO/skylink-chcargo.git
```

Ejemplo real si tu usuario es `carlos` y el repo `bch-tracking`:

```bash
git remote add origin https://github.com/carlos/bch-tracking.git
```

Comprueba que quedó bien:

```bash
git remote -v
```

Deberías ver algo como:

```
origin  https://github.com/TU_USUARIO/skylink-chcargo.git (fetch)
origin  https://github.com/TU_USUARIO/skylink-chcargo.git (push)
```

Si te equivocaste de URL:

```bash
git remote remove origin
git remote add origin https://github.com/TU_USUARIO/nombre-correcto.git
```

---

## 1.9 Nombrar la rama principal "main" (recomendado)

GitHub usa por defecto la rama `main`. Asegúrate de que tu rama se llame así:

```bash
git branch -M main
```

Así la rama actual (donde hiciste el commit) pasa a llamarse `main`.

---

## 1.10 Subir el código a GitHub (primer push)

```bash
git push -u origin main
```

- **Si te pide usuario y contraseña:**  
  - Usuario: tu usuario de GitHub.  
  - Contraseña: **ya no** se usa la contraseña de la cuenta. Debes usar un **Personal Access Token (PAT)**.  
    1. GitHub → **Settings** (tu perfil) → **Developer settings** → **Personal access tokens** → **Tokens (classic)**.  
    2. **Generate new token (classic)**.  
    3. Nombre ej. "Deploy BCH". Marca al menos el permiso **repo**.  
    4. Generar y **copiar el token** (solo se muestra una vez).  
    5. Cuando Git pida "Password", pega ese token (no tu contraseña de GitHub).

- **Si usas SSH** y te pide passphrase de la llave, es la que definiste al crear la llave.

Cuando termine, verás algo como: `Branch 'main' set up to track remote branch 'main' from 'origin'.`

---

## 1.11 Comprobar en GitHub

1. Refresca la página del repositorio en GitHub.
2. Deberías ver todos tus archivos y carpetas (`app`, `config`, `resources`, `routes`, etc.).
3. **No** debe aparecer la carpeta `vendor` ni el archivo `.env` (están en `.gitignore`).

---

## Resumen de comandos (orden)

```bash
cd /Users/carloshurtado/skylink_chcargo
git init
git add .
git status
git commit -m "Preparar proyecto para producción - BCH Tracking"
git remote add origin https://github.com/TU_USUARIO/skylink-chcargo.git
git branch -M main
git push -u origin main
```

(Sustituye `TU_USUARIO/skylink-chcargo` por tu usuario y nombre de repo.)

Cuando esto esté hecho, ya tienes el **Paso 1** completo y puedes seguir con el **Paso 2** (crear el Droplet en DigitalOcean) de la guía principal.
