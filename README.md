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
- Historial con paginación (10/20 por página) y orden por fecha ASC/DESC.
- Gráficas en `pages/listar.php` con selector de rango (5/10/30) y opción de mostrar tendencia (media móvil SMA3). Optimizadas para iOS Safari con Chart.js 3.9.1 y creación condicional vía `requestAnimationFrame` solo en iOS.
- Modo oscuro con alternancia desde la barra de navegación; preferencia persistida en `localStorage`.
- Multi‑vehículo con selector en la barra (filtra dashboard, historial y formulario).
- Foto por vehículo (navbar + tarjeta en dashboard) vía campo `vehiculos.foto_url`.
- "Depósito lleno" y cálculo real "lleno a lleno" (badge en historial y cálculo automático al guardar el segundo lleno).
- UI con Bootstrap 5 y navegación simple.
- Seguridad básica en `.htaccess` (bloqueo de dotfiles y carpeta `sql/`).
- Exportación a PDF del historial desde `pages/listar.php` con diseño de cabecera/pie y paginación automática.

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
│  └─ export-pdf.js      # Lógica de exportación del historial a PDF (cliente)
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

### Guía rápida del formulario y cálculo de consumo

- Campos del formulario (`pages/formulario.php`):
  - Fecha: día del repostaje.
  - Km actuales: lectura del cuentakilómetros al repostar.
  - Litros: litros cargados.
  - Precio/L: precio por litro (el importe total se calcula solo como `litros * precio_litro`).
  - Km recorridos (opcional): si lo dejas vacío, se calcula automáticamente como la diferencia con el último "Km actuales" guardado.

- Cálculo de consumo (L/100 km):
  - Si `km_recorridos > 0` y `litros > 0`, entonces `consumo_100km = (litros / km_recorridos) * 100`.
  - Si no hay registros previos o no puede calcularse, `km_recorridos` será 0 y `consumo_100km` quedará en 0 (las gráficas lo mostrarán como hueco si aplica).

### Exportación a PDF del historial

- Ubicación: `pages/listar.php`.
- Botón: “Exportar PDF”.
- Qué exporta: el bloque del historial `#historial-export` (cabecera con logo y fecha, tabla completa).
- Implementación: html2canvas + jsPDF en cliente, con paginación automática a tamaño A4.
- Requisitos: conexión a CDN o disponer de los recursos en caché del navegador.

Nota: En el historial (`pages/listar.php`) puedes cambiar el orden de la columna Fecha haciendo clic en el encabezado y ajustar el tamaño de página en el selector “Por página”.

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
- Error de sintaxis en `manifest.webmanifest` en local: se produce si el servidor redirige HTTP→HTTPS y devuelve HTML para el manifest. Se ha añadido una excepción en `.htaccess` para `localhost` y `127.0.0.1`.
- Iconos del manifest: los iconos referenciados existen en `img/` (`gasolina-180.png`, `gasolina-152.png`). Si cambias los tamaños, actualiza `manifest.webmanifest`.
- Gráficas en iOS Safari no renderizan o aparecen en blanco: aseguramos altura mínima del canvas únicamente en iOS Safari y desactivamos animación allí. En desktop no se fuerza altura y se mantiene aspecto normal. Limpia caché si venías de una versión anterior.

## Modo oscuro

- Activación: usa el botón con icono 🌙/☀️ en la barra de navegación (`includes/navbar.php`).
- Persistencia: la preferencia se guarda en `localStorage` con la clave `tema` (`oscuro`/`claro`).
- Alcance: se aplica la clase `tema-oscuro` al `<body>`, con estilos en `css/main.css`.
- Gráficas: los colores de texto y rejilla de Chart.js se ajustan automáticamente al cambiar de tema, sin recargar la página.

## Multi‑vehículo y foto del vehículo

- Selector: en `includes/navbar.php` aparece un menú "🚘 Vehículo" si existe la tabla `vehiculos`. La selección se guarda en sesión.
- Filtro: `index.php`, `pages/listar.php` y `pages/formulario.php` filtran/guardan por el vehículo activo si existe `consumos.vehiculo_id`.
- Foto: establece `vehiculos.foto_url` con una ruta relativa (p. ej., `img/audi.png`) o una URL absoluta. Se muestra en navbar y como tarjeta en `index.php`.

### Gestión de vehículos (UI)

Desde la versión actual existe una página dedicada para administrar vehículos:

