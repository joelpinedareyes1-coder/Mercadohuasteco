<?php
session_start();
require_once 'config.php';

// Verificar que sea administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: auth.php');
    exit();
}

// Función para obtener configuración
function obtenerConfiguracion($pdo) {
    try {
        $stmt = $pdo->query("SELECT setting_nombre, setting_valor, descripcion FROM configuracion ORDER BY setting_nombre");
        $config = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['setting_nombre']] = $row;
        }
        
        // Debug: Log de configuración obtenida
        error_log("Configuración obtenida de BD: " . print_r($config, true));
        
        return $config;
    } catch (Exception $e) {
        error_log("Error al obtener configuración: " . $e->getMessage());
        return [];
    }
}

// Verificar que la tabla configuracion existe y tiene datos
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM configuracion");
    $total_config = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($total_config == 0) {
        // Insertar configuraciones por defecto si no existen
        $configuraciones_default = [
            ['site_name', 'Mercado Huasteco', 'Nombre del sitio web'],
            ['site_welcome_message', 'Bienvenido a nuestro directorio de tiendas locales', 'Mensaje de bienvenida en la página principal'],
            ['auto_approve_reviews', '1', 'Auto-aprobar reseñas (1=sí, 0=no)'],
            ['max_photos_per_store', '10', 'Máximo número de fotos por tienda'],
            ['featured_stores_limit', '6', 'Número máximo de tiendas destacadas en inicio']
        ];
        
        foreach ($configuraciones_default as $config_item) {
            $stmt = $pdo->prepare("INSERT INTO configuracion (setting_nombre, setting_valor, descripcion) VALUES (?, ?, ?)");
            $stmt->execute($config_item);
        }
        
        error_log("Configuraciones por defecto insertadas");
    }
} catch (Exception $e) {
    error_log("Error al verificar tabla configuracion: " . $e->getMessage());
}

