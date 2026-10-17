-- Migración 003: índices para las consultas habituales.
-- Ejecutar una sola vez sobre una base ya existente, después de hacer una copia.
-- Comprobar antes con SHOW INDEX para evitar duplicar índices si ya existen.

ALTER TABLE vehiculos
  ADD INDEX idx_vehiculos_activo (activo);

ALTER TABLE consumos
  ADD INDEX idx_consumos_vehiculo_fecha (vehiculo_id, fecha);

ALTER TABLE mantenimientos
  ADD INDEX idx_mantenimientos_vehiculo_fecha (vehiculo_id, proxima_fecha_calc);