- Ruta: `pages/vehiculos.php`
- Acceso: enlace "🚘 Vehículos" en la barra (`includes/navbar.php`).
- Funcionalidad:
  - Crear, editar y eliminar vehículos.
  - Campos: marca, modelo, año, combustible, matrícula, VIN, foto_url y (opcional) capacidad_deposito_l.
  - Botón "Hacer activo": marca el vehículo como activo (se guarda en sesión) y el resto de páginas filtran por él.
  - Seguridad: no permite eliminar un vehículo si tiene consumos asociados.

## Mantenimientos

- Tabla: `mantenimientos` (por vehículo) con campos: `tipo`, `cada_km`, `cada_meses`, `ultima_fecha`, `ultimo_km`, `proxima_fecha_calc`, `proximo_km_calc`, `nota`.
- Página: `pages/mantenimientos.php` (lista y formulario por vehículo activo).
- Enlace en navbar: "🛠️ Mantenimientos".
- Dashboard: tarjetas "Próximos Mantenimientos" en `index.php` (muestra hasta 3, estados: OK/Pronto/Atrasado). Umbrales por defecto: ≤30 días o ≤500 km para estado "Pronto".

### Cómo registrar un mantenimiento

- Solo por km (ej. aceite motor): rellena "Cada (km)" y "Último km".
- Solo por fecha (ej. ITV): rellena "Cada (meses)" y "Última fecha".
- Ambos (km y meses): rellena ambos pares de campos.

Al guardar, el sistema calcula `proximo_km_calc` y/o `proxima_fecha_calc`.

### SQL de creación (si tu servidor no ejecuta las migraciones del archivo)

```sql
CREATE TABLE IF NOT EXISTS mantenimientos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehiculo_id INT NOT NULL,
  tipo VARCHAR(60) NOT NULL,
  cada_km INT NULL,
  cada_meses INT NULL,
  ultima_fecha DATE NULL,
  ultimo_km INT NULL,
  proxima_fecha_calc DATE NULL,
  proximo_km_calc INT NULL,
  nota TEXT NULL,
  CONSTRAINT fk_mantenimientos_vehiculo FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id)
    ON UPDATE CASCADE ON DELETE CASCADE
);
```
### Migración SQL (multi‑vehículo + foto)

Ejecuta en tu BD (ajusta si tu motor no soporta IF NOT EXISTS):

```sql
CREATE TABLE IF NOT EXISTS vehiculos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  marca VARCHAR(50) NOT NULL,
  modelo VARCHAR(80) NOT NULL,
  anio INT NULL,
  combustible VARCHAR(20) NULL,
  matricula VARCHAR(20) NULL,
  vin VARCHAR(32) NULL,
  foto_url VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1
);

ALTER TABLE consumos ADD COLUMN IF NOT EXISTS vehiculo_id INT NULL;
ALTER TABLE consumos
  ADD CONSTRAINT fk_consumos_vehiculo
  FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id)
  ON UPDATE CASCADE
  ON DELETE SET NULL;

-- Si tu versión no acepta IF NOT EXISTS, usa ADD COLUMN simple:
-- ALTER TABLE vehiculos ADD COLUMN foto_url VARCHAR(255) NULL;
```

Asocia tus registros existentes a un vehículo (ejemplo Audi Q3):

```sql
INSERT INTO vehiculos (marca, modelo, anio, combustible, matricula, vin, foto_url, activo)
VALUES ('Audi', 'Q3 1.4 TFSI S tronic Design', 2018, 'Gasolina 95/98', NULL, NULL, 'img/audi.png', 1);

UPDATE consumos SET vehiculo_id = (SELECT id FROM vehiculos WHERE marca='Audi' AND modelo LIKE 'Q3%' ORDER BY id DESC LIMIT 1)
WHERE vehiculo_id IS NULL;
```

## "Depósito lleno" y cálculo "lleno a lleno"

- Formulario: `pages/formulario.php` incluye un checkbox "Depósito lleno" (por defecto activado). Si haces un llenado parcial, desmárcalo.
- Historial: `pages/listar.php` muestra una insignia "Lleno" junto a la fecha para los registros con `lleno=1`.
- Cálculo real: cuando guardas un registro marcado como "lleno", la app busca el anterior "lleno" del mismo vehículo y calcula el consumo real del tramo entre ambos:
  - `consumo_100km(real) = (suma de litros entre llenos) / (kmDelta entre llenos) × 100`
  - El valor se guarda en `consumo_100km` del segundo lleno (registro actual), sustituyendo el estimado.

### Migración SQL (campo "lleno")

```sql
-- Si no existe la columna 'lleno' en consumos, añádela:
ALTER TABLE consumos ADD COLUMN lleno TINYINT(1) NOT NULL DEFAULT 1;

-- Marca tu punto de partida como lleno (ejemplo de fecha):
UPDATE consumos SET lleno = 1 WHERE fecha = '2025-09-02';
```

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
