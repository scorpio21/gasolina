# Gasolina

Aplicación sencilla para registrar y consultar consumos de combustible.

## Requisitos

- PHP 8.x
- MySQL/MariaDB
- Servidor local (XAMPP, WAMP, etc.)

## Configuración

1. Copia `.env.example` a `.env` y ajusta las variables:

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