$config = obtenerConfiguracion($pdo);
$mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Debug: Log de datos recibidos
        error_log("POST data recibido: " . print_r($_POST, true));
        
        // Manejar checkbox auto_approve_reviews por separado
        $auto_approve_value = isset($_POST['auto_approve_reviews']) ? '1' : '0';
        
        // Lista de campos esperados
        $campos_esperados = ['site_name', 'site_welcome_message', 'max_photos_per_store', 'featured_stores_limit'];
        
        // Actualizar campos de texto
        foreach ($campos_esperados as $campo) {
            if (isset($_POST[$campo])) {
                $stmt = $pdo->prepare("UPDATE configuracion SET setting_valor = ?, updated_at = NOW() WHERE setting_nombre = ?");
                $resultado = $stmt->execute([$_POST[$campo], $campo]);
                
                // Debug: Log de cada actualización
                error_log("Actualizando $campo con valor: " . $_POST[$campo] . " - Resultado: " . ($resultado ? 'OK' : 'ERROR'));
                
                if (!$resultado) {
                    throw new Exception("Error al actualizar $campo");
                }
            }
        }
        
        // Actualizar checkbox auto_approve_reviews
        $stmt = $pdo->prepare("UPDATE configuracion SET setting_valor = ?, updated_at = NOW() WHERE setting_nombre = ?");
        $resultado = $stmt->execute([$auto_approve_value, 'auto_approve_reviews']);
        
        error_log("Actualizando auto_approve_reviews con valor: $auto_approve_value - Resultado: " . ($resultado ? 'OK' : 'ERROR'));
        
        if (!$resultado) {
            throw new Exception("Error al actualizar auto_approve_reviews");
        }
        
        $pdo->commit();
        $mensaje = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Configuración guardada exitosamente</div>';
        
        // Recargar configuración después del commit
        $config = obtenerConfiguracion($pdo);
        
        // Debug: Log de configuración recargada
        error_log("Configuración recargada: " . print_r($config, true));
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error al guardar: ' . htmlspecialchars($e->getMessage()) . '</div>';
        error_log("Error en configuracion.php: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sitio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard_admin.php">
                <i class="fas fa-cog me-2"></i>Panel Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard_admin.php">
                    <i class="fas fa-arrow-left me-1"></i>Volver al Dashboard
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-cogs me-2"></i>Configuración del Sitio
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php echo $mensaje; ?>
                        
                        <?php if (empty($config)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Advertencia:</strong> No se pudieron cargar las configuraciones.
                            <a href="debug_configuracion.php" class="btn btn-sm btn-warning ms-2">
                                <i class="fas fa-bug"></i> Ejecutar Debug
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo session_id(); ?>">
                            
                            <!-- Nombre del Sitio -->
                            <div class="mb-4">
                                <label for="site_name" class="form-label fw-bold">
                                    <i class="fas fa-globe me-2 text-primary"></i>Nombre del Sitio
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="site_name" 
                                       name="site_name" 
                                       value="<?php echo htmlspecialchars($config['site_name']['setting_valor'] ?? ''); ?>"
                                       required>
                                <div class="form-text">
                                    <?php echo htmlspecialchars($config['site_name']['descripcion'] ?? ''); ?>
                                </div>
                            </div>

                            <!-- Mensaje de Bienvenida -->
                            <div class="mb-4">
                                <label for="site_welcome_message" class="form-label fw-bold">
                                    <i class="fas fa-comment-alt me-2 text-primary"></i>Mensaje de Bienvenida
                                </label>
                                <textarea class="form-control" 
                                          id="site_welcome_message" 
                                          name="site_welcome_message" 
                                          rows="3"
                                          required><?php echo htmlspecialchars($config['site_welcome_message']['setting_valor'] ?? ''); ?></textarea>
                                <div class="form-text">
                                    <?php echo htmlspecialchars($config['site_welcome_message']['descripcion'] ?? ''); ?>
                                </div>
                            </div>

                            <!-- Auto-aprobar Reseñas -->
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="auto_approve_reviews" 
                                           name="auto_approve_reviews"
                                           <?php echo ($config['auto_approve_reviews']['setting_valor'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="auto_approve_reviews">
                                        <i class="fas fa-check-circle me-2 text-success"></i>Auto-aprobar Reseñas
                                    </label>
                                </div>
                                <div class="form-text">
                                    <?php echo htmlspecialchars($config['auto_approve_reviews']['descripcion'] ?? ''); ?>
                                    <br><small class="text-muted">
                                        Si está desactivado, todas las reseñas nuevas requerirán aprobación manual.
                                    </small>
                                </div>
                            </div>

                            <!-- Configuraciones Adicionales -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="max_photos_per_store" class="form-label fw-bold">
                                        <i class="fas fa-images me-2 text-primary"></i>Máx. Fotos por Tienda
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="max_photos_per_store" 
                                           name="max_photos_per_store" 
                                           value="<?php echo htmlspecialchars($config['max_photos_per_store']['setting_valor'] ?? '10'); ?>"
                                           min="1" max="50">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="featured_stores_limit" class="form-label fw-bold">
                                        <i class="fas fa-star me-2 text-warning"></i>Tiendas Destacadas (Inicio)
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="featured_stores_limit" 
                                           name="featured_stores_limit" 
                                           value="<?php echo htmlspecialchars($config['featured_stores_limit']['setting_valor'] ?? '6'); ?>"
                                           min="1" max="20">
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="button" class="btn btn-secondary me-md-2" onclick="window.location.href='dashboard_admin.php'">
                                    <i class="fas fa-times me-1"></i>Cancelar
                                </button>
                                <button type="button" class="btn btn-info me-md-2" onclick="debugConfig()">
                                    <i class="fas fa-bug me-1"></i>Debug
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Guardar Configuración
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Vista Previa -->
                <div class="card shadow mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-eye me-2"></i>Vista Previa de Configuración Actual
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Nombre del Sitio:</strong><br>
                                <span class="text-primary fs-5"><?php echo htmlspecialchars($config['site_name']['setting_valor'] ?? 'No configurado'); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Auto-aprobar Reseñas:</strong><br>
                                <span class="badge <?php echo ($config['auto_approve_reviews']['setting_valor'] ?? '0') === '1' ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo ($config['auto_approve_reviews']['setting_valor'] ?? '0') === '1' ? 'Activado' : 'Desactivado'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <strong>Mensaje de Bienvenida:</strong><br>
                            <em class="text-muted">"<?php echo htmlspecialchars($config['site_welcome_message']['setting_valor'] ?? 'No configurado'); ?>"</em>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación del formulario
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Función de debug
        function debugConfig() {
            const formData = new FormData(document.querySelector('form'));
            let debugInfo = 'DATOS DEL FORMULARIO:\n';
            
            for (let [key, value] of formData.entries()) {
                debugInfo += `${key}: ${value}\n`;
            }
            
            debugInfo += '\nVALORES ACTUALES EN VISTA PREVIA:\n';
            debugInfo += `Nombre del Sitio: <?php echo addslashes($config['site_name']['setting_valor'] ?? 'No configurado'); ?>\n`;
            debugInfo += `Mensaje: <?php echo addslashes($config['site_welcome_message']['setting_valor'] ?? 'No configurado'); ?>\n`;
            debugInfo += `Auto-aprobar: <?php echo ($config['auto_approve_reviews']['setting_valor'] ?? '0'); ?>\n`;
            
            alert(debugInfo);
            console.log('Debug Config:', debugInfo);
        }
    </script>
</body>
</html>