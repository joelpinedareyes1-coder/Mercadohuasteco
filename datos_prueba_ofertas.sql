-- ============================================
-- DATOS DE PRUEBA - Sistema de Ofertas Públicas
-- Inserta ofertas de ejemplo para probar el sistema
-- ============================================

-- NOTA: Asegúrate de tener al menos una tienda Premium antes de ejecutar esto
-- Para hacer Premium a un usuario: UPDATE usuarios SET es_premium = 1 WHERE id = [ID];

-- ============================================
-- EJEMPLO 1: Ofertas para diferentes categorías
-- ============================================

-- Oferta de Comida (Asume que tienda ID 1 existe y es Premium)
INSERT INTO cupones_ofertas (id_tienda, titulo, descripcion, fecha_inicio, fecha_expiracion, estado)
VALUES 
(1, '2x1 en Hamburguesas', 'Compra una hamburguesa y llévate otra gratis. Válido de lunes a viernes.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'activo');

-- Oferta de Servicios (Asume que tienda ID 2 existe y es Premium)
INSERT INTO cupones_ofertas (id_tienda, titulo, descripcion, fecha_inicio, fecha_expiracion, estado)
VALUES 
(2, '20% de descuento en cortes', 'Obtén 20% de descuento en cualquier corte de cabello. Menciona este cupón al llegar.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'activo');

-- Oferta de Tecnología (Asume que tienda ID 3 existe y es Premium)
INSERT INTO cupones_ofertas (id_tienda, titulo, descripcion, fecha_inicio, fecha_expiracion, estado)
VALUES 
(3, '15% OFF en accesorios', 'Descuento del 15% en todos los accesorios para celular y laptop.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'activo');

-- ============================================
-- EJEMPLO 2: Oferta urgente (expira pronto)
-- ============================================
INSERT INTO cupones_ofertas (id_tienda, titulo, descripcion, fecha_inicio, fecha_expiracion, estado)
VALUES 
(1, '¡FLASH SALE! 30% OFF', 'Descuento del 30% en todo el menú. ¡Solo por 2 días!', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'activo');

-- ============================================
-- EJEMPLO 3: Oferta sin fecha de expiración
-- ============================================
INSERT INTO cupones_ofertas (id_tienda, titulo, descripcion, fecha_inicio, fecha_expiracion, estado)
VALUES 
(2, 'Descuento estudiantes', '10% de descuento permanente para estudiantes con credencial vigente.', CURDATE(), NULL, 'activo');

-- ============================================
-- VERIFICAR OFERTAS INSERTADAS
-- ============================================
SELECT 
    c.id,
    c.titulo,
    c.descripcion,
    c.fecha_expiracion,
    t.nombre_tienda,
    t.categoria,
    u.es_premium
FROM cupones_ofertas c
INNER JOIN tiendas t ON c.id_tienda = t.id
INNER JOIN usuarios u ON t.vendedor_id = u.id
WHERE c.estado = 'activo'
ORDER BY c.id DESC;

-- ============================================
-- SCRIPT PARA HACER PREMIUM A UN USUARIO
-- ============================================
-- Descomenta y ajusta el ID según tu base de datos:
-- UPDATE usuarios SET es_premium = 1 WHERE id = 1;
-- UPDATE usuarios SET es_premium = 1 WHERE id = 2;
-- UPDATE usuarios SET es_premium = 1 WHERE id = 3;

-- ============================================
-- VERIFICAR USUARIOS PREMIUM
-- ============================================
SELECT 
    u.id,
    u.nombre,
    u.email,
    u.rol,
    u.es_premium,
    t.id as tienda_id,
    t.nombre_tienda
FROM usuarios u
LEFT JOIN tiendas t ON u.id = t.vendedor_id
WHERE u.es_premium = 1;
