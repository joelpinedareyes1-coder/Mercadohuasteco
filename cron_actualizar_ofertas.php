<?php
/**
 * Script CRON para actualizar ofertas expiradas
 * Ejecutar diariamente: 0 0 * * * /usr/bin/php /ruta/a/cron_actualizar_ofertas.php
 */

require_once 'config.php';

try {
    // Actualizar ofertas expiradas
    $stmt = $pdo->prepare("
        UPDATE cupones_ofertas 
        SET estado = 'expirado' 
        WHERE fecha_expiracion < CURDATE() 
        AND estado = 'activo'
    ");
    $stmt->execute();
    
    $ofertas_actualizadas = $stmt->rowCount();
    
    // Log del resultado
    $log_message = date('Y-m-d H:i:s') . " - Ofertas actualizadas: $ofertas_actualizadas\n";
    file_put_contents('logs/cron_ofertas.log', $log_message, FILE_APPEND);
    
    echo "Ofertas actualizadas: $ofertas_actualizadas\n";
    
} catch(PDOException $e) {
    $error_message = date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n";
    file_put_contents('logs/cron_ofertas.log', $error_message, FILE_APPEND);
    echo "Error: " . $e->getMessage() . "\n";
}
?>
