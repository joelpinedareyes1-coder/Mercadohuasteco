<?php
session_start();
require_once 'config.php';

// Verificar que el usuario sea administrador
if (!esta_logueado() || $_SESSION['rol'] !== 'admin') {
    header('Location: auth.php');
    exit();
}

// Procesar acciones del formulario
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['accion'])) {
            switch ($_POST['accion']) {
                case 'crear':
                    $sql = "INSERT INTO chispitas_dialogo (titulo_menu, respuesta, tipo, orden, esta_activo) VALUES (?, ?, ?, ?, 1)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$_POST['titulo_menu'], $_POST['respuesta'], $_POST['tipo'], $_POST['orden']]);
                    $mensaje = "Diálogo creado exitosamente.";
                    $tipo_mensaje = "success";
                    break;
                    
                case 'editar':
                    $sql = "UPDATE chispitas_dialogo SET titulo_menu = ?, respuesta = ?, tipo = ?, orden = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$_POST['titulo_menu'], $_POST['respuesta'], $_POST['tipo'], $_POST['orden'], $_POST['id']]);
                    $mensaje = "Diálogo actualizado exitosamente.";
                    $tipo_mensaje = "success";
                    break;
                    
                case 'toggle_estado':
                    $sql = "UPDATE chispitas_dialogo SET esta_activo = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $nuevo_estado = $_POST['estado'] == '1' ? 0 : 1;
                    $stmt->execute([$nuevo_estado, $_POST['id']]);
                    $mensaje = $nuevo_estado ? "Diálogo activado." : "Diálogo desactivado.";
                    $tipo_mensaje = "success";
                    break;
                    
                case 'eliminar':
                    $sql = "DELETE FROM chispitas_dialogo WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$_POST['id']]);
                    $mensaje = "Diálogo eliminado permanentemente.";
                    $tipo_mensaje = "success";
                    break;
            }
        }
    } catch (PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener todos los diálogos
$sql = "SELECT * FROM chispitas_dialogo ORDER BY orden ASC, id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$dialogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener el siguiente número de orden
$sql = "SELECT COALESCE(MAX(orden), 0) + 1 as siguiente_orden FROM chispitas_dialogo";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$siguiente_orden = $stmt->fetch(PDO::FETCH_ASSOC)['siguiente_orden'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Chispitas - Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #006666;
            --secondary-color: #CC5500;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
        }
        
        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: bold;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #004d4d;
            border-color: #004d4d;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-secondary:hover {
            background-color: #b34700;
            border-color: #b34700;
        }
        
        .table-responsive {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .badge-activo {
            background-color: var(--success-color);
        }
        
        .badge-inactivo {
            background-color: var(--danger-color);
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), #004d4d);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 102, 102, 0.25);
        }
        
        .btn-sm {
            margin: 2px;
        }
        
        .orden-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
        
        /* Estilos para el formulario de actualización rápida */
        .card-header.bg-warning {
            background: linear-gradient(135deg, #ffc107, #ffb300) !important;
            border-bottom: 2px solid #ff8f00;
        }
        
        .btn-warning {
            background: linear-gradient(45deg, #ffc107, #ffb300);
            border: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-warning:hover {
            background: linear-gradient(45deg, #ffb300, #ff8f00);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.3);
        }
        
        #respuesta_rapida {
            border: 2px solid #ffc107;
            border-radius: 8px;
        }
        
        #respuesta_rapida:focus {
            border-color: #ffb300;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard_admin.php">
                <i class="fas fa-cog"></i> Panel Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard_admin.php">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-robot text-primary"></i> Gestionar Chispitas</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                        <i class="fas fa-plus"></i> Nuevo Diálogo
                    </button>
                </div>

                <!-- Mensajes -->
                <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Formulario de Actualización Rápida -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Informar Actualización Rápida a Chispitas</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="accion" value="crear">
                            <input type="hidden" name="titulo_menu" value="¡Nueva Actualización!">
                            <input type="hidden" name="tipo" value="actualizacion">
                            <input type="hidden" name="orden" value="1">
                            
                            <div class="col-md-9">
                                <label for="respuesta_rapida" class="form-label">Noticia o Actualización</label>
                                <textarea class="form-control" id="respuesta_rapida" name="respuesta" rows="3" required
                                          placeholder="Ej: 🎉 ¡Nueva función disponible! Ahora puedes filtrar tiendas por horarios de atención..."></textarea>
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i> 
                                    Esta actualización aparecerá como "¡Nueva Actualización!" en el menú de Chispitas
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" name="crear_dialogo" class="btn btn-warning w-100">
                                    <i class="fas fa-paper-plane"></i> Publicar Actualización
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de diálogos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Diálogos de Chispitas</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Orden</th>
                                        <th>Título Menú</th>
                                        <th>Respuesta</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($dialogos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-robot fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No hay diálogos configurados aún.</p>
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                                                <i class="fas fa-plus"></i> Crear el primer diálogo
                                            </button>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($dialogos as $dialogo): ?>
                                        <tr>
                                            <td>
                                                <span class="orden-badge"><?php echo $dialogo['orden']; ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($dialogo['titulo_menu']); ?></strong>
                                            </td>
                                            <td>
                                                <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis;">
                                                    <?php echo htmlspecialchars(substr($dialogo['respuesta'], 0, 80)); ?>
                                                    <?php if (strlen($dialogo['respuesta']) > 80): ?>...<?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($dialogo['tipo'] === 'actualizacion'): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-bolt"></i> <?php echo htmlspecialchars($dialogo['tipo']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($dialogo['tipo']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($dialogo['esta_activo']): ?>
                                                    <span class="badge badge-activo">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge badge-inactivo">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="editarDialogo(<?php echo htmlspecialchars(json_encode($dialogo)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="accion" value="toggle_estado">
                                                    <input type="hidden" name="id" value="<?php echo $dialogo['id']; ?>">
                                                    <input type="hidden" name="estado" value="<?php echo $dialogo['esta_activo']; ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo $dialogo['esta_activo'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>"
                                                            title="<?php echo $dialogo['esta_activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                        <i class="fas <?php echo $dialogo['esta_activo'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                                    </button>
                                                </form>
                                                
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmarEliminar(<?php echo $dialogo['id']; ?>, '<?php echo htmlspecialchars($dialogo['titulo_menu']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar -->
    <div class="modal fade" id="modalCrear" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo Diálogo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formDialogo">
                    <div class="modal-body">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        <input type="hidden" name="id" id="dialogoId">
                        
                        <div class="mb-3">
                            <label for="titulo_menu" class="form-label">Título del Menú</label>
                            <input type="text" class="form-control" id="titulo_menu" name="titulo_menu" required
                                   placeholder="Ej: ¿Qué son los cupones?">
                            <div class="form-text">Esta será la opción que aparece en el menú de Chispitas</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="respuesta" class="form-label">Respuesta</label>
                            <textarea class="form-control" id="respuesta" name="respuesta" rows="4" required
                                      placeholder="Ej: Los cupones son descuentos especiales que ofrecen las tiendas..."></textarea>
                            <div class="form-text">Este texto se mostrará cuando el usuario haga clic en la opción</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Diálogo</label>
                            <select class="form-control" id="tipo" name="tipo" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="ayuda">Ayuda</option>
                                <option value="funciones">Funciones</option>
                                <option value="motivacion">Motivación</option>
                                <option value="seguridad">Seguridad</option>
                                <option value="vendedores">Para Vendedores</option>
                                <option value="novedades">Novedades</option>
                                <option value="actualizacion">Actualización</option>
                                <option value="general">General</option>
                            </select>
                            <div class="form-text">Categoría del diálogo para mejor organización</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="orden" class="form-label">Orden de Aparición</label>
                            <input type="number" class="form-control" id="orden" name="orden" min="1" required
                                   value="<?php echo $siguiente_orden; ?>">
                            <div class="form-text">Número que determina el orden en el menú (menor número = aparece primero)</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">Crear Diálogo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="modalEliminar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres eliminar permanentemente este diálogo?</p>
                    <p><strong id="preguntaEliminar"></strong></p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Esta acción no se puede deshacer. Si solo quieres ocultarlo temporalmente, usa el botón de desactivar.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" id="idEliminar">
                        <button type="submit" class="btn btn-danger">Eliminar Permanentemente</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarDialogo(dialogo) {
            document.getElementById('modalTitle').textContent = 'Editar Diálogo';
            document.getElementById('accion').value = 'editar';
            document.getElementById('dialogoId').value = dialogo.id;
            document.getElementById('titulo_menu').value = dialogo.titulo_menu;
            document.getElementById('respuesta').value = dialogo.respuesta;
            document.getElementById('tipo').value = dialogo.tipo;
            document.getElementById('orden').value = dialogo.orden;
            document.getElementById('btnSubmit').textContent = 'Actualizar Diálogo';
            
            new bootstrap.Modal(document.getElementById('modalCrear')).show();
        }
        
        function confirmarEliminar(id, titulo_menu) {
            document.getElementById('idEliminar').value = id;
            document.getElementById('preguntaEliminar').textContent = titulo_menu;
            new bootstrap.Modal(document.getElementById('modalEliminar')).show();
        }
        
        // Resetear modal al cerrarse
        document.getElementById('modalCrear').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalTitle').textContent = 'Nuevo Diálogo';
            document.getElementById('accion').value = 'crear';
            document.getElementById('dialogoId').value = '';
            document.getElementById('formDialogo').reset();
            document.getElementById('btnSubmit').textContent = 'Crear Diálogo';
            document.getElementById('orden').value = <?php echo $siguiente_orden; ?>;
        });
    </script>
</body>
</html>