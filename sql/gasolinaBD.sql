CREATE TABLE consumos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  km_actuales INT NOT NULL,
  litros DECIMAL(6,2) NOT NULL,
  precio_litro DECIMAL(5,3) NOT NULL,
  importe_total DECIMAL(7,2) GENERATED ALWAYS AS (litros * precio_litro) STORED,
  km_recorridos INT NULL,
  consumo_100km DECIMAL(6,2) NULL
);
