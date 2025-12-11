-- Agregar columna de video a la tabla tiendas
ALTER TABLE tiendas ADD COLUMN link_video VARCHAR(500) DEFAULT NULL AFTER logo;

-- Agregar índice para búsquedas más rápidas
CREATE INDEX idx_link_video ON tiendas(link_video);
