-- Guarda la gasolinera o localidad de cada repostaje para permitir búsquedas.
-- Ejecutar una sola vez sobre bases de datos existentes.

ALTER TABLE consumos
  ADD COLUMN IF NOT EXISTS gasolinera VARCHAR(120) NULL AFTER fecha;
