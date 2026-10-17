-- Migración 002: convertir las tablas a InnoDB y activar relaciones.
-- Ejecutar únicamente después de exportar una copia de seguridad.
-- Esta migración está pensada para una base ya existente.

-- Comprobación previa recomendada: ambas consultas deben devolver 0.
SELECT COUNT(*) AS consumos_huerfanos
FROM consumos c
LEFT JOIN vehiculos v ON v.id = c.vehiculo_id
WHERE c.vehiculo_id IS NOT NULL AND v.id IS NULL;

SELECT COUNT(*) AS mantenimientos_huerfanos
FROM mantenimientos m
LEFT JOIN vehiculos v ON v.id = m.vehiculo_id
WHERE v.id IS NULL;

-- Convertir primero la tabla referenciada y después las tablas dependientes.
ALTER TABLE vehiculos ENGINE=InnoDB;
ALTER TABLE consumos ENGINE=InnoDB;
ALTER TABLE mantenimientos ENGINE=InnoDB;

-- Los nombres *_rel evitan colisiones con los índices existentes del volcado.
ALTER TABLE consumos
  ADD CONSTRAINT fk_consumos_vehiculo_rel
  FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id)
  ON UPDATE CASCADE
  ON DELETE SET NULL;

ALTER TABLE mantenimientos
  ADD CONSTRAINT fk_mantenimientos_vehiculo_rel
  FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;
