-- Script para agregar el sistema de membresías Premium
-- Ejecutar este script en tu base de datos

USE directorio_tiendas;

-- Agregar columna es_premium a la tabla usuarios
ALTER TABLE usuarios 
ADD COLUMN es_premium TINYINT(1) DEFAULT 0 AFTER activo;

-- Crear índice para optimizar consultas de usuarios premium
CREATE INDEX idx_es_premium ON usuarios(es_premium);

-- Actualizar estadísticas de la tabla
ANALYZE TABLE usuarios;

-- Verificar que la columna se agregó correctamente
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    COLUMN_DEFAULT, 
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'directorio_tiendas' 
  AND TABLE_NAME = 'usuarios' 
  AND COLUMN_NAME = 'es_premium';
