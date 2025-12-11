<?php
session_start();
require_once 'config.php';

$mensaje = '';
$error = '';

// Verificar que el usuario esté logueado
if (!esta_logueado()) {
    header("Location: auth.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['nombre'];
$user_rol = $_SESSION['rol'];

// Obtener información actual del usuario
try {
    $stmt = $pdo->prepare("SELECT nombre, email, pregunta_secreta, password FROM usuarios WHERE id = ? AND activo = 1");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        $error = "Error: Usuario no encontrado.";
    }
    
    $tiene_pregunta = !empty($usuario['pregunta_secreta']);
    
} catch(PDOException $e) {
    $error = "Error al cargar la información del usuario.";
    error_log("Error en mi_perfil.php: " . $e->getMessage());
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pregunta_secreta = limpiar_entrada($_POST['pregunta_secreta']);
    $respuesta_secreta = $_POST['respuesta_secreta'];
    
    // Validaciones básicas
    if (empty($pregunta_secreta) || empty($respuesta_secreta)) {
        $error = "La pregunta y respuesta secreta son obligatorias.";
    } elseif (strlen($pregunta_secreta) < 10) {
        $error = "La pregunta secreta debe tener al menos 10 caracteres.";
    } elseif (strlen($respuesta_secreta) < 3) {
        $error = "La respuesta secreta debe tener al menos 3 caracteres.";
    } else {
        // Si ya tiene pregunta, verificar contraseña actual
        if ($tiene_pregunta) {
            $password_actual = $_POST['password_actual'];
            
            if (empty($password_actual)) {
                $error = "Debes ingresar tu contraseña actual para actualizar la pregunta secreta.";
            } elseif (!password_verify($password_actual, $usuario['password'])) {
                $error = "La contraseña actual es incorrecta.";
            }
        }
        
        // Si no hay errores, proceder con la actualización
        if (empty($error)) {
            try {
                // Hashear la nueva respuesta secreta
                $respuesta_secreta_hash = password_hash($respuesta_secreta, PASSWORD_DEFAULT);
                
                // Actualizar en la base de datos
                $stmt = $pdo->prepare("UPDATE usuarios SET pregunta_secreta = ?, respuesta_secreta = ? WHERE id = ?");
                $resultado = $stmt->execute([$pregunta_secreta, $respuesta_secreta_hash, $user_id]);
                
                if ($resultado) {
                    $mensaje = $tiene_pregunta ? 
                        "¡Pregunta secreta actualizada exitosamente!" : 
                        "¡Pregunta secreta configurada exitosamente! Ahora puedes recuperar tu contraseña si la olvidas.";
                    
                    // Recargar información del usuario
                    $stmt = $pdo->prepare("SELECT nombre, email, pregunta_secreta, password FROM usuarios WHERE id = ? AND activo = 1");
                    $stmt->execute([$user_id]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    $tiene_pregunta = !empty($usuario['pregunta_secreta']);
                } else {
                    $error = "Error al guardar la pregunta secreta. Intenta de nuevo.";
                }
            } catch(PDOException $e) {
                $error = "Error en el sistema. Intenta más tarde.";
                error_log("Error al actualizar pregunta secreta: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Mercado Huasteco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --color-principal: #006666;
            --color-secundario: #CC5500;
            --color-fondo: #F8F9FA;
            --color-tarjetas: #FFFFFF;
            --color-texto-principal: #333333;
            --color-texto-secundario: #6c757d;
        }
        
        body {
            background-color: var(--color-fondo);
            color: var(--color-texto-principal);
        }
        
        .navbar {
            background-color: var(--color-principal) !important;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--color-principal) 0%, #004d4d 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .profile-card {
            background: var(--color-tarjetas);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--color-principal), #004d4d);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #004d4d, #003333);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, var(--color-secundario), #b34700);
            border: none;
            color: white;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(45deg, #b34700, #a03d00);
            color: white;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border-left: 4px solid var(--color-secundario);
            border-radius: 8px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border-left: 4px solid #28a745;
            border-radius: 8px;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #ffb3ba);
            border-left: 4px solid #dc3545;
            border-radius: 8px;
        }
        
        .form-control:focus {
            border-color: var(--color-principal);
            box-shadow: 0 0 0 0.2rem rgba(0, 102, 102, 0.25);
        }
        
        .security-status {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .security-good {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 1px solid #28a745;
            color: #155724;
        }
        
        .security-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 1px solid var(--color-secundario);
            color: #856404;
        }
        
        .user-info {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .form-section {
            border-top: 2px solid #e9ecef;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="directorio.php">
                <i class="bi bi-geo-alt"></i> Mercado Huasteco
            </a>
            <div class="navbar-nav ms-auto">
                <?php if ($user_rol === 'admin'): ?>
                    <a class="nav-link" href="dashboard_admin.php">
                        <i class="bi bi-speedometer2"></i> Dashboard Admin
                    </a>
                <?php elseif ($user_rol === 'vendedor'): ?>
                    <a class="nav-link" href="panel_vendedor.php">
                        <i class="bi bi-shop"></i> Mi Panel
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="dashboard_cliente.php">
                        <i class="bi bi-person"></i> Mi panel
                    </a>
                <?php endif; ?>
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <section class="profile-header">
        <div class="container text-center">
            <h1 class="display-5 mb-3">
                <i class="bi bi-person-circle"></i> Mi Perfil
            </h1>
            <p class="lead">Gestiona tu información personal y configuración de seguridad</p>
        </div>
    </section>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Información del Usuario -->
                <div class="profile-card">
                    <h3 class="mb-4">
                        <i class="bi bi-info-circle text-primary"></i> Información de la Cuenta
                    </h3>
                    
                    <div class="user-info">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Nombre:</strong> <?php echo htmlspecialchars($user_name); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <strong>Rol:</strong> 
                                <span class="badge bg-primary"><?php echo ucfirst($user_rol); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Estado:</strong> 
                                <span class="badge bg-success">Activo</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración de Seguridad -->
                <div class="profile-card">
                    <h3 class="mb-4">
                        <i class="bi bi-shield-lock text-warning"></i> Configuración de Seguridad
                    </h3>
                    
                    <!-- Estado de Seguridad -->
                    <?php if (!$tiene_pregunta): ?>
                        <div class="security-status security-warning">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <h5 class="mb-1">⚠️ Configuración de Seguridad Incompleta</h5>
                                    <p class="mb-0">Aún no has configurado tu pregunta de seguridad. Añade una para poder recuperar tu cuenta si olvidas tu contraseña.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="security-status security-good">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle-fill me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <h5 class="mb-1">✅ Configuración de Seguridad Completa</h5>
                                    <p class="mb-0">Tu pregunta de seguridad está configurada. Puedes actualizarla cuando quieras.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Mensajes -->
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i><?php echo $mensaje; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Formulario -->
                    <div class="form-section">
                        <h4 class="mb-3">
                            <?php echo $tiene_pregunta ? 
                                '<i class="bi bi-pencil-square"></i> Actualizar Pregunta Secreta' : 
                                '<i class="bi bi-plus-circle"></i> Configurar Pregunta Secreta'; ?>
                        </h4>
                        
                        <?php if ($tiene_pregunta): ?>
                            <div class="alert alert-info">
                                <strong>Pregunta actual:</strong> "<?php echo htmlspecialchars($usuario['pregunta_secreta']); ?>"
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="pregunta_secreta" class="form-label">
                                    <i class="bi bi-question-circle"></i> 
                                    <?php echo $tiene_pregunta ? 'Nueva Pregunta Secreta:' : 'Pregunta Secreta:'; ?>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="pregunta_secreta" 
                                       name="pregunta_secreta" 
                                       required 
                                       minlength="10"
                                       placeholder="Ej: ¿Cuál es el nombre de tu primera mascota?"
                                       value="<?php echo isset($_POST['pregunta_secreta']) ? htmlspecialchars($_POST['pregunta_secreta']) : ''; ?>">
                                <div class="form-text">
                                    Mínimo 10 caracteres. Esta pregunta te ayudará a recuperar tu contraseña.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="respuesta_secreta" class="form-label">
                                    <i class="bi bi-key"></i> 
                                    <?php echo $tiene_pregunta ? 'Nueva Respuesta Secreta:' : 'Respuesta Secreta:'; ?>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="respuesta_secreta" 
                                       name="respuesta_secreta" 
                                       required 
                                       minlength="3"
                                       placeholder="Escribe tu respuesta secreta">
                                <div class="form-text">
                                    Mínimo 3 caracteres. Recuerda esta respuesta exactamente como la escribes.
                                </div>
                            </div>
                            
                            <?php if ($tiene_pregunta): ?>
                                <div class="mb-4">
                                    <label for="password_actual" class="form-label">
                                        <i class="bi bi-lock"></i> Contraseña Actual (para confirmar):
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_actual" 
                                           name="password_actual" 
                                           required 
                                           placeholder="Ingresa tu contraseña actual">
                                    <div class="form-text text-warning">
                                        <i class="bi bi-info-circle"></i> 
                                        Por seguridad, necesitamos verificar tu contraseña actual.
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-lg"></i> 
                                    <?php echo $tiene_pregunta ? 'Actualizar Pregunta' : 'Guardar Pregunta'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Información Adicional -->
                <div class="profile-card">
                    <h4 class="mb-3">
                        <i class="bi bi-info-circle text-info"></i> Información Importante
                    </h4>
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-shield-check text-success"></i> Seguridad</h6>
                            <ul class="list-unstyled small text-muted">
                                <li>• Tu respuesta se guarda encriptada</li>
                                <li>• Solo tú puedes ver tu pregunta</li>
                                <li>• Puedes cambiarla cuando quieras</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-lightbulb text-warning"></i> Consejos</h6>
                            <ul class="list-unstyled small text-muted">
                                <li>• Usa una pregunta que solo tú sepas</li>
                                <li>• Evita información pública</li>
                                <li>• Recuerda la respuesta exacta</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <?php if ($user_rol === 'cliente'): ?>
                <!-- Zona de Peligro - Solo para Compradores -->
                <div class="profile-card" style="border-left: 4px solid #dc3545;">
                    <h4 class="mb-3 text-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i> Zona de Peligro
                    </h4>
                    
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-info-circle"></i> Eliminar Cuenta Permanentemente</h6>
                        <p class="mb-2">
                            <strong>⚠️ ADVERTENCIA:</strong> Esta acción es <strong>irreversible</strong>. 
                            Al eliminar tu cuenta:
                        </p>
                        <ul class="mb-3">
                            <li>Se borrará toda tu información personal</li>
                            <li>Se eliminarán todas tus reseñas</li>
                            <li>Perderás acceso permanente a tu cuenta</li>
                            <li>No podrás recuperar esta información</li>
                        </ul>
                        
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarCuenta">
                            <i class="bi bi-trash3-fill"></i> Eliminar Mi Cuenta Permanentemente
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Cuenta -->
    <div class="modal fade" id="modalEliminarCuenta" tabindex="-1" aria-labelledby="modalEliminarCuentaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalEliminarCuentaLabel">
                        <i class="bi bi-exclamation-triangle-fill"></i> Confirmar Eliminación de Cuenta
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-exclamation-triangle-fill"></i> ¡ÚLTIMA ADVERTENCIA!</h6>
                        <p><strong>Esta acción NO se puede deshacer.</strong> Tu cuenta y toda la información asociada será eliminada permanentemente.</p>
                    </div>
                    
                    <h6>¿Estás completamente seguro de que quieres eliminar tu cuenta?</h6>
                    <p>Se eliminarán:</p>
                    <ul>
                        <li>✗ Tu información personal (nombre, email, etc.)</li>
                        <li>✗ Todas tus reseñas y calificaciones</li>
                        <li>✗ Tu historial de actividad</li>
                        <li>✗ Acceso permanente a Mercado Huasteco</li>
                    </ul>
                    
                    <form id="formEliminarCuenta" method="POST" action="eliminar_cuenta.php">
                        <div class="mb-3">
                            <label for="confirmacionTexto" class="form-label">
                                <strong>Para confirmar, escribe exactamente:</strong> <code>ELIMINAR</code>
                            </label>
                            <input type="text" class="form-control" id="confirmacionTexto" name="confirmacion" required
                                   placeholder="Escribe ELIMINAR para confirmar">
                        </div>
                        
                        <div class="mb-3">
                            <label for="passwordConfirmacion" class="form-label">
                                <strong>Ingresa tu contraseña actual:</strong>
                            </label>
                            <input type="password" class="form-control" id="passwordConfirmacion" name="password_actual" required
                                   placeholder="Tu contraseña actual">
                        </div>
                        
                        <input type="hidden" name="accion" value="eliminar_cuenta">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminacion" disabled>
                        <i class="bi bi-trash3-fill"></i> Sí, Eliminar Mi Cuenta Permanentemente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para validar confirmación de eliminación
        document.addEventListener('DOMContentLoaded', function() {
            // Modal de eliminación de cuenta
            const confirmacionTexto = document.getElementById('confirmacionTexto');
            const passwordConfirmacion = document.getElementById('passwordConfirmacion');
            const btnConfirmar = document.getElementById('btnConfirmarEliminacion');
            const formEliminar = document.getElementById('formEliminarCuenta');
            
            if (confirmacionTexto && passwordConfirmacion && btnConfirmar) {
                function validarFormulario() {
                    const textoValido = confirmacionTexto.value === 'ELIMINAR';
                    const passwordValido = passwordConfirmacion.value.length > 0;
                    
                    btnConfirmar.disabled = !(textoValido && passwordValido);
                    
                    if (textoValido) {
                        confirmacionTexto.classList.remove('is-invalid');
                        confirmacionTexto.classList.add('is-valid');
                    } else {
                        confirmacionTexto.classList.remove('is-valid');
                        if (confirmacionTexto.value.length > 0) {
                            confirmacionTexto.classList.add('is-invalid');
                        }
                    }
                }
                
                confirmacionTexto.addEventListener('input', validarFormulario);
                passwordConfirmacion.addEventListener('input', validarFormulario);
                
                btnConfirmar.addEventListener('click', function() {
                    if (confirm('¿Estás ABSOLUTAMENTE seguro? Esta acción NO se puede deshacer.')) {
                        formEliminar.submit();
                    }
                });
            }
            
            // Modal de desactivación de cuenta
            const passwordDesactivacion = document.getElementById('passwordDesactivacion');
            const confirmarDesactivacion = document.getElementById('confirmarDesactivacion');
            const btnConfirmarDesactivacion = document.getElementById('btnConfirmarDesactivacion');
            const formDesactivar = document.getElementById('formDesactivarCuenta');
            
            if (passwordDesactivacion && confirmarDesactivacion && btnConfirmarDesactivacion) {
                function validarDesactivacion() {
                    const passwordValido = passwordDesactivacion.value.length > 0;
                    const checkboxMarcado = confirmarDesactivacion.checked;
                    
                    btnConfirmarDesactivacion.disabled = !(passwordValido && checkboxMarcado);
                }
                
                passwordDesactivacion.addEventListener('input', validarDesactivacion);
                confirmarDesactivacion.addEventListener('change', validarDesactivacion);
                
                btnConfirmarDesactivacion.addEventListener('click', function() {
                    if (confirm('¿Estás seguro de que quieres desactivar tu cuenta? Podrás reactivarla cuando quieras.')) {
                        formDesactivar.submit();
                    }
                });
            }
        });
    </script>
</body>
</html>