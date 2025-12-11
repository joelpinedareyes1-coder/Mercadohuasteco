-- Agregar columnas de redes sociales a la tabla tiendas
ALTER TABLE tiendas 
ADD COLUMN link_facebook VARCHAR(255) DEFAULT NULL AFTER telefono_wa,
ADD COLUMN link_instagram VARCHAR(255) DEFAULT NULL AFTER link_facebook,
ADD COLUMN link_tiktok VARCHAR(255) DEFAULT NULL AFTER link_instagram;

-- Agregar índices para búsquedas más rápidas
CREATE INDEX idx_link_facebook ON tiendas(link_facebook);
CREATE INDEX idx_link_instagram ON tiendas(link_instagram);
CREATE INDEX idx_link_tiktok ON tiendas(link_tiktok);
