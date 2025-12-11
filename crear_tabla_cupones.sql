-- ============================================
-- TABLA DE CUPONES Y OFERTAS (PREMIUM)
-- Sistema para que vendedores Premium creen ofertas
-- ============================================

CREATE TABLE IF NOT EXISTS cupones_ofertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_tienda INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    fecha_inicio DATE DEFAULT CURRENT_DATE,
    fecha_expiracion DATE NOT NULL,
    estado ENUM('activo', 'expirado', 'pausado') DEFAULT 'activo',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tienda) REFERENCES tiendas(id) ON DELETE CASCADE,
    INDEX idx_estado (estado),
    INDEX idx_tienda (id_tienda),
    INDEX idx_fecha_expiracion (fecha_expiracion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
