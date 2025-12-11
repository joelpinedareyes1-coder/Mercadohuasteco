<?php
/**
 * Script de Prueba para Webhook de Mercado Pago
 * 
 * Este script simula una notificación de Mercado Pago para probar
 * que el webhook funciona correctamente.
 * 
 * USO:
 * 1. Abre este archivo en tu navegador: http://localhost/test_webhook.php
 * 2. Ingresa un ID de usuario válido
 * 3. Haz clic en "Simular Webhook"
 * 4. Verifica que el usuario se active como Premium
 */

require_once 'config.php';

// Verificar que solo admins puedan acceder
if (!esta_logueado() || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado. Solo administradores pueden usar esta herramienta.");
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar simulación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simular'])) {
    $usuario_id = intval($_POST['usuario_id']);
    
    // Verificar que el usuario existe
    $stmt = $pdo->prepare("SELECT id, nombre, email, es_premium, fecha_expiracion_premium FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        $mensaje = "❌ Error: Usuario con ID $usuario_id no encontrado";
        $tipo_mensaje = 'error';
    } else {
        // Crear un JSON simulado de Mercado Pago
        $webhook_data = [
            'type' => 'payment',
            'data' => [
                'id' => 'TEST_' . time() // ID de pago simulado
            ]
        ];
        
        // Crear datos de pago simulados
        $payment_info = [
            'id' => 'TEST_' . time(),
            'status' => 'approved',
            'external_reference' => (string)$usuario_id,
            'transaction_amount' => 150.00,
            'currency_id' => 'MXN',
            'date_created' => date('Y-m-d\TH:i:s.000-00:00'),
            'preapproval_id' => 'TEST_PREAPPROVAL_' . time()
        ];
        
        // Simular el proceso del webhook
        try {
            // Registrar en webhook_logs
            $stmt = $pdo->prepare("INSERT INTO webhook_logs (tipo, payment_id, datos_recibidos, fecha_recepcion) VALUES (?, ?, ?, NOW())");
            $stmt->execute([
                'payment',
                $payment_info['id'],
                json_encode($webhook_data)
            ]);
            $webhook_log_id = $pdo->lastInsertId();
            
            // Registrar el pago
            $stmt = $pdo->prepare("INSERT INTO pagos_suscripcion (usuario_id, payment_id, status, monto, moneda, datos_pago, fecha_pago) VALUES (?, ?, 'approved', ?, ?, ?, NOW())");
            $stmt->execute([
                $usuario_id,
                $payment_info['id'],
                $payment_info['transaction_amount'],
                $payment_info['currency_id'],
                json_encode($payment_info)
            ]);
            
            // Calcular nueva fecha de expiración
            if ($usuario['fecha_expiracion_premium'] && strtotime($usuario['fecha_expiracion_premium']) > time()) {
                // Ya tiene Premium activo - extender
                $fecha_base = new DateTime($usuario['fecha_expiracion_premium']);
                $fecha_base->modify('+30 days');
                $fecha_expiracion = $fecha_base->format('Y-m-d H:i:s');
                $accion = 'extendido';
            } else {
                // Activar desde ahora
                $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+30 days'));
                $accion = 'activado';
            }
            
            // Activar Premium
            $stmt = $pdo->prepare("UPDATE usuarios SET es_premium = 1, fecha_expiracion_premium = ?, fecha_ultimo_pago = NOW() WHERE id = ?");
            $stmt->execute([$fecha_expiracion, $usuario_id]);
            
            // Si es vendedor, actualizar tienda
            $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_data && $user_data['rol'] === 'vendedor') {
                $stmt = $pdo->prepare("UPDATE tiendas SET es_premium = 1, fecha_expiracion_premium = ? WHERE vendedor_id = ?");
                $stmt->execute([$fecha_expiracion, $usuario_id]);
            }
            
            // Marcar webhook como procesado
            $stmt = $pdo->prepare("UPDATE webhook_logs SET procesado = 1, fecha_procesado = NOW() WHERE id = ?");
            $stmt->execute([$webhook_log_id]);
            
            $mensaje = "✅ ¡Éxito! Premium $accion para usuario: {$usuario['nombre']} (ID: $usuario_id)<br>";
            $mensaje .= "📅 Fecha de expiración: $fecha_expiracion<br>";
            $mensaje .= "💰 Pago simulado registrado: {$payment_info['id']}";
            $tipo_mensaje = 'success';
            
        } catch (PDOException $e) {
            $mensaje = "❌ Error al procesar: " . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

// Obtener lista de usuarios para el selector
$stmt = $pdo->query("SELECT id, nombre, email, rol, es_premium, fecha_expiracion_premium FROM usuarios ORDER BY nombre");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Webhook Mercado Pago</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .content {
            padding: 30px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .info-box h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-box ul {
            margin-left: 20px;
            color: #666;
            font-size: 14px;
            line-height: 1.8;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            color: #666;
        }
        
        .premium-badge {
            display: inline-block;
            background: #ffd700;
            color: #333;
            padding: 2px 8px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Test Webhook Mercado Pago</h1>
            <p>Simula una notificación de pago para probar el sistema</p>
        </div>
        
        <div class="content">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h3>📋 ¿Qué hace este test?</h3>
                <ul>
                    <li>Simula una notificación de pago aprobado de Mercado Pago</li>
                    <li>Registra el webhook en la tabla <code>webhook_logs</code></li>
                    <li>Registra el pago en la tabla <code>pagos_suscripcion</code></li>
                    <li>Activa o extiende el Premium del usuario por 30 días</li>
                    <li>Si es vendedor, también actualiza su tienda</li>
                </ul>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="usuario_id">Selecciona un usuario:</label>
                    <select name="usuario_id" id="usuario_id" required onchange="mostrarInfoUsuario(this)">
                        <option value="">-- Selecciona un usuario --</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?php echo $u['id']; ?>" 
                                    data-nombre="<?php echo htmlspecialchars($u['nombre']); ?>"
                                    data-email="<?php echo htmlspecialchars($u['email']); ?>"
                                    data-rol="<?php echo $u['rol']; ?>"
                                    data-premium="<?php echo $u['es_premium']; ?>"
                                    data-expiracion="<?php echo $u['fecha_expiracion_premium']; ?>">
                                <?php echo htmlspecialchars($u['nombre']); ?> 
                                (<?php echo htmlspecialchars($u['email']); ?>) 
                                - <?php echo ucfirst($u['rol']); ?>
                                <?php if ($u['es_premium']): ?>
                                    <span class="premium-badge">⭐ PREMIUM</span>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="user-info" class="user-info" style="display: none; margin-bottom: 20px;">
                    <!-- Se llenará con JavaScript -->
                </div>
                
                <button type="submit" name="simular" class="btn">
                    🚀 Simular Webhook de Pago Aprobado
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function mostrarInfoUsuario(select) {
            const option = select.options[select.selectedIndex];
            const userInfo = document.getElementById('user-info');
            
            if (option.value) {
                const nombre = option.dataset.nombre;
                const email = option.dataset.email;
                const rol = option.dataset.rol;
                const premium = option.dataset.premium === '1';
                const expiracion = option.dataset.expiracion;
                
                let html = `<strong>Usuario seleccionado:</strong><br>`;
                html += `📧 ${email}<br>`;
                html += `👤 Rol: ${rol}<br>`;
                html += `⭐ Premium: ${premium ? 'Sí' : 'No'}`;
                
                if (premium && expiracion) {
                    html += ` (expira: ${expiracion})`;
                }
                
                userInfo.innerHTML = html;
                userInfo.style.display = 'block';
            } else {
                userInfo.style.display = 'none';
            }
        }
    </script>
</body>
</html>
