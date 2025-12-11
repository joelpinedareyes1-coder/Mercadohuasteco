-- ============================================
-- TABLA DE REPORTES DE TIENDAS
-- Sistema de moderación para reportar tiendas
-- ============================================

CREATE TABLE IF NOT EXISTS reportes_tienda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_tienda INT NOT NULL,
    id_usuario_reporta INT NULL,
    motivo TEXT NOT NULL,
    estado ENUM('pendiente', 'resuelto') DEFAULT 'pendiente',
    fecha_reporte DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion DATETIME NULL,
    notas_admin TEXT NULL,
    FOREIGN KEY (id_tienda) REFERENCES tiendas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario_reporta) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha_reporte),
    INDEX idx_tienda (id_tienda)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
