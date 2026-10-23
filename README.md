# Control Gasolina

Aplicación web para gestionar repostajes, vehículos y mantenimiento, con indicadores de consumo y costes. Funciona en local o en hosting PHP/MySQL y está adaptada a ordenador, tablet y móvil.

## Funcionalidades

- Panel de control con gasto, kilómetros y consumo.
- Historial paginado con filtros, gráficas y exportación PDF.
- Cálculo automático de litros, importe y consumo por vehículo.
- Gestión de vehículos y mantenimientos por fecha o kilometraje.
- Protección CSRF, sesiones seguras y autenticación opcional.

## Requisitos

- PHP 8.2 o superior recomendado.
- MySQL o MariaDB.
- Apache, XAMPP, WAMP o hosting PHP compatible.

## Instalación

1. Clona el repositorio dentro de `htdocs`.
2. Crea la base de datos e importa el esquema disponible en `sql/`.
3. Copia `app/env.example.php` como `app/env.php` y configura las credenciales, o utiliza variables de entorno.
4. Abre `http://localhost/Gasolina/`.

Ejemplo de configuración local:

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=gasolina
```

`app/env.php` y `.env` son configuraciones privadas: pueden existir en el servidor, pero nunca deben publicarse en GitHub. No subas copias de bases de datos, logs ni archivos de diagnóstico.

Para proteger una instalación pública, activa la autenticación en la configuración de producción:

```php
'AUTH_ENABLED' => '1',
'AUTH_USER' => 'tu_usuario',
'AUTH_PASSWORD_HASH' => 'hash_generado_con_password_hash',
```

Genera el hash sin guardar la contraseña en el código:

```bash
php -r "echo password_hash('TU_CONTRASEÑA', PASSWORD_DEFAULT), PHP_EOL;"
```

## Despliegue

Antes de actualizar, realiza una copia de seguridad de los archivos y de la base de datos. Después, sube el código, conserva la configuración privada del servidor y verifica el inicio, el historial, los vehículos y el registro de repostajes.

## Estructura

```text
app/          Configuración y conexión
css/          Estilos
includes/     Componentes compartidos
js/           JavaScript y gráficas
pages/        Módulos de la aplicación
sql/          Esquema y migraciones
index.php     Panel principal
```

## Desarrollo

Prueba los cambios en local antes de publicarlos y utiliza commits pequeños y descriptivos. Las migraciones SQL deben ejecutarse una sola vez y siempre después de realizar una copia de seguridad.

## Licencia

MIT. Consulta [`LICENSE`](LICENSE).
