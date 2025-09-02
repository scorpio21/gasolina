# Gasolina

Aplicación sencilla para registrar y consultar consumos de combustible.

## Requisitos

- PHP 7.4+ (compatible con PHP 8.x)
- MySQL/MariaDB
- Servidor local (XAMPP, WAMP, etc.)

## Configuración

1. Configura variables de entorno. Opciones soportadas por `app/config.php` (elige una):

   - Opción A (recomendada en hosting compartido como InfinityFree): `app/env.php` (no subir al repo)

     ```php
     <?php
     $vars = [
         'DB_HOST' => 'localhost',
         'DB_USER' => 'root',
         'DB_PASS' => '',
         'DB_NAME' => 'gasolina',
     ];
     foreach ($vars as $k => $v) {
         putenv("$k=$v");
         $_ENV[$k] = $v;
         $_SERVER[$k] = $v;
     }
     ```

   - Opción B: copia `.env.example` a `.env` y ajusta las variables:

    ```env
    DB_HOST=localhost
    DB_USER=root
    DB_PASS=
    DB_NAME=gasolina
    ```

2. Importa `sql/gasolinaBD.sql` en tu base de datos.
3. Coloca el proyecto en tu servidor (por ejemplo `d:/xampp/htdocs/Gasolina`).

## Estructura

- `index.php` (raíz): dashboard de resumen y últimos repostajes.
- `app/config.php`: conexión a BD (`getDb()`) y helper `e()` para escapar HTML.
- `includes/navbar.php`: barra de navegación reutilizable.
- `pages/formulario.php`: alta de repostajes (prepared statements).
- `pages/listar.php`: historial de repostajes.
- `css/main.css`: estilos globales.
- `js/main.js`: scripts globales.
- `img/`: recursos de imagen.

## Reglas de código aplicadas

- Código y comentarios en español.
- Sin CSS/JS embebido en PHP/HTML; todo en `/css` y `/js`.
- Sin credenciales en el código; usar variables de entorno.
- Escapar salidas con `e()` y `htmlspecialchars` equivalente.
- Mantener funciones y separación de responsabilidades.

## Desarrollo

- Realiza commits frecuentes y claros.
- No subir `.env`, archivos generados ni datos sensibles.

## Despliegue en InfinityFree

1. Crea la base de datos en el panel (host, usuario, contraseña, nombre BD).
2. Importa `sql/gasolinaBD.sql` desde phpMyAdmin.
3. Sube el proyecto a `htdocs/`.
4. Configura variables de entorno (recomendado `app/env.php`):

   - Método recomendado (más fiable en InfinityFree): `htdocs/app/env.php`

     ```php
     <?php
     $vars = [
         'DB_HOST' => 'sqlXXX.infinityfree.com',
         'DB_USER' => 'epiz_XXXXXXXX',
         'DB_PASS' => 'TU_PASSWORD',
         'DB_NAME' => 'epiz_XXXXXXXX_gasolina',
     ];
     foreach ($vars as $k => $v) {
         putenv("$k=$v");
         $_ENV[$k] = $v;
         $_SERVER[$k] = $v;
     }
     ```

   - Alternativas:
     - `.env` en raíz del proyecto.
     - `SetEnv` en `.htaccess` (puede estar deshabilitado según plan/servidor).

5. Mantén activas las reglas de seguridad de `.htaccess`:
    - Sin listado de directorios (`Options -Indexes`)
    - Bloquear dotfiles (`RewriteRule "(^|/)\." - [F]`)
    - Bloquear acceso a `sql/`
    - Forzar HTTPS si tu dominio tiene SSL

Notas importantes:

- No subas credenciales al repositorio. `app/env.php` y `.env` están en `.gitignore`.
- En algunos hostings `putenv()`/`getenv()` pueden estar restringidos. `app/config.php` incluye un helper `env()` que consulta `getenv()`, `$_ENV` y `$_SERVER` para mayor compatibilidad.
- Si tu dominio no tiene SSL, no fuerces HTTPS en `.htaccess` hasta activarlo.
