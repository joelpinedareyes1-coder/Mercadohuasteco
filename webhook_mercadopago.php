<?php
require_once 'config.php';
require_once 'funciones_config.php';

// Configuración de Mercado Pago
// IMPORTANTE: Reemplaza esto con tu Access Token real de Mercado Pago
define('MP_ACCESS_TOKEN', 'TU_ACCESS_TOKEN_DE_MERCADO_PAGO');

// Log de inicio
error_log("=== WEBHOOK MERCADO PAGO RECIBIDO ===");

// Obtener datos del webhook
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log de datos recibidos
error_log("Tipo de notificación: " . ($data['type'] ?? 'desconocido'));
error_log("Datos completos: " . $input);

// Registrar el webhook recibido (opcional, para debugging)
try {
    $stmt = $pdo->prepare("INSERT INTO webhook_logs (tipo, payment_id, datos_recibidos, fecha_recepcion) VALUES (?, ?, ?, NOW())");
    $stmt->execute([
        $data['type'] ?? 'unknown',
        $data['data']['id'] ?? null,
        $input
    ]);
    $webhook_log_id = $pdo->lastInsertId();
} catch (PDOException $e) {
    // Si falla el log, continuamos igual (no es crítico)
    error_log("Error al guardar webhook log: " . $e->getMessage());
    $webhook_log_id = null;
}

// ============================================
// PROCESAR PAGOS (Lo más importante)
// ============================================
if (isset($data['type']) && $data['type'] === 'payment') {
    $payment_id = $data['data']['id'];
    
    error_log("Procesando pago ID: $payment_id");
    
    // Consultar información del pago a Mercado Pago
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/$payment_id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . MP_ACCESS_TOKEN
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $payment_info = json_decode($response, true);
        
        error_log("Estado del pago: " . ($payment_info['status'] ?? 'desconocido'));
        
        // ¡MAGIA! Verificar si el pago fue aprobado
        if (isset($payment_info['status']) && $payment_info['status'] === 'approved') {
            
            // Obtener el ID del usuario que guardamos en external_reference
            $usuario_id = $payment_info['external_reference'] ?? null;
            
            if (!$usuario_id) {
                error_log("ERROR: No se encontró external_reference en el pago");
                http_response_code(200);
                exit();
            }
            
            error_log("Usuario que pagó: $usuario_id");
            
            $preapproval_id = $payment_info['preapproval_id'] ?? null;
            
            // Buscar la suscripción asociada (si existe)
            $suscripcion_id = null;
            if ($preapproval_id) {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM suscripciones_premium WHERE preapproval_id = ?");
                    $stmt->execute([$preapproval_id]);
                    $suscripcion = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($suscripcion) {
                        $suscripcion_id = $suscripcion['id'];
                        error_log("Suscripción encontrada: $suscripcion_id");
                    }
                } catch (PDOException $e) {
                    error_log("Error al buscar suscripción: " . $e->getMessage());
                }
            }
            
            // Registrar el pago individual en nuestra base de datos
            try {
                $stmt = $pdo->prepare("INSERT INTO pagos_suscripcion (suscripcion_id, usuario_id, payment_id, status, monto, moneda, datos_pago, fecha_pago) VALUES (?, ?, ?, 'approved', ?, ?, ?, NOW())");
                $stmt->execute([
                    $suscripcion_id,
                    $usuario_id,
                    $payment_id,
                    $payment_info['transaction_amount'] ?? 0,
                    $payment_info['currency_id'] ?? 'MXN',
                    json_encode($payment_info)
                ]);
                error_log("Pago registrado en base de datos");
            } catch (PDOException $e) {
                error_log("Error al registrar pago: " . $e->getMessage());
            }
            
            // ============================================
            // ACTIVAR O EXTENDER PREMIUM POR 30 DÍAS
            // ============================================
            try {
                // Verificar si ya tiene Premium activo
                $stmt = $pdo->prepare("SELECT fecha_expiracion_premium FROM usuarios WHERE id = ?");
                $stmt->execute([$usuario_id]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$usuario) {
                    error_log("ERROR: Usuario $usuario_id no encontrado");
                    http_response_code(200);
                    exit();
                }
                
                // Calcular nueva fecha de expiración
                if ($usuario['fecha_expiracion_premium'] && strtotime($usuario['fecha_expiracion_premium']) > time()) {
                    // Ya tiene Premium activo - extender desde la fecha actual de expiración
                    $fecha_base = new DateTime($usuario['fecha_expiracion_premium']);
                    $fecha_base->modify('+30 days');
                    $fecha_expiracion = $fecha_base->format('Y-m-d H:i:s');
                    error_log("Extendiendo Premium existente");
                } else {
                    // No tiene Premium o ya expiró - activar desde ahora
                    $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+30 days'));
                    error_log("Activando Premium nuevo");
                }
                
                // ¡ACTUALIZAR EL USUARIO A PREMIUM!
                $stmt = $pdo->prepare("UPDATE usuarios SET es_premium = 1, fecha_expiracion_premium = ?, fecha_ultimo_pago = NOW() WHERE id = ?");
                $stmt->execute([$fecha_expiracion, $usuario_id]);
                
                // Si el usuario es vendedor, actualizar su tienda también
                $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
                $stmt->execute([$usuario_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user_data && $user_data['rol'] === 'vendedor') {
                    $stmt = $pdo->prepare("UPDATE tiendas SET es_premium = 1, fecha_expiracion_premium = ? WHERE vendedor_id = ?");
                    $stmt->execute([$fecha_expiracion, $usuario_id]);
                    error_log("Tienda actualizada a Premium");
                }
                
                // Marcar el webhook como procesado
                if ($webhook_log_id) {
                    $stmt = $pdo->prepare("UPDATE webhook_logs SET procesado = 1, fecha_procesado = NOW() WHERE id = ?");
                    $stmt->execute([$webhook_log_id]);
                }
                
                error_log("✅ ÉXITO: Premium activado/extendido para usuario $usuario_id hasta $fecha_expiracion");
                
            } catch (PDOException $e) {
                error_log("❌ ERROR al activar Premium: " . $e->getMessage());
            }
            
        } else {
            error_log("Pago no aprobado o estado diferente: " . ($payment_info['status'] ?? 'desconocido'));
        }
    } else {
        error_log("Error al consultar pago a Mercado Pago. HTTP Code: $http_code");
    }
}

