# Guía de Despliegue en Hostinger y GitHub - Visor TV Pro

Esta guía detalla paso a paso cómo subir y desplegar **Visor TV Pro** (Frontend React compilado + Backend Laravel 12 API) tanto en **GitHub** como en tu hosting de **Hostinger**.

---

## 1. Subida a GitHub (Comandos para tu Terminal)

Abre tu terminal en la raíz del proyecto (`C:\Users\ashly\Documents\Visor_Tv`) y ejecuta los siguientes comandos en orden:

### Paso 1: Inicializar el repositorio Git
```bash
git init
```

### Paso 2: Agregar todos los archivos (el `.gitignore` protegerá tus contraseñas y carpetas pesadas)
```bash
git add .
```

### Paso 3: Crear el primer commit
```bash
git commit -m "feat: lanzamiento inicial de Visor TV Pro (Frontend Vite + Backend Laravel API)"
```

### Paso 4: Cambiar a la rama principal `main`
```bash
git branch -M main
```

### Paso 5: Conectar con tu repositorio en GitHub
> *Nota: Primero crea un repositorio vacío en tu cuenta de GitHub (por ejemplo llamado `visor-tv-pro`), ya sea público o privado, y copia tu URL.*

```bash
git remote add origin https://github.com/TU_USUARIO/TU_REPOSITORIO.git
```

### Paso 6: Subir el proyecto a GitHub
```bash
git push -u origin main
```

---

## 2. ¿Qué significa "Compilado para Hostinger"?

En desarrollo trabajas con archivos `.jsx` y un servidor de Node.js (`npm run dev`). Sin embargo, en un hosting compartido como Hostinger **no necesitas tener Node.js corriendo 24/7**:

1. Se ejecuta el script:
   ```cmd
   compilar_produccion.bat
   ```
   (o ejecutas en terminal `cd visortv_web && npm run build`).
2. Esto crea la carpeta **`visortv_web/dist/`**.
3. Esa carpeta contiene archivos estáticos súper optimizados (`index.html`, JavaScript empaquetado, CSS compilado y su archivo `.htaccess`).
4. **Eso es el Frontend compilado**. Se sube a Hostinger y carga en milisegundos en cualquier televisor inteligente o navegador.

---

## 3. Despliegue en Hostinger (Paso a Paso)

### Opción A (Recomendada): Subdominio para la API
- **Frontend (Web y Pantallas)**: `tudominio.com` (o `visor.tudominio.com`)
- **Backend (API Laravel)**: `api.tudominio.com`

---

### Paso 3.1: Crear la Base de Datos en Hostinger hPanel
1. Inicia sesión en tu cuenta de **Hostinger** y entra al panel **hPanel**.
2. Ve a **Bases de datos** ➔ **Gestión de MySQL**.
3. Crea una nueva base de datos:
   - Nombre de la BD: `u123456_visortv` (Hostinger le agrega tu prefijo de usuario).
   - Usuario de MySQL: `u123456_admin`
   - Contraseña segura: `GuardaEstaContraseña123!`
4. Anota estos 3 datos: **Nombre de BD**, **Usuario** y **Contraseña**.

---

### Paso 3.2: Subir el Backend (`visortv_api`)
1. En Hostinger hPanel, entra a **Administrador de Archivos** (o usa FileZilla / FTP / SSH).
2. Sube la carpeta `visortv_api` a la raíz de tu cuenta (al mismo nivel donde ves `public_html`, **NO dentro de `public_html`** para máxima seguridad):
   ```text
   /home/u123456789/
   ├── domains/
   │   └── tudominio.com/
   │       ├── public_html/       <-- Aquí irá el frontend
   │       └── visortv_api/       <-- Aquí subes el backend
   ```
3. Dentro de `visortv_api`, crea o edita el archivo `.env`:
   ```env
   APP_NAME="Visor TV Pro"
   APP_ENV=production
   APP_KEY=base64:... (genera con php artisan key:generate o copia tu key local)
   APP_DEBUG=false
   APP_URL=https://api.tudominio.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=u123456_visortv
   DB_USERNAME=u123456_admin
   DB_PASSWORD=GuardaEstaContraseña123!

   SESSION_DRIVER=database
   FILESYSTEM_DISK=public
   ```
4. Si tienes acceso SSH en Hostinger (o desde la terminal de Hostinger):
   ```bash
   cd domains/tudominio.com/visortv_api
   composer install --no-dev --optimize-autoloader
   php artisan key:generate --force
   php artisan migrate --force --seed
   php artisan storage:link
   ```
   > **Nota de credenciales iniciales sembradas**:
   > - Usuario Superadmin: `ashly`
   > - Contraseña: `admin123`

5. En Hostinger hPanel ➔ **Subdominios**, crea el subdominio `api` (`api.tudominio.com`) y haz que apunte a:
   `domains/tudominio.com/visortv_api/public`

---

### Paso 3.3: Compilar y Subir el Frontend (`visortv_web`)

1. En tu computadora, define la URL de la API de producción en `visortv_web/.env`:
   ```env
   VITE_API_BASE_URL=https://api.tudominio.com/api
   ```
2. Ejecuta en tu computadora:
   ```cmd
   compilar_produccion.bat
   ```
3. Ve a la carpeta `visortv_web/dist/`.
4. Selecciona **TODO el contenido de `visortv_web/dist/`** (`index.html`, carpeta `assets/`, `.htaccess`, `favicon.png`, etc.) y súbelo dentro de la carpeta **`public_html/`** de tu dominio en Hostinger.
5. El archivo `.htaccess` incluido en `dist` asegura que cuando entres a `/admin/dashboard`, `/admin/media` o `/sede/av-0` la página cargue directamente sin error 404.

---

## 4. Verificación y Puesta en Marcha

1. Abre tu navegador e ingresa a `https://tudominio.com/`. Verás el selector interactivo de sedes de Visor TV Pro.
2. Ingresa a `https://tudominio.com/admin` e inicia sesión con:
   - **Usuario**: `ashly`
   - **Contraseña**: `admin123`
3. ¡Listo! Tu plataforma estará 100% operativa en producción en Hostinger con soporte para transmisión en televisores y kioskos.

---

## 5. Centralizar el Sistema en tu MySQL Workbench (MySQL Remoto de Hostinger)

Para ver y administrar en tu computadora todas las tablas, usuarios, sedes y estadísticas de la web en vivo de Hostinger directamente desde tu **MySQL Workbench**:

1. Entra a tu **Hostinger hPanel**.
2. Ve a **Bases de datos** ➔ **MySQL Remoto** (Remote MySQL).
3. En **IP (IPv4 o IPv6)** escribe tu dirección IP pública (o escribe `%` para permitir tu conexión desde cualquier lugar) y selecciona la base de datos `visortv`. Haz clic en **Crear**.
4. Ahora abre **MySQL Workbench** en tu computadora.
5. Haz clic en el botón `+` junto a **MySQL Connections**:
   - **Connection Name**: `Visor TV - Hostinger Producción`
   - **Hostname**: La IP del servidor de Hostinger (te la muestra en la pantalla de MySQL Remoto, o el dominio `sql.tudominio.com`).
   - **Port**: `3306`
   - **Username**: El usuario de MySQL creado en Hostinger (ej. `u123456_admin`).
   - **Password**: Haz clic en *Store in Vault...* y escribe tu contraseña de Hostinger.
6. Haz clic en **Test Connection** y luego en **OK**.
7. ¡Listo! Al hacer doble clic en esa conexión dentro de MySQL Workbench, verás en vivo todas las tablas de producción y cada usuario o sede registrada en tiempo real.

