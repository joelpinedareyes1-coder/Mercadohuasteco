-- ============================================
-- AGREGAR COLUMNA PARA GOOGLE MAPS (PREMIUM)
-- ============================================

ALTER TABLE tiendas 
ADD COLUMN google_maps_src VARCHAR(500) DEFAULT NULL 
AFTER link_video;

-- Nota: Esta columna almacenará la URL del iframe de Google Maps
-- Solo las tiendas Premium podrán usar esta función
