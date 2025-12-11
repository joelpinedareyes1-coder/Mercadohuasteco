<?php
require_once 'config.php';

// Configurar variables para el header
$page_title = "Acerca de Nosotros";
$page_description = "Conoce más sobre Mercado Huasteco y nuestra misión";
$body_class = "about-page";
$additional_css = ['css/legal-pages.css'];

// Incluir el header
include 'includes/header.php'; 
?>

<div class="legal-hero">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="legal-icon">
                    <i class="fas fa-store"></i>
                </div>
                <h1 class="legal-title">Acerca de Mercado Huasteco</h1>
                <p class="legal-subtitle">Conectando el talento de la región con oportunidades</p>
            </div>
        </div>
    </div>
</div>

<div class="container legal-content">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="legal-card">
                <div class="legal-section">
                    <h2><i class="fas fa-lightbulb me-2"></i>Nuestra Misión</h2>
                    <p>Mercado Huasteco nació con una visión clara: <strong>crear un puente digital entre estudiantes universitarios y emprendedores locales</strong>, fortaleciendo el ecosistema económico de nuestra comunidad.</p>
                    <p>Creemos que cada estudiante merece acceso fácil a servicios de calidad, y cada emprendedor merece una plataforma para hacer crecer su negocio.</p>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-eye me-2"></i>Nuestra Visión</h2>
                    <p>Ser la plataforma líder en la región que conecta el talento universitario con oportunidades de negocio, promoviendo el emprendimiento local y facilitando el acceso a productos y servicios de calidad.</p>
                    
                    <div class="info-box">
                        <p><strong>Aspiramos a:</strong> Transformar la manera en que estudiantes y emprendedores interactúan, creando una comunidad digital vibrante y próspera.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-heart me-2"></i>Nuestros Valores</h2>
                    <div class="purpose-grid">
                        <div class="purpose-item">
                            <i class="fas fa-handshake"></i>
                            <h4>Confianza</h4>
                            <p>Construimos relaciones basadas en la transparencia y la honestidad</p>
                        </div>
                        <div class="purpose-item">
                            <i class="fas fa-users"></i>
                            <h4>Comunidad</h4>
                            <p>Fortalecemos los lazos entre estudiantes y emprendedores locales</p>
                        </div>
                        <div class="purpose-item">
                            <i class="fas fa-rocket"></i>
                            <h4>Innovación</h4>
                            <p>Buscamos constantemente mejorar y evolucionar nuestra plataforma</p>
                        </div>
                        <div class="purpose-item">
                            <i class="fas fa-star"></i>
                            <h4>Calidad</h4>
                            <p>Promovemos la excelencia en productos y servicios</p>
                        </div>
                    </div>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-history me-2"></i>Nuestra Historia</h2>
                    <p>Mercado Huasteco comenzó como un proyecto universitario con el objetivo de resolver un problema real: la dificultad de los estudiantes para encontrar tiendas y servicios confiables cerca del campus.</p>
                    
                    <h3>El Comienzo</h3>
                    <p>Un grupo de estudiantes emprendedores identificó que muchos negocios locales excelentes pasaban desapercibidos, mientras que los estudiantes perdían tiempo buscando opciones de calidad.</p>
                    
                    <h3>El Crecimiento</h3>
                    <p>Lo que comenzó como un simple directorio se transformó en una plataforma completa con sistema de reseñas, gestión de tiendas y herramientas para emprendedores.</p>
                    
                    <h3>El Presente</h3>
                    <p>Hoy, Mercado Huasteco conecta a cientos de estudiantes con decenas de tiendas locales, generando oportunidades y fortaleciendo la economía de nuestra comunidad universitaria.</p>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-gift me-2"></i>¿Qué Ofrecemos?</h2>
                    
                    <h3>Para Estudiantes</h3>
                    <ul class="legal-list">
                        <li><i class="fas fa-check-circle"></i> <strong>Directorio Completo:</strong> Acceso a todas las tiendas y servicios locales en un solo lugar</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Reseñas Verificadas:</strong> Opiniones reales de otros estudiantes para tomar mejores decisiones</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Búsqueda Inteligente:</strong> Encuentra exactamente lo que necesitas con filtros avanzados</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Favoritos:</strong> Guarda tus tiendas preferidas para acceso rápido</li>
                    </ul>

                    <h3>Para Vendedores</h3>
                    <ul class="legal-list">
                        <li><i class="fas fa-check-circle"></i> <strong>Visibilidad:</strong> Llega a cientos de estudiantes potenciales</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Gestión Fácil:</strong> Panel de control intuitivo para administrar tu tienda</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Estadísticas:</strong> Conoce el rendimiento de tu tienda con datos en tiempo real</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Interacción:</strong> Responde reseñas y construye relaciones con tus clientes</li>
                    </ul>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-chart-line me-2"></i>Nuestro Impacto</h2>
                    <div class="purpose-grid">
                        <div class="purpose-item">
                            <i class="fas fa-store"></i>
                            <h4>Tiendas Registradas</h4>
                            <p>Decenas de negocios locales con presencia digital</p>
                        </div>
                        <div class="purpose-item">
                            <i class="fas fa-users"></i>
                            <h4>Estudiantes Activos</h4>
                            <p>Cientos de usuarios explorando y descubriendo</p>
                        </div>
                        <div class="purpose-item">
                            <i class="fas fa-star"></i>
                            <h4>Reseñas Publicadas</h4>
                            <p>Miles de opiniones ayudando a otros estudiantes</p>
                        </div>
                        <div class="purpose-item">
                            <i class="fas fa-handshake"></i>
                            <h4>Conexiones Creadas</h4>
                            <p>Innumerables relaciones entre clientes y vendedores</p>
                        </div>
                    </div>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-bullseye me-2"></i>Nuestros Objetivos</h2>
                    <p>Estamos comprometidos con el crecimiento continuo y la mejora de nuestra plataforma:</p>
                    <ul class="legal-list">
                        <li><i class="fas fa-check-circle"></i> Expandir a más campus universitarios en la región</li>
                        <li><i class="fas fa-check-circle"></i> Incorporar más funcionalidades para vendedores</li>
                        <li><i class="fas fa-check-circle"></i> Desarrollar una aplicación móvil nativa</li>
                        <li><i class="fas fa-check-circle"></i> Crear programas de apoyo para emprendedores estudiantiles</li>
                        <li><i class="fas fa-check-circle"></i> Implementar sistema de promociones y descuentos</li>
                    </ul>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-users-cog me-2"></i>Nuestro Equipo</h2>
                    <p>Mercado Huasteco es desarrollado y mantenido por un equipo apasionado de estudiantes y profesionales comprometidos con el emprendimiento local.</p>
                    
                    <div class="info-box">
                        <p><strong>¿Quieres unirte?</strong> Siempre estamos buscando talento para mejorar nuestra plataforma. Contáctanos si te interesa colaborar.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-handshake me-2"></i>Únete a Nuestra Comunidad</h2>
                    <p>Mercado Huasteco es más que una plataforma, es una comunidad. Te invitamos a ser parte de este movimiento que está transformando la manera en que estudiantes y emprendedores se conectan.</p>
                    
                    <div class="purpose-grid">
                        <div class="purpose-item">
                            <i class="fas fa-user-plus"></i>
                            <h4>Como Estudiante</h4>
                            <p>Descubre tiendas increíbles y apoya el emprendimiento local</p>
                        </div>
                        <div class="purpose-item">
                            <i class="fas fa-store-alt"></i>
                            <h4>Como Vendedor</h4>
                            <p>Haz crecer tu negocio y llega a más clientes</p>
                        </div>
                    </div>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-envelope me-2"></i>Contáctanos</h2>
                    <p>¿Tienes preguntas, sugerencias o quieres colaborar con nosotros? Nos encantaría escucharte.</p>
                    <div class="contact-info">
                        <p><i class="fas fa-envelope me-2"></i><strong>Email:</strong> contacto@mercadohuasteco.com</p>
                        <p><i class="fas fa-phone me-2"></i><strong>Teléfono:</strong> +1 (555) 123-4567</p>
                        <p><i class="fas fa-map-marker-alt me-2"></i><strong>Dirección:</strong> Campus Universitario, Ciudad, País</p>
                    </div>
                    
                    <h3>Síguenos en Redes Sociales</h3>
                    <div class="d-flex gap-3 justify-content-center mt-3">
                        <a href="https://www.facebook.com/share/17QBZ6ge8C/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-modern" style="border-radius: 50%; width: 50px; height: 50px; padding: 0; display: flex; align-items: center; justify-content: center;" title="Síguenos en Facebook">
                            <i class="fab fa-facebook" style="font-size: 1.5rem;"></i>
                        </a>
                        <a href="https://www.instagram.com/mercado_huasteco1?igsh=MWhqc2U0bDFqMGNkeg==" target="_blank" rel="noopener noreferrer" class="btn btn-outline-modern" style="border-radius: 50%; width: 50px; height: 50px; padding: 0; display: flex; align-items: center; justify-content: center;" title="Síguenos en Instagram">
                            <i class="fab fa-instagram" style="font-size: 1.5rem;"></i>
                        </a>
                    </div>
                </div>

                <div class="legal-footer">
                    <p><i class="fas fa-heart text-danger me-2"></i>Hecho con amor para la comunidad universitaria</p>
                    <p><i class="fas fa-users me-2"></i>Juntos construimos un mejor futuro para el emprendimiento local</p>
                </div>
            </div>

            <div class="legal-actions">
                <a href="index.php" class="btn btn-primary-modern">
                    <i class="fas fa-home me-2"></i>Volver al Inicio
                </a>
                <a href="directorio.php" class="btn btn-outline-modern">
                    <i class="fas fa-th-large me-2"></i>Ver Directorio
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
