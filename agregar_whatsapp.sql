-- Agregar columna de WhatsApp a la tabla tiendas
ALTER TABLE tiendas ADD COLUMN telefono_wa VARCHAR(20) DEFAULT NULL AFTER url_tienda;

-- Agregar índice para búsquedas más rápidas
CREATE INDEX idx_telefono_wa ON tiendas(telefono_wa);
