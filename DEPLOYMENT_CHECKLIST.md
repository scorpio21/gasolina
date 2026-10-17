# Lista de despliegue

## Antes de subir archivos

- [ ] Comprobar que la aplicación local carga correctamente.
- [ ] Ejecutar `php -l` sobre todos los archivos PHP.
- [ ] Hacer una copia de seguridad de la base de datos de producción.
- [ ] Confirmar que `app/env.php` y `.env` no se subirán al repositorio ni al FTP.
- [ ] Preparar `app/env.php` de producción con las credenciales de InfinityFree.
- [ ] Configurar autenticación solo después de probarla localmente.

## Archivos que sí se pueden subir

- Código PHP, CSS y JavaScript.
- `pages/login.php` y `pages/logout.php`.
- `sql/` para disponer de las migraciones, sin ejecutarlas automáticamente.
- `README.md` y esta lista de verificación.

## Archivos que no se deben subir

- `.env`
- `app/env.php` local o cualquier copia con credenciales distintas.
- `test/diagnostico.php`
- Copias `.sql` con datos reales.

## Orden cuando phpMyAdmin esté disponible

1. Exportar la base de datos actual y guardar la copia fuera del servidor.
2. Subir los archivos del proyecto por FTP.
3. Comprobar que la web sigue cargando.
4. Ejecutar `sql/migracion_002_innodb.sql` una sola vez.
5. Comprobar que los registros y las claves foráneas siguen presentes.
6. Ejecutar `sql/migracion_003_indices.sql` solo si los índices no existen.
7. Probar inicio, historial, alta, edición, eliminación, vehículos y mantenimientos.
8. Activar autenticación únicamente después de verificar el acceso.

## Comprobación rápida posterior

- La página principal responde.
- El botón `Inicio` funciona.
- El historial muestra los registros existentes.
- Se puede crear y editar un repostaje.
- La eliminación pide confirmación y elimina solo el registro seleccionado.
- El cambio de vehículo filtra los datos correctamente.
- No aparecen errores PHP ni de conexión.

## Rollback

Si algo falla, conservar los archivos actuales y restaurar la copia de la base de datos. No ejecutar otra migración sobre una instalación parcialmente modificada sin revisar antes el estado de las tablas y los índices.
