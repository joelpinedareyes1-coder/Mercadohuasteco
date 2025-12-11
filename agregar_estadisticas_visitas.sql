-- Crear tabla para registrar visitas individuales
CREATE TABLE IF NOT EXISTS visitas_tienda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tienda_id INT NOT NULL,
    fecha_visita DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_visitante VARCHAR(45) NULL,
    user_agent TEXT NULL,
    INDEX idx_tienda_fecha (tienda_id, fecha_visita),
    INDEX idx_fecha (fecha_visita),
    FOREIGN KEY (tienda_id) REFERENCES tiendas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrar datos existentes (crear una visita por cada clic registrado)
-- Esto es opcional, solo si quieres tener datos históricos aproximados
INSERT INTO visitas_tienda (tienda_id, fecha_visita)
SELECT id, DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY)
FROM tiendas
WHERE clics > 0;
