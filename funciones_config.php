<?php
// Funciones para manejar la configuración del sitio

/**
 * Obtiene un valor de configuración específico
 */
function obtenerConfiguracion($pdo, $setting_nombre, $valor_por_defecto = '') {
    try {
        $stmt = $pdo->prepare("SELECT setting_valor FROM configuracion WHERE setting_nombre = ?");
        $stmt->execute([$setting_nombre]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['setting_valor'] : $valor_por_defecto;
    } catch (Exception $e) {
        return $valor_por_defecto;
    }
}

/**
 * Obtiene todas las configuraciones como array asociativo
 */
function obtenerTodasConfiguraciones($pdo) {
    try {
        $stmt = $pdo->query("SELECT setting_nombre, setting_valor FROM configuracion");
        $config = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['setting_nombre']] = $row['setting_valor'];
        }
        return $config;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Actualiza un valor de configuración específico
 */
function actualizarConfiguracion($pdo, $setting_nombre, $setting_valor) {
    try {
        $stmt = $pdo->prepare("UPDATE configuracion SET setting_valor = ?, updated_at = NOW() WHERE setting_nombre = ?");
        return $stmt->execute([$setting_valor, $setting_nombre]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Verifica si las reseñas se auto-aprueban
 */
function autoAprobarReseñas($pdo) {
    return obtenerConfiguracion($pdo, 'auto_approve_reviews', '1') === '1';
}

/**
 * Obtiene el nombre del sitio
 */
function obtenerNombreSitio($pdo) {
    return obtenerConfiguracion($pdo, 'site_name', 'Directorio Uni');
}

/**
 * Obtiene el mensaje de bienvenida
 */
function obtenerMensajeBienvenida($pdo) {
    return obtenerConfiguracion($pdo, 'site_welcome_message', 'Bienvenido a nuestro directorio');
}

/**
 * Obtiene el límite máximo de fotos por tienda
 */
function obtenerMaxFotosPorTienda($pdo) {
    return (int)obtenerConfiguracion($pdo, 'max_photos_per_store', '10');
}

/**
 * Obtiene el límite de tiendas destacadas
 */
function obtenerLimiteTiendasDestacadas($pdo) {
    return (int)obtenerConfiguracion($pdo, 'featured_stores_limit', '6');
}

/**
 * Verifica si un usuario tiene Premium activo
 * @param string|null $fecha_expiracion Fecha de expiración del premium
 * @return bool True si el premium está activo, false si no
 */
function esPremiumActivo($fecha_expiracion) {
    if (empty($fecha_expiracion)) {
        return false;
    }
    
    $fecha_actual = new DateTime();
    $fecha_exp = new DateTime($fecha_expiracion);
    
    return $fecha_exp > $fecha_actual;
}

/**
 * Obtiene información completa del usuario incluyendo estado Premium
 * @param PDO $pdo Conexión a la base de datos
 * @param int $user_id ID del usuario
 * @return array|false Array con información del usuario o false si no existe
 */
function obtenerInfoUsuario($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener info de usuario: " . $e->getMessage());
        return false;
    }
}
?>