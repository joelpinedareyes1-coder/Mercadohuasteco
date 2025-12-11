<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda Desactivada - Mercado Huasteco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .deactivation-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
            max-width: 700px;
            margin: 2rem;
        }
        
        .deactivation-icon {
            font-size: 4rem;
            color: #fd7e14;
            margin-bottom: 1rem;
        }
        
        .btn-panel {
            background: linear-gradient(45deg, #fd7e14, #ffc107);
            border: none;
            color: white;
            font-weight: bold;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .btn-panel:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-left: 4px solid #fd7e14;
        }
        
        .contact-box {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="deactivation-card">
        <div class="deactivation-icon">
            <i class="bi bi-eye-slash-fill"></i>
        </div>
        
        <h1 class="mb-4">Tienda Desactivada Exitosamente</h1>
        
        <p class="lead mb-4">
            Tu tienda <strong>"<?php echo isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : 'Tu tienda'; ?>"</strong> 
            ha sido desactivada y ya no es visible en el directorio público.
        </p>
        
        <div class="info-box">
            <h6><i class="bi bi-info-circle text-primary"></i> ¿Qué significa esto?</h6>
            <div class="row text-start">
                <div class="col-md-6">
                    <h6 class="text-success">✓ Se conserva:</h6>
                    <ul class="list-unstyled small">
                        <li>• Todas tus reseñas y calificaciones</li>
                        <li>• Estadísticas de visitas</li>
                        <li>• Información de la tienda</li>
                        <li>• Tu cuenta de vendedor</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-warning">⚠️ Temporalmente oculto:</h6>
                    <ul class="list-unstyled small">
                        <li>• Tienda no visible en búsquedas</li>
                        <li>• No aparece en el directorio</li>
                        <li>• Enlaces directos no funcionan</li>
                        <li>• No recibe nuevas visitas</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="contact-box">
            <h6><i class="bi bi-arrow-clockwise text-info"></i> ¿Quieres reactivar tu tienda?</h6>
            <p class="mb-2">
                Puedes reactivar tu tienda en cualquier momento contactando a nuestro equipo de administración.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <small class="text-muted">
                    <i class="bi bi-envelope"></i> admin@mercadohuasteco.com
                </small>
                <small class="text-muted">
                    <i class="bi bi-whatsapp"></i> WhatsApp: +1234567890
                </small>
            </div>
        </div>
        
        <div class="alert alert-info">
            <h6><i class="bi bi-lightbulb"></i> Alternativas</h6>
            <p class="mb-0">
                Si solo necesitas una pausa temporal, considera usar el <strong>"Modo Vacaciones"</strong> 
                en lugar de desactivar completamente tu tienda.
            </p>
        </div>
        
        <div class="mt-4">
            <a href="panel_vendedor.php" class="btn btn-panel me-3">
                <i class="bi bi-arrow-left"></i> Volver al Panel
            </a>
            <a href="directorio.php" class="btn btn-outline-primary">
                <i class="bi bi-house-fill"></i> Ver Directorio
            </a>
        </div>
        
        <hr class="my-4">
        
        <div class="text-muted">
            <small>
                <i class="bi bi-shield-check"></i> 
                Tu información está segura. La desactivación es reversible y no se pierde ningún dato.
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>