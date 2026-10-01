# 📺 Visor TV Sistemas - API Backend (Laravel 12 + PHP 8.2+)

Backend API de alto rendimiento para el sistema de pantallas y carteleras digitales Visor TV, optimizado para reproducción continua en Smart TVs, sincronización en caliente sin recargas y despliegue rápido en **Hostinger**.

---

## 🚀 Características Principales

- **Arquitectura RESTful & Backward Compatible**: Soporta rutas limpias (`/api/playlist`, `/api/sedes`, `/api/media`, `/api/stats`, `/api/settings`) y compatibilidad con endpoints legados (`/api/playlist.php`, `/api/sedes.php`, etc.).
- **Streaming de Video HTTP 206 (Range Requests)**: Búfer de 256KB por chunks para reproducir videos Full HD y 4K en Smart TVs (LG webOS, Samsung Tizen, Android TV, FireTV) sin saturar la memoria RAM del servidor.
- **Sincronización en Caliente (Hot Reload)**: Endpoint ultra rápido de comprobación de versiones mediante hash MD5 (`/api/playlist?slug={slug}&check_version=1`) que actualiza la programación sin interrumpir el video en reproducción.
- **Base de Datos Flexible**: Funciona nativamente con **MySQL / MariaDB** (ideal para Hostinger) y soporte para SQLite en desarrollo local.
- **Autenticación con Tokens**: Laravel Sanctum / Bearer Tokens para el panel de administración.

---

## 🔑 Credenciales por Defecto

- **Usuario Administrador**: `admin`
- **Contraseña**: `admin123`

---

## 🛠️ Instalación y Uso Local

1. Instalar dependencias PHP:
   ```bash
   composer install
   ```

2. Configurar entorno:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Ejecutar migraciones y datos iniciales (semillas):
   ```bash
   php artisan migrate --seed
   ```

4. Crear enlace simbólico de almacenamiento:
   ```bash
   php artisan storage:link
   ```

5. Iniciar servidor de desarrollo:
   ```bash
   php artisan serve
   ```
   La API quedará disponible en: `http://127.0.0.1:8000`

---

## 🌐 Despliegue en Hostinger (Paso a Paso)

### 1. Subir los archivos vía Git o Administrador de Archivos (hPanel)
- En Hostinger, clona el repositorio o sube la carpeta `visortv_api` a un directorio fuera o dentro de tu cuenta (ejemplo: `/home/u123456789/domains/tudominio.com/visortv_api` o un subdominio `api.tudominio.com`).

### 2. Configurar la Raíz del Sitio (Document Root)
- En el panel de Hostinger, configura el **Document Root** para que apunte a la carpeta:
  ```
  visortv_api/public
  ```
  *(El proyecto incluye `.htaccess` en la raíz como fallback en caso de apuntar a la carpeta principal).*

### 3. Crear Base de Datos MySQL en Hostinger
- Ve a **Bases de Datos MySQL** en hPanel y crea una nueva base de datos y usuario:
  - Nombre BD: `u123456789_visortv`
  - Usuario: `u123456789_admin`
  - Contraseña: `Tu_Password_Seguro`

### 4. Configurar el archivo `.env` en Hostinger
Edita el archivo `.env` en el servidor con los datos de tu Hostinger:
```env
APP_NAME="Visor TV Sistemas"
APP_ENV=production
APP_KEY=base64:... (generada con php artisan key:generate)
APP_DEBUG=false
APP_URL=https://api.tudominio.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u123456789_visortv
DB_USERNAME=u123456789_admin
DB_PASSWORD=Tu_Password_Seguro

FILESYSTEM_DISK=public
```

### 5. Ejecutar Comandos por SSH en Hostinger
Abre la terminal SSH de Hostinger:
```bash
cd domains/tudominio.com/visortv_api

# Instalar dependencias de producción
composer install --no-dev --optimize-autoloader

# Generar llave de aplicación si no existe
php artisan key:generate

# Correr migraciones y crear datos demo (sedes y admin)
php artisan migrate --seed --force

# Crear enlace para subida de videos e imágenes
php artisan storage:link

# Optimizar caché de Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Subida de Videos Grandes (Hostinger PHP Limits)
El proyecto incluye un archivo `.user.ini` y directivas en `.htaccess` para permitir videos de hasta 1GB. Puedes verificarlo en el dashboard de administrador en la sección **Estado del Sistema**.

- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing
Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
