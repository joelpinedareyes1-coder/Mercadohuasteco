<?php
require_once 'config.php';
require_once 'funciones_config.php';

// Verificar que el usuario esté logueado y sea vendedor
if (!esta_logueado() || $_SESSION['rol'] !== 'vendedor') {
    header("Location: auth.php");
    exit();
}

// Configuración de Mercado Pago
// IMPORTANTE: Reemplaza estos valores con tus credenciales reales de Mercado Pago
define('MP_ACCESS_TOKEN', 'TU_ACCESS_TOKEN_DE_MERCADO_PAGO');
define('MP_PUBLIC_KEY', 'TU_PUBLIC_KEY_DE_MERCADO_PAGO');

// ID del Plan de Suscripción creado en Mercado Pago
// IMPORTANTE: Reemplaza con el ID de tu plan (ej: 2c9380848e8e8e8e018e8e8e8e8e8e8e)
define('MP_PLAN_ID', 'TU_PLAN_ID_DE_MERCADO_PAGO');

// URL base del sitio (ajustar según tu dominio)
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']);

// Obtener información del usuario
$usuario = obtenerInfoUsuario($pdo, $_SESSION['user_id']);

if (!$usuario) {
    die("Error: Usuario no encontrado");
}

// Verificar si ya tiene Premium activo
if (esPremiumActivo($usuario['fecha_expiracion_premium'])) {
    header("Location: panel_vendedor.php?msg=ya_premium");
    exit();
}

// Verificar si ya tiene una suscripción activa
try {
    $stmt = $pdo->prepare("SELECT * FROM suscripciones_premium WHERE usuario_id = ? AND status IN ('authorized', 'pending') ORDER BY fecha_creacion DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $suscripcion_existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($suscripcion_existente) {
        header("Location: panel_vendedor.php?msg=suscripcion_pendiente");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al verificar suscripción: " . $e->getMessage());
}

// Configuración de la suscripción
$precio = 150.00; // $150 MXN
$moneda = 'MXN';

// Crear suscripción en Mercado Pago
$subscription_data = [
    'reason' => 'Suscripción Premium - Mercado Huasteco',
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

// Si tienes un plan creado en Mercado Pago, úsalo
if (MP_PLAN_ID !== 'TU_PLAN_ID_DE_MERCADO_PAGO') {
    $subscription_data['preapproval_plan_id'] = MP_PLAN_ID;
}

// Hacer la petición a Mercado Pago
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/preapproval');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($subscription_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . MP_ACCESS_TOKEN
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($http_code == 201) {
    $subscription = json_decode($response, true);
    
    // Guardar el registro de la suscripción en la base de datos
    try {
        $stmt = $pdo->prepare("INSERT INTO suscripciones_premium (usuario_id, preapproval_id, plan_id, status, monto, moneda, datos_suscripcion) VALUES (?, ?, ?, 'pending', ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'], 
            $subscription['id'], 
            MP_PLAN_ID,
            $precio, 
            $moneda,
            json_encode($subscription)
        ]);
    } catch (PDOException $e) {
        error_log("Error al guardar suscripción: " . $e->getMessage());
    }
    
    // Redirigir al checkout de Mercado Pago
    header("Location: " . $subscription['init_point']);
    exit();
} else {
    // Error al crear la suscripción
    error_log("Error al crear suscripción de Mercado Pago (HTTP $http_code): " . $response);
    if ($curl_error) {
        error_log("cURL Error: " . $curl_error);
    }
    header("Location: panel_vendedor.php?error=suscripcion_error");
    exit();
}
?>
