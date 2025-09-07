CREATE TABLE consumos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  km_actuales INT NOT NULL,
  litros DECIMAL(6,2) NOT NULL,
  precio_litro DECIMAL(5,3) NOT NULL,
  importe_total DECIMAL(7,2) GENERATED ALWAYS AS (litros * precio_litro) STORED,
  km_recorridos INT NULL,
  consumo_100km DECIMAL(6,2) NULL,
  lleno TINYINT(1) NOT NULL DEFAULT 1
);

-- =============================
-- Multi-vehículo (opcional)
-- =============================
-- Tabla de vehículos
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

-- Añadir columna vehiculo_id a consumos si aún no existe
-- Nota: ejecutar solo una vez en migración. Si tu motor no soporta IF NOT EXISTS en columnas,
-- realiza la comprobación previa manualmente.
ALTER TABLE consumos ADD COLUMN IF NOT EXISTS vehiculo_id INT NULL;

-- Clave foránea (si la columna existe y la FK no está creada)
-- Algunos MariaDB/MySQL no soportan IF NOT EXISTS en ADD CONSTRAINT. Ajusta según tu versión.
ALTER TABLE consumos
  ADD CONSTRAINT fk_consumos_vehiculo
  FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id)
  ON UPDATE CASCADE
  ON DELETE SET NULL;

-- Añadir columna foto_url si migras desde una versión anterior sin este campo
ALTER TABLE vehiculos ADD COLUMN IF NOT EXISTS foto_url VARCHAR(255) NULL;

-- Añadir columna 'lleno' si migras desde una versión anterior sin este campo
ALTER TABLE consumos ADD COLUMN IF NOT EXISTS lleno TINYINT(1) NOT NULL DEFAULT 1;

-- =============================
-- Mantenimientos (por vehículo)
-- =============================
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
