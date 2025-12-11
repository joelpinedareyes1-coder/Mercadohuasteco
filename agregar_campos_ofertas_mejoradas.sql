-- ============================================
-- MEJORAS AL SISTEMA DE OFERTAS PREMIUM
-- Agrega nuevos campos para ofertas más completas
-- ============================================

-- Agregar nuevos campos a la tabla cupones_ofertas
ALTER TABLE cupones_ofertas 
ADD COLUMN IF NOT EXISTS porcentaje_descuento INT DEFAULT NULL COMMENT 'Porcentaje de descuento (ej: 20 para 20%)',
ADD COLUMN IF NOT EXISTS link_producto VARCHAR(500) DEFAULT NULL COMMENT 'URL del producto en oferta',
ADD COLUMN IF NOT EXISTS imagen_oferta VARCHAR(500) DEFAULT NULL COMMENT 'Imagen promocional de la oferta',
ADD COLUMN IF NOT EXISTS categoria_oferta ENUM('descuento', '2x1', '3x2', 'envio_gratis', 'regalo', 'temporada', 'otro') DEFAULT 'descuento' COMMENT 'Tipo de oferta',
ADD COLUMN IF NOT EXISTS vistas INT DEFAULT 0 COMMENT 'Contador de vistas de la oferta',
ADD COLUMN IF NOT EXISTS clics INT DEFAULT 0 COMMENT 'Contador de clics al link',
ADD COLUMN IF NOT EXISTS codigo_cupon VARCHAR(50) DEFAULT NULL COMMENT 'Código de cupón para usar en la tienda',
ADD COLUMN IF NOT EXISTS stock_limitado INT DEFAULT NULL COMMENT 'Cantidad limitada de cupones disponibles',
ADD COLUMN IF NOT EXISTS stock_usado INT DEFAULT 0 COMMENT 'Cantidad de cupones ya usados',
ADD COLUMN IF NOT EXISTS destacado TINYINT(1) DEFAULT 0 COMMENT 'Oferta destacada (aparece primero)',
ADD COLUMN IF NOT EXISTS color_badge VARCHAR(7) DEFAULT '#FFD700' COMMENT 'Color personalizado para el badge',
ADD COLUMN IF NOT EXISTS terminos_condiciones TEXT DEFAULT NULL COMMENT 'Términos y condiciones de la oferta';

-- Crear índices para mejor rendimiento
CREATE INDEX IF NOT EXISTS idx_categoria ON cupones_ofertas(categoria_oferta);
CREATE INDEX IF NOT EXISTS idx_porcentaje ON cupones_ofertas(porcentaje_descuento);
CREATE INDEX IF NOT EXISTS idx_destacado ON cupones_ofertas(destacado);
CREATE INDEX IF NOT EXISTS idx_fecha_expiracion ON cupones_ofertas(fecha_expiracion);
