# Control Gasolina

Aplicación web ligera para registrar repostajes, controlar vehículos y consultar el coste real de cada trayecto.

## Funciones

- Dashboard con gasto, kilómetros y consumo medio.
- Historial con filtros, paginación y gráficas.
- Cálculo automático de litros, importe y consumo.
- Gestión de varios vehículos.
- Mantenimientos por fecha o kilómetros.
- Exportación del historial a PDF.
- Diseño responsive para ordenador, tablet y móvil.
- Protección CSRF y autenticación opcional.

## Requisitos

- PHP 7.4 o superior.
- MySQL o MariaDB.
- Apache, XAMPP, WAMP o un hosting compatible.

## Instalación local

1. Clona el repositorio en `htdocs`.
2. Crea una base de datos vacía.
3. Importa `sql/gasolinaBD.sql`.
4. Configura las credenciales en `.env` o `app/env.php`.
5. Abre `http://localhost/Gasolina/`.

Ejemplo mínimo de `.env`:

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=gasolina
```

`app/env.php` y `.env` contienen configuración privada y no deben subirse al repositorio.

## Estructura

```text
app/          Configuración y conexión a la base de datos
css/          Estilos de la aplicación
img/          Imágenes e iconos
includes/     Componentes compartidos, como la navegación
js/           Scripts y gráficas
pages/        Historial, formularios, vehículos y mantenimientos
sql/          Esquema y migraciones de base de datos
index.php     Dashboard principal
```

## Despliegue

1. Haz una copia de seguridad de la base de datos y de los archivos actuales.
2. Sube el proyecto al directorio `htdocs` del hosting.
3. Configura `app/env.php` con las credenciales del servidor.
4. Comprueba el dashboard, el historial y la creación de un repostaje.

No subas credenciales, bases de datos exportadas, logs ni archivos de diagnóstico.

## Desarrollo

- Mantén las credenciales fuera de Git.
- Usa commits pequeños y descriptivos.
- Prueba localmente antes de publicar.
- Ejecuta las migraciones SQL una sola vez y conserva una copia previa.

## Próximos pasos

- Filtros avanzados y duplicación del último repostaje.
- Autonomía estimada por vehículo.
- Revisión adicional de configuración, permisos y seguridad.

## Licencia

MIT. Consulta `LICENSE` para más información.
