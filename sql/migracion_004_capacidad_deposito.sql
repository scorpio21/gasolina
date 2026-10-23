-- Añade la capacidad del depósito para calcular la autonomía estimada.
-- Ejecutar una sola vez sobre bases de datos existentes.

ALTER TABLE vehiculos
  ADD COLUMN IF NOT EXISTS capacidad_deposito_l DECIMAL(5,1) NULL AFTER vin;
