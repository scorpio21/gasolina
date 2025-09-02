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

- `index.php` (raíz): dashboard de resumen y últimos repostajes (sin gráficas).
- `app/config.php`: conexión a BD (`getDb()`) y helper `e()` para escapar HTML.
- `includes/navbar.php`: barra de navegación reutilizable.
- `pages/formulario.php`: alta de repostajes (prepared statements).
- `pages/listar.php`: historial de repostajes y gráficas interactivas.
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

---

## Tabla de contenidos

1. Descripción y características
2. Estructura del proyecto
3. Instalación en local
4. Configuración de variables (env.php, .env, SetEnv)
5. Base de datos y modelo de datos
6. Despliegue (paso a paso) en InfinityFree
7. Solución de problemas (Troubleshooting)
8. Preguntas frecuentes (FAQ)
9. Roadmap
10. Licencia

## 1) Descripción y características

Aplicación ligera para registrar repostajes y consultar el historial de consumo.

- Registro de repostajes con fecha, kilómetros, litros, precio/litro.
- Cálculo automático de importe y consumo (L/100km).
- Listado histórico ordenado por fecha.
- Gráficas en `pages/listar.php` con selector de rango (5/10/30) y opción de mostrar tendencia (media móvil SMA3).
- UI con Bootstrap 5 y navegación simple.
- Seguridad básica en `.htaccess` (bloqueo de dotfiles y carpeta `sql/`).

## 2) Estructura del proyecto

```text
├─ app/
│  ├─ config.php         # Conexión BD (getDb), helper env() y e()
│  └─ env.php            # (Producción) Variables de entorno (no se versiona)
├─ css/
│  └─ main.css
├─ img/
├─ includes/
│  └─ navbar.php
├─ js/
│  └─ main.js
├─ pages/
│  ├─ formulario.php     # Alta de repostajes
│  └─ listar.php         # Historial
├─ sql/
│  └─ gasolinaBD.sql     # Esquema base de datos
├─ test/
│  └─ diagnostico.php    # (Debug local) No subir a producción
├─ .htaccess
├─ .gitignore
├─ index.php
└─ README.md
```

## 3) Instalación en local

1. Clona el repositorio en tu servidor local (XAMPP/WAMP).
2. Crea una BD vacía y ejecuta `sql/gasolinaBD.sql`.
3. Configura variables (ver sección 4) usando `.env` o `app/env.php`.
4. Accede a `http://localhost/Gasolina`.

## 4) Configuración de variables (env.php, .env, SetEnv)

`app/config.php` soporta tres métodos. En hosting compartido, se recomienda `app/env.php`:

- app/env.php: establece `putenv`, `$_ENV` y `$_SERVER`.
- .env: fichero KEY=VALUE en la raíz.
- SetEnv en `.htaccess`: puede estar deshabilitado según el hosting.

Consulta los ejemplos completos en “Configuración” y “Despliegue en InfinityFree” más arriba.

## 5) Base de datos y modelo de datos

Tabla `consumos`:

- id (PK, AI)
- fecha (DATE)
- km_actuales (INT)
- litros (DECIMAL 6,2)
- precio_litro (DECIMAL 5,3)
- importe_total (DECIMAL 7,2) Generada SIEMPRE como `litros * precio_litro`
- km_recorridos (INT, NULL)
- consumo_100km (DECIMAL 6,2, NULL)

Importante: no insertar `importe_total` en `INSERT`; la BD lo calcula.

## 6) Despliegue (paso a paso) en InfinityFree

1. Crea BD y credenciales en el panel; importa `sql/gasolinaBD.sql`.
2. Sube el proyecto a `htdocs/`.
3. Crea `htdocs/app/env.php` con tus valores: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`.
4. Verifica que funciona accediendo a `/pages/listar.php` y creando un registro en `/pages/formulario.php`.
5. Mantén `.htaccess` con las reglas de seguridad. Fuerza HTTPS solo si tu dominio ya tiene SSL.

## 7) Solución de problemas (Troubleshooting)

- Error 500 al cargar: en hostings con PHP < 8, asegúrate de usar la versión de `app/config.php` compatible (ya incluida). Verifica que `env.php/.env` están bien ubicados y legibles.
- Variables `DB_*` vacías: algunos hostings restringen `putenv/getenv`. Usa `app/env.php` con asignación también a `$_ENV/$_SERVER` (ejemplo en este README).
- Inserción falla al guardar: no incluyas la columna `importe_total` en el `INSERT` (ya corregido en `pages/formulario.php`).
- Redirección a HTTPS falla: comenta temporalmente la regla en `.htaccess` hasta tener SSL.

## 8) Preguntas frecuentes (FAQ)

- ¿Puedo usar `.env` en producción? Sí, si el hosting permite leerlo (el loader ya está en `app/config.php`).
- ¿Dónde pongo mis credenciales? En `app/env.php` (no se versiona) o en `.env` (ignorarlo en Git). Nunca en código.
- ¿Se puede cambiar el prefijo de tabla? Actualmente no hay prefijo; puedes ajustarlo en el SQL y en el código si lo necesitas.

## 9) Roadmap

- Paginación y filtros en el listado.
- Exportación CSV/Excel.
- Tests automáticos para funciones críticas.
- Mejoras de accesibilidad y validaciones en el formulario.

## 10) Licencia

Este proyecto se distribuye bajo la licencia MIT. Consulta `LICENSE` si se incluye en el repositorio.
