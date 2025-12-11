-- Script para sincronizar el estado Premium con las tiendas destacadas
-- Ejecutar este script para corregir cualquier desincronización

USE directorio_tiendas;

-- Actualizar todas las tiendas según el estado Premium de sus vendedores
UPDATE tiendas t
INNER JOIN usuarios u ON t.vendedor_id = u.id
SET t.es_destacado = u.es_premium
WHERE u.rol = 'vendedor';

-- Verificar el resultado
SELECT 
    u.id as usuario_id,
    u.nombre as vendedor,
    u.es_premium,
    t.id as tienda_id,
    t.nombre_tienda as tienda,
    t.es_destacado,
    CASE 
        WHEN u.es_premium = t.es_destacado THEN 'Sincronizado'
        ELSE 'Desincronizado'
    END as estado
FROM usuarios u
LEFT JOIN tiendas t ON u.id = t.vendedor_id
WHERE u.rol = 'vendedor'
ORDER BY u.es_premium DESC, u.nombre;
