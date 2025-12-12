<?php
require_once 'config.php';
require_once 'funciones_config.php';

// 1. Seguridad: Verificar logueo y rol
if (!esta_logueado() || $_SESSION['rol'] !== 'vendedor') {
    header("Location: auth.php");
    exit();
}

// 2. Seguridad: Obtener credenciales desde Railway (Variables de Entorno)
$mp_access_token = getenv('MP_ACCESS_TOKEN');

if (!$mp_access_token) {
    die("Error de configuración: Faltan credenciales de Mercado Pago en Railway.");
}

// 3. Configuración automática de la URL (Detecta si estás en Railway o Local)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
// Si estamos en Railway, forzamos HTTPS
if (getenv('RAILWAY_ENVIRONMENT')) { $protocol = 'https'; }
$base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

// 4. Verificar usuario
$usuario = obtenerInfoUsuario($pdo, $_SESSION['user_id']);
if (!$usuario) die("Error: Usuario no encontrado");

// 5. Evitar doble suscripción (Verificar si ya tiene una pendiente o autorizada)
try {
    $stmt = $pdo->prepare("SELECT * FROM suscripciones_premium WHERE usuario_id = ? AND status IN ('authorized', 'pending') ORDER BY fecha_creacion DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    if ($stmt->fetch()) {
        header("Location: panel_vendedor.php?msg=suscripcion_pendiente");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error DB: " . $e->getMessage());
}

// 6. Configuración del Plan (Mensual $150)
$precio = 150.00; 
$moneda = 'MXN';

$subscription_data = [
    'reason' => 'Membresía Premium - Mercado Huasteco (Mensual)',
    'auto_recurring' => [
        'frequency' => 1,
        'frequency_type' => 'months',
        'transaction_amount' => $precio,
        'currency_id' => $moneda
    ],
    'back_url' => $base_url . '/pago_exitoso.php',
    'payer_email' => $usuario['email'],
    'external_reference' => (string)$_SESSION['user_id'],
    'status' => 'pending'
];

// 7. Enviar petición a Mercado Pago (cURL)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/preapproval');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($subscription_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $mp_access_token
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 8. Procesar respuesta
if ($http_code == 201) {
    $subscription = json_decode($response, true);
    
    // Guardar en Base de Datos
    try {
        $stmt = $pdo->prepare("INSERT INTO suscripciones_premium (usuario_id, preapproval_id, status, monto, moneda, datos_suscripcion) VALUES (?, ?, 'pending', ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'], 
            $subscription['id'], 
            $precio, 
            $moneda,
            json_encode($subscription)
        ]);
    } catch (PDOException $e) {
        error_log("Error al guardar suscripción: " . $e->getMessage());
    }
    
    // Redirigir al cliente a pagar
    header("Location: " . $subscription['init_point']);
    exit();

} else {
    // Manejo de errores
    error_log("Error Mercado Pago ($http_code): " . $response);
    die("Hubo un error al conectar con Mercado Pago. Código: $http_code. Revisa los logs.");
}
?>
