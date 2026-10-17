-- Esquema inicial para una instalación nueva.
-- No elimina ni modifica tablas existentes: usa CREATE TABLE IF NOT EXISTS.
-- Para actualizar una base ya existente, utiliza las migraciones correspondientes.

CREATE TABLE IF NOT EXISTS vehiculos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  marca VARCHAR(50) NOT NULL,
  modelo VARCHAR(80) NOT NULL,
  anio INT NULL,
  combustible VARCHAR(20) NULL,
  matricula VARCHAR(20) NULL,
  vin VARCHAR(32) NULL,
  foto_url VARCHAR(255) NULL,
  capacidad_deposito_l DECIMAL(5,1) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  KEY idx_vehiculos_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS consumos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  km_actuales INT NOT NULL,
  litros DECIMAL(6,2) NOT NULL,
  precio_litro DECIMAL(5,3) NOT NULL,
  importe_total DECIMAL(7,2) GENERATED ALWAYS AS (litros * precio_litro) STORED,
  km_recorridos INT NULL,
  consumo_100km DECIMAL(6,2) NULL,
  vehiculo_id INT NULL,
  lleno TINYINT(1) NOT NULL DEFAULT 1,
  KEY idx_consumos_vehiculo_fecha (vehiculo_id, fecha),
  CONSTRAINT fk_consumos_vehiculo
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  KEY idx_mantenimientos_vehiculo_fecha (vehiculo_id, proxima_fecha_calc),
  CONSTRAINT fk_mantenimientos_vehiculo
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