// Procesar notificaciones de SUSCRIPCIÓN (autorización inicial)
if (isset($data['type']) && $data['type'] === 'subscription_preapproval') {
    $preapproval_id = $data['data']['id'];
    
    // Consultar información de la suscripción a Mercado Pago
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/preapproval/$preapproval_id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . MP_ACCESS_TOKEN
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $subscription_info = json_decode($response, true);
        
        // Actualizar el estado de la suscripción
        try {
            $stmt = $pdo->prepare("UPDATE suscripciones_premium SET status = ?, fecha_inicio = ?, datos_suscripcion = ? WHERE preapproval_id = ?");
            $stmt->execute([
                $subscription_info['status'],
                $subscription_info['date_created'] ?? date('Y-m-d H:i:s'),
                json_encode($subscription_info),
                $preapproval_id
            ]);
            
            // Marcar el webhook como procesado
            $stmt = $pdo->prepare("UPDATE webhook_logs SET procesado = 1 WHERE id = ?");
            $stmt->execute([$webhook_log_id]);
            
            error_log("Suscripción $preapproval_id actualizada a estado: " . $subscription_info['status']);
        } catch (PDOException $e) {
            error_log("Error al actualizar suscripción: " . $e->getMessage());
        }
    }
}

// Responder con 200 OK
http_response_code(200);
echo json_encode(['status' => 'ok']);
?>
