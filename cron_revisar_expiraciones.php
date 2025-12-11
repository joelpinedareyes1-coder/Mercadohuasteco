<?php
/**
 * Script de Mantenimiento: Revisar Expiraciones de Premium
 * 
 * Este script debe ejecutarse 1 vez al día mediante Cron Job
 * Desactiva automáticamente el Premium de usuarios cuya suscripción expiró
 * 
 * Cron Job recomendado (ejecutar a las 2:00 AM):
 * 0 2 * * * /usr/bin/php /ruta/completa/cron_revisar_expiraciones.php
 */

require_once 'config.php';

// Log de inicio
error_log("=== CRON: Revisando expiraciones de Premium ===");
$fecha_ejecucion = date('Y-m-d H:i:s');

try {
    // Buscar usuarios premium cuya fecha de expiración ya pasó
    $stmt = $pdo->prepare("
        SELECT id, nombre, email, fecha_expiracion_premium 
        FROM usuarios 
        WHERE es_premium = 1 
        AND fecha_expiracion_premium < NOW()
    ");
    $stmt->execute();
    $usuarios_expirados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_expirados = count($usuarios_expirados);
    
    if ($total_expirados > 0) {
        error_log("Encontrados $total_expirados usuarios con Premium expirado");
        
        // Desactivar Premium de usuarios expirados
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET es_premium = 0 
            WHERE es_premium = 1 
            AND fecha_expiracion_premium < NOW()
        ");
        $stmt->execute();
        $usuarios_actualizados = $stmt->rowCount();
        
        // Desactivar Premium de tiendas asociadas
        $stmt = $pdo->prepare("
            UPDATE tiendas t
            INNER JOIN usuarios u ON t.vendedor_id = u.id
            SET t.es_premium = 0
            WHERE u.es_premium = 0
            AND t.es_premium = 1
        ");
        $stmt->execute();
        $tiendas_actualizadas = $stmt->rowCount();
        
        // Log detallado de cada usuario
        foreach ($usuarios_expirados as $usuario) {
            error_log("  - Usuario ID {$usuario['id']} ({$usuario['nombre']}) - Expiró: {$usuario['fecha_expiracion_premium']}");
            
            // Opcional: Enviar email de notificación
            // mail(
            //     $usuario['email'],
            //     'Tu suscripción Premium ha expirado',
            //     "Hola {$usuario['nombre']},\n\nTu suscripción Premium ha expirado...",
            //     'From: noreply@mercadohuasteco.com'
            // );
        }
        
        error_log("✅ Premium desactivado para $usuarios_actualizados usuarios y $tiendas_actualizadas tiendas");
        
    } else {
        error_log("✅ No hay usuarios con Premium expirado");
    }
    
    // Verificar suscripciones canceladas sin procesar
    $stmt = $pdo->prepare("
        SELECT s.id, s.usuario_id, s.status, u.nombre, u.fecha_expiracion_premium
        FROM suscripciones_premium s
        INNER JOIN usuarios u ON s.usuario_id = u.id
        WHERE s.status = 'cancelled'
        AND u.es_premium = 1
        AND u.fecha_expiracion_premium < NOW()
    ");
    $stmt->execute();
    $suscripciones_canceladas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($suscripciones_canceladas) > 0) {
        error_log("Encontradas " . count($suscripciones_canceladas) . " suscripciones canceladas con Premium expirado");
        
        foreach ($suscripciones_canceladas as $sub) {
            // Desactivar Premium
            $stmt = $pdo->prepare("UPDATE usuarios SET es_premium = 0 WHERE id = ?");
            $stmt->execute([$sub['usuario_id']]);
            
            error_log("  - Suscripción ID {$sub['id']} - Usuario: {$sub['nombre']} - Desactivado");
        }
    }
    
    // Estadísticas generales
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE es_premium = 1");
    $total_premium_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    error_log("📊 Estadísticas: $total_premium_activos usuarios Premium activos");
    error_log("=== CRON: Finalizado correctamente ===");
    
    // Registrar ejecución del cron (opcional)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO cron_logs (script, fecha_ejecucion, usuarios_procesados, resultado) 
            VALUES ('revisar_expiraciones', ?, ?, 'success')
        ");
        $stmt->execute([$fecha_ejecucion, $total_expirados]);
    } catch (PDOException $e) {
        // Si la tabla no existe, no es crítico
        error_log("Nota: No se pudo registrar en cron_logs (tabla opcional)");
    }
    
} catch (PDOException $e) {
    error_log("❌ ERROR en cron de expiraciones: " . $e->getMessage());
    
    // Registrar error (opcional)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO cron_logs (script, fecha_ejecucion, usuarios_procesados, resultado, error_mensaje) 
            VALUES ('revisar_expiraciones', ?, 0, 'error', ?)
        ");
        $stmt->execute([$fecha_ejecucion, $e->getMessage()]);
    } catch (PDOException $e2) {
        // Ignorar si la tabla no existe
    }
    
    exit(1);
}

exit(0);
?>
