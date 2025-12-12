<?php
require_once 'config.php';
require_once 'funciones_config.php';

// 1. Seguridad: Obtener credenciales desde Railway
$mp_access_token = getenv('MP_ACCESS_TOKEN');

if (!$mp_access_token) {
    error_log("CRITICAL ERROR: No MP_ACCESS_TOKEN found in environment variables.");
    http_response_code(500);
    exit();
}

define('MP_ACCESS_TOKEN', $mp_access_token);

// Log de inicio
error_log("=== WEBHOOK MERCADO PAGO RECIBIDO ===");

// Obtener datos del webhook
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Registrar el webhook recibido (Debugging)
try {
    $stmt = $pdo->prepare("INSERT INTO webhook_logs (tipo, payment_id, datos_recibidos, fecha_recepcion) VALUES (?, ?, ?, NOW())");
    $stmt->execute([
        $data['type'] ?? ($data['topic'] ?? 'unknown'), // Mercado Pago a veces usa 'topic' o 'type'
        $data['data']['id'] ?? ($data['id'] ?? null),
        $input
    ]);
    $webhook_log_id = $pdo->lastInsertId();
} catch (PDOException $e) {
    error_log("Error al guardar webhook log: " . $e->getMessage());
    $webhook_log_id = null;
}

// ============================================
// PROCESAR PAGOS (Mensualidad cobrada)
// ============================================
// Nota: Mercado Pago envía 'payment' o 'topic: payment'
if ( (isset($data['type']) && $data['type'] === 'payment') || (isset($data['topic']) && $data['topic'] === 'payment') ) {
    
    $payment_id = $data['data']['id'] ?? $data['id'];
    error_log("Procesando pago ID: $payment_id");
    
    // Consultar API de Mercado Pago
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
        
        // Verificar si el pago fue APROBADO
        if (isset($payment_info['status']) && $payment_info['status'] === 'approved') {
            
            // Obtener el ID del usuario (external_reference)
            $usuario_id = $payment_info['external_reference'] ?? null;
            
            if (!$usuario_id) {
                error_log("ERROR: Pago sin external_reference (ID Usuario)");
                http_response_code(200); exit();
            }
            
            error_log("Pago aprobado para Usuario ID: $usuario_id");

            // Registrar el pago en pagos_suscripcion
            try {
                // Buscamos si pertenece a una suscripción
                $suscripcion_id = null;
                if (!empty($payment_info['order']['id'])) { // A veces viene ligado por orden o preapproval
                     // Lógica opcional para ligar ID exacto
                }

                $stmt = $pdo->prepare("INSERT INTO pagos_suscripcion (usuario_id, payment_id, status, monto, moneda, datos_pago, fecha_pago) VALUES (?, ?, 'approved', ?, ?, ?, NOW())");
                $stmt->execute([
                    $usuario_id,
                    $payment_id,
                    $payment_info['transaction_amount'] ?? 0,
                    $payment_info['currency_id'] ?? 'MXN',
                    json_encode($payment_info)
                ]);
            } catch (PDOException $e) {
                error_log("Error DB Pago: " . $e->getMessage());
            }
            
            // ============================================
            // ACTIVAR O EXTENDER PREMIUM
            // ============================================
            try {
                // 1. Obtener fecha actual de expiración
                $stmt = $pdo->prepare("SELECT fecha_expiracion_premium, rol FROM usuarios WHERE id = ?");
                $stmt->execute([$usuario_id]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($usuario) {
                    // Calcular nueva fecha (+30 días)
                    $ahora = time();
                    $expiracion_actual = $usuario['fecha_expiracion_premium'] ? strtotime($usuario['fecha_expiracion_premium']) : 0;
                    
                    if ($expiracion_actual > $ahora) {
                        // Si ya tiene premium, sumamos 30 días a su fecha final
                        $nueva_fecha = date('Y-m-d H:i:s', strtotime('+30 days', $expiracion_actual));
                    } else {
                        // Si está vencido o es nuevo, sumamos 30 días a HOY
                        $nueva_fecha = date('Y-m-d H:i:s', strtotime('+30 days'));
                    }

                    // 2. Actualizar Usuario
                    $stmt = $pdo->prepare("UPDATE usuarios SET es_premium = 1, fecha_expiracion_premium = ?, fecha_ultimo_pago = NOW() WHERE id = ?");
                    $stmt->execute([$nueva_fecha, $usuario_id]);

                    // 3. Actualizar Tienda (si es vendedor)
                    if ($usuario['rol'] === 'vendedor') {
                        $stmt = $pdo->prepare("UPDATE tiendas SET es_premium = 1, fecha_expiracion_premium = ? WHERE vendedor_id = ?");
                        $stmt->execute([$nueva_fecha, $usuario_id]);
                    }

                    // 4. Marcar log como procesado
                    if ($webhook_log_id) {
                        $pdo->query("UPDATE webhook_logs SET procesado = 1, fecha_procesado = NOW() WHERE id = $webhook_log_id");
                    }

                    error_log("✅ PREMIUM ACTIVADO: Usuario $usuario_id hasta $nueva_fecha");
                }
            } catch (PDOException $e) {
                error_log("❌ Error activando Premium: " . $e->getMessage());
            }
        }
    }
}

// Responder a Mercado Pago que recibimos el mensaje
http_response_code(200);
echo "OK";
?>
