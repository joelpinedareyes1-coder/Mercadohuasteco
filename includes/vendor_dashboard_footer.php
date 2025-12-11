            </div>
        </main>
    </div>

    <!-- JavaScript para funcionalidad del dashboard -->
    <script>
        // Toggle del sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            
            // Guardar estado en localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });

        // Restaurar estado del sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                document.getElementById('sidebar').classList.add('collapsed');
            }
        });

        // JavaScript para el menú móvil
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            if (sidebar && overlay) {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }

        function closeSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            if (sidebar && overlay) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        }

        // Cerrar sidebar al hacer clic en un enlace (en móvil)
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.sidebar .nav-link');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        closeSidebar();
                    }
                });
            });
            
            // Cerrar sidebar al redimensionar ventana
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    closeSidebar();
                }
            });
        });

        // Función para mostrar notificaciones
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${message}
            `;
            
            document.body.appendChild(notification);
            
            // Mostrar notificación
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            // Ocultar después de 4 segundos
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }

        // Efectos hover mejorados para botones
        document.querySelectorAll('.btn-modern').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px) scale(1.02)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-2px) scale(1)';
            });
        });

        // Animación de entrada para las tarjetas
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.card-modern').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            card.style.transitionDelay = (index * 0.1) + 's';
            observer.observe(card);
        });

        // Validación de formularios en tiempo real
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 0 0 3px rgba(40, 167, 69, 0.1)';
            });
            
            input.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
                if (!this.matches(':focus')) {
                    this.style.boxShadow = '';
                }
            });
        });

        // JavaScript específico de la página (si existe)
        <?php if (isset($inline_js)): ?>
            <?php echo $inline_js; ?>
        <?php endif; ?>
    </script>
    
    <!-- Asistente Chispitas JavaScript -->
    <?php include 'asistente_bateria_js.php'; ?>
</body>
</html>