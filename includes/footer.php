    </main>
    
    <!-- Footer Moderno -->
    <footer class="footer-modern">
        <div class="container">
            <div class="row">
                <!-- Información Principal -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-brand">
                        <i class="fas fa-store me-2"></i>Mercado Huasteco
                    </div>
                    <p class="footer-description">
                        Conectando estudiantes universitarios con las mejores tiendas y servicios locales. 
                        Una plataforma diseñada por y para la comunidad estudiantil.
                    </p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="https://www.facebook.com/share/17QBZ6ge8C/" target="_blank" rel="noopener noreferrer" class="text-white-50 hover-scale" style="font-size: 1.5rem;" title="Síguenos en Facebook">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="https://www.instagram.com/mercado_huasteco1?igsh=MWhqc2U0bDFqMGNkeg==" target="_blank" rel="noopener noreferrer" class="text-white-50 hover-scale" style="font-size: 1.5rem;" title="Síguenos en Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Navegación -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="text-white mb-3">Navegación</h5>
                    <ul class="footer-links">
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="directorio.php">Directorio</a></li>
                        <li><a href="auth.php">Registrarse</a></li>
                        <li><a href="auth.php">Iniciar Sesión</a></li>
                    </ul>
                </div>
                
                <!-- Para Vendedores -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="text-white mb-3">Para Vendedores</h5>
                    <ul class="footer-links">
                        <li><a href="auth.php">Registrar Tienda</a></li>
                        <li><a href="index.php#como-funciona">Cómo Funciona</a></li>
                        <li><a href="#" data-bs-toggle="modal" data-bs-target="#pricingModal">Precios</a></li>
                        <li><a href="#" data-bs-toggle="modal" data-bs-target="#supportModal">Soporte</a></li>
                    </ul>
                </div>
                
                <!-- Información Legal -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="text-white mb-3">Información</h5>
                    <ul class="footer-links">
                        <li><a href="acerca-de.php">Acerca de Nosotros</a></li>
                        <li><a href="terminos-de-uso.php">Términos de Uso</a></li>
                        <li><a href="politica-privacidad.php">Política de Privacidad</a></li>
                        <li><a href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Contacto</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Línea divisoria y copyright -->
            <div class="footer-bottom">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0">
                            &copy; <?php echo date('Y'); ?> Mercado Huasteco. Todos los derechos reservados.
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-0">
                            <i class="fas fa-heart text-danger me-1"></i>
                            Hecho con amor para la comunidad universitaria
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modales de Información -->
    
    <!-- Modal Acerca de Nosotros -->
    <div class="modal fade" id="aboutModal" tabindex="-1" aria-labelledby="aboutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="aboutModalLabel">
                        <i class="fas fa-info-circle me-2"></i>Acerca de Mercado Huasteco
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Mercado Huasteco es una plataforma innovadora diseñada específicamente para conectar el talento de la región con emprendedores y tiendas locales.</p>
                    <p>Nuestra misión es crear un ecosistema digital que beneficie tanto a estudiantes como a emprendedores locales, proporcionando:</p>
                    <ul>
                        <li><strong>Para Estudiantes:</strong> Un directorio confiable de tiendas con reseñas verificadas</li>
                        <li><strong>Para Vendedores:</strong> Una plataforma para promocionar sus negocios y llegar a más clientes</li>
                        <li><strong>Para la Comunidad:</strong> Fortalecimiento del ecosistema emprendedor universitario</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-modern" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Contacto -->
    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalLabel">
                        <i class="fas fa-envelope me-2"></i>Contacto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6><i class="fas fa-envelope me-2"></i>Email</h6>
                            <p>mercadohuasteco.com</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6><i class="fas fa-phone me-2"></i>Teléfono</h6>
                            <p>+52(229) 9152097</p>
                        </div>
                        <div class="col-12 mb-3">
                            <h6><i class="fas fa-map-marker-alt me-2"></i>Dirección</h6>
                            <p>INSTITUTO TECNOLOGICO SUPERIOR DE NARANJOS<br>Naranjos- veracruz, Mexico</p>
                        </div>
                    </div>
                    <hr>
                    <h6>Síguenos en redes sociales:</h6>
                    <div class="d-flex gap-3">
                        <a href="https://www.facebook.com/share/17QBZ6ge8C/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                            <i class="fab fa-facebook me-1"></i>Facebook
                        </a>
                        <a href="https://www.instagram.com/mercado_huasteco1?igsh=MWhqc2U0bDFqMGNkeg==" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm">
                            <i class="fab fa-instagram me-1"></i>Instagram
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-modern" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Términos de Uso -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">
                        <i class="fas fa-file-contract me-2"></i>Términos de Uso
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Aceptación de Términos</h6>
                    <p>Al usar Mercado Huasteco, aceptas estos términos de uso.</p>
                    
                    <h6>2. Uso de la Plataforma</h6>
                    <p>Mercado Huasteco es una plataforma de directorio que conecta el talento de la región con tiendas locales.</p>
                    
                    <h6>3. Responsabilidades del Usuario</h6>
                    <ul>
                        <li>Proporcionar información veraz y actualizada</li>
                        <li>Respetar a otros usuarios y comerciantes</li>
                        <li>No publicar contenido ofensivo o inapropiado</li>
                    </ul>
                    
                    <h6>4. Responsabilidades de los Vendedores</h6>
                    <ul>
                        <li>Mantener información actualizada de su tienda</li>
                        <li>Proporcionar productos/servicios de calidad</li>
                        <li>Responder de manera profesional a las reseñas</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-modern" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Política de Privacidad -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyModalLabel">
                        <i class="fas fa-shield-alt me-2"></i>Política de Privacidad
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Información que Recopilamos</h6>
                    <p>Recopilamos información necesaria para el funcionamiento de la plataforma:</p>
                    <ul>
                        <li>Información de registro (nombre, email)</li>
                        <li>Información de tiendas (para vendedores)</li>
                        <li>Reseñas y calificaciones</li>
                    </ul>
                    
                    <h6>Cómo Usamos tu Información</h6>
                    <ul>
                        <li>Para proporcionar y mejorar nuestros servicios</li>
                        <li>Para conectar estudiantes con tiendas</li>
                        <li>Para comunicarnos contigo sobre tu cuenta</li>
                    </ul>
                    
                    <h6>Protección de Datos</h6>
                    <p>Implementamos medidas de seguridad para proteger tu información personal.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-modern" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Soporte -->
    <div class="modal fade" id="supportModal" tabindex="-1" aria-labelledby="supportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="supportModalLabel">
                        <i class="fas fa-life-ring me-2"></i>Soporte
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>¿Necesitas ayuda?</h6>
                    <p>Estamos aquí para ayudarte. Puedes contactarnos de las siguientes maneras:</p>
                    
                    <div class="list-group">
                        <div class="list-group-item">
                            <i class="fas fa-envelope me-2"></i>
                            <strong>Email:</strong> soporte@mercadohuasteco.com
                        </div>
                        <div class="list-group-item">
                            <i class="fas fa-clock me-2"></i>
                            <strong>Horario:</strong> Lunes a Viernes, 9:00 AM - 6:00 PM
                        </div>
                        <div class="list-group-item">
                            <i class="fas fa-reply me-2"></i>
                            <strong>Tiempo de respuesta:</strong> Dentro de 24 horas
                        </div>
                    </div>
                    
                    <h6 class="mt-3">Preguntas Frecuentes</h6>
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    ¿Cómo registro mi tienda?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Regístrate como vendedor en la página de registro y completa la información de tu tienda.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    ¿Es gratis usar Mercado Huasteco?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Sí, Mercado Huasteco es completamente gratuito tanto para estudiantes como para vendedores.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-modern" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Precios -->
    <div class="modal fade" id="pricingModal" tabindex="-1" aria-labelledby="pricingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pricingModalLabel">
                        <i class="fas fa-tags me-2"></i>Precios
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card-modern text-center h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Plan Básico</h5>
                                    <h2 class="text-primary-modern">GRATIS</h2>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>Registro de tienda</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Hasta 5 fotos</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Reseñas de clientes</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Estadísticas básicas</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card-modern text-center h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Plan Premium</h5>
                                    <h2 class="text-primary-modern">$9.99<small>/mes</small></h2>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>Todo del plan básico</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Tienda destacada</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Fotos ilimitadas</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Estadísticas avanzadas</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Soporte prioritario</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-modern" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón de scroll to top -->
    <button class="btn btn-primary-modern scroll-to-top" id="scrollToTop" style="display: none;">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript Global -->
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-modern');
            const scrollToTop = document.getElementById('scrollToTop');
            
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
                scrollToTop.style.display = 'block';
            } else {
                navbar.classList.remove('scrolled');
                scrollToTop.style.display = 'none';
            }
        });

        // Scroll to top functionality
        document.getElementById('scrollToTop').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Smooth scrolling para enlaces internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('alert-dismissible')) {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);

        // Mejorar accesibilidad del navbar toggle
        const navbarToggler = document.querySelector('.navbar-toggler');
        if (navbarToggler) {
            navbarToggler.addEventListener('click', function() {
                const icon = this.querySelector('i');
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                
                if (isExpanded) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                } else {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                }
            });
        }
    </script>
    
    <!-- Scripts específicos de página -->
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js_file): ?>
            <script src="<?php echo $js_file; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- JavaScript inline específico de página -->
    <?php if (isset($inline_js)): ?>
        <script>
            <?php echo $inline_js; ?>
        </script>
    <?php endif; ?>
    
    <!-- Asistente Chispitas JavaScript -->
    <?php include 'includes/asistente_bateria_js.php'; ?>
</body>
</html>