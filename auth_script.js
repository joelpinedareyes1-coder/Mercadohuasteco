// Elementos del DOM
const signUpButton = document.getElementById('signUp');
const signInButton = document.getElementById('signIn');
const container = document.getElementById('container');

// Variables para manejar el estado
let isSignUpMode = false;

// Función para cambiar a modo registro
function switchToSignUp() {
    if (!isSignUpMode) {
        container.classList.add("right-panel-active");
        isSignUpMode = true;

        // ===== SOLUCIÓN MÓVIL: Control directo del display =====
        const signUpContainer = document.querySelector('.sign-up-container');
        const signInContainer = document.querySelector('.sign-in-container');

        if (window.innerWidth <= 768) {
            // En móvil: controlar display directamente
            signUpContainer.style.display = 'block';
            signInContainer.style.display = 'none';
        }

        // Cambiar el título de la página
        document.title = 'Registrarse - Mercado Huasteco';

        // Enfocar el primer input del formulario de registro
        setTimeout(() => {
            const firstInput = document.querySelector('.sign-up-container input[type="text"]');
            if (firstInput) {
                firstInput.focus();
            }
        }, 600);
    }
}

// Función para cambiar a modo inicio de sesión
function switchToSignIn() {
    if (isSignUpMode) {
        container.classList.remove("right-panel-active");
        isSignUpMode = false;

        // ===== SOLUCIÓN MÓVIL: Control directo del display =====
        const signUpContainer = document.querySelector('.sign-up-container');
        const signInContainer = document.querySelector('.sign-in-container');

        if (window.innerWidth <= 768) {
            // En móvil: controlar display directamente
            signUpContainer.style.display = 'none';
            signInContainer.style.display = 'block';
        }

        // Cambiar el título de la página
        document.title = 'Iniciar Sesión - Mercado Huasteco';

        // Enfocar el primer input del formulario de inicio de sesión
        setTimeout(() => {
            const firstInput = document.querySelector('.sign-in-container input[type="email"]');
            if (firstInput) {
                firstInput.focus();
            }
        }, 600);
    }
}

// Event listeners para los botones principales
signUpButton.addEventListener('click', switchToSignUp);
signInButton.addEventListener('click', switchToSignIn);

// Función para manejar el envío del formulario de registro
function handleSignUpSubmit(event) {
    const form = event.target;

    // Obtener datos del formulario
    const userData = {
        name: form.querySelector('input[name="nombre"]').value,
        email: form.querySelector('input[type="email"]').value,
        password: form.querySelector('input[name="password"]').value,
        confirmPassword: form.querySelector('input[name="confirmar_password"]').value,
        role: form.querySelector('input[name="rol"]:checked') ? form.querySelector('input[name="rol"]:checked').value : ''
    };

    // Validaciones básicas del lado del cliente
    if (!validateSignUpForm(userData)) {
        event.preventDefault();
        return false;
    }

    // Mostrar loading en el botón
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Registrando...';
        submitBtn.disabled = true;

        // El formulario se enviará normalmente al servidor
        // El servidor manejará la respuesta
    }

    // Permitir que el formulario se envíe normalmente
    return true;
}

// Función para manejar el envío del formulario de inicio de sesión
function handleSignInSubmit(event) {
    const form = event.target;

    // Obtener datos del formulario
    const loginData = {
        email: form.querySelector('input[type="email"]').value,
        password: form.querySelector('input[name="password"]').value,
        remember: form.querySelector('input[type="checkbox"]') ? form.querySelector('input[type="checkbox"]').checked : false
    };

    // Validaciones básicas del lado del cliente
    if (!validateSignInForm(loginData)) {
        event.preventDefault();
        return false;
    }

    // Mostrar loading en el botón
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Iniciando sesión...';
        submitBtn.disabled = true;

        // El formulario se enviará normalmente al servidor
        // El servidor manejará la respuesta y redirección
    }

    // Permitir que el formulario se envíe normalmente
    return true;
}

// Función de validación para registro
function validateSignUpForm(data) {
    if (!data.name || data.name.trim().length < 2) {
        showNotification('El nombre debe tener al menos 2 caracteres', 'error');
        return false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(data.email)) {
        showNotification('Por favor ingresa un email válido', 'error');
        return false;
    }

    if (data.password.length < 6) {
        showNotification('La contraseña debe tener al menos 6 caracteres', 'error');
        return false;
    }

    if (data.password !== data.confirmPassword) {
        showNotification('Las contraseñas no coinciden', 'error');
        return false;
    }

    if (!data.role || data.role === '') {
        showNotification('Por favor selecciona un tipo de cuenta (Cliente o Vendedor)', 'error');
        return false;
    }

    return true;
}

// Función de validación para inicio de sesión
function validateSignInForm(data) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(data.email)) {
        showNotification('Por favor ingresa un email válido', 'error');
        return false;
    }

    if (data.password.length < 1) {
        showNotification('Por favor ingresa tu contraseña', 'error');
        return false;
    }

    return true;
}

// Función para mostrar notificaciones
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;

    // Agregar estilos dinámicamente si no existen
    if (!document.querySelector('#notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 10px;
                color: white;
                font-weight: 500;
                z-index: 10000;
                transform: translateX(400px);
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                max-width: 350px;
            }
            
            .notification.success {
                background: linear-gradient(135deg, #28a745, #20c997);
            }
            
            .notification.error {
                background: linear-gradient(135deg, #dc3545, #fd7e14);
            }
            
            .notification.info {
                background: linear-gradient(135deg, #007bff, #6f42c1);
            }
            
            .notification.show {
                transform: translateX(0);
            }
            
            .notification-content {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .notification-content i {
                font-size: 18px;
            }
        `;
        document.head.appendChild(styles);
    }

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('show');
    }, 100);

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

// Función para manejar efectos de input
function setupInputEffects() {
    const inputs = document.querySelectorAll('input');

    inputs.forEach(input => {
        input.addEventListener('focus', function () {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function () {
            this.parentElement.classList.remove('focused');
        });

        input.addEventListener('input', function () {
            if (this.type === 'email') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailRegex.test(this.value)) {
                    this.style.borderColor = '#28a745';
                } else if (this.value.length > 0) {
                    this.style.borderColor = '#dc3545';
                } else {
                    this.style.borderColor = '';
                }
            }

            if (this.type === 'password') {
                if (this.value.length >= 6) {
                    this.style.borderColor = '#28a745';
                } else if (this.value.length > 0) {
                    this.style.borderColor = '#ffc107';
                } else {
                    this.style.borderColor = '';
                }
            }
        });
    });
}

// Función para manejar enlaces sociales
function setupSocialLinks() {
    const socialLinks = document.querySelectorAll('.social');

    socialLinks.forEach(link => {
        link.addEventListener('click', function (event) {
            event.preventDefault();

            const platform = this.querySelector('i').classList[1].split('-')[1];
            showNotification(`Conectando con ${platform}...`, 'info');

            setTimeout(() => {
                showNotification(`Conexión con ${platform} no disponible en demo`, 'error');
            }, 2000);
        });
    });
}

// Función para manejar el redimensionado de ventana
function handleResize() {
    const signUpContainer = document.querySelector('.sign-up-container');
    const signInContainer = document.querySelector('.sign-in-container');

    if (window.innerWidth <= 768) {
        // En móvil: asegurar que solo un formulario esté visible
        if (isSignUpMode) {
            signUpContainer.style.display = 'block';
            signInContainer.style.display = 'none';
        } else {
            signUpContainer.style.display = 'none';
            signInContainer.style.display = 'block';
        }
    } else {
        // En desktop: restaurar comportamiento normal
        signUpContainer.style.display = '';
        signInContainer.style.display = '';
    }
}

// Función de inicialización
function init() {
    const signUpForm = document.querySelector('.sign-up-container form');
    const signInForm = document.querySelector('.sign-in-container form');

    if (signUpForm) {
        signUpForm.addEventListener('submit', handleSignUpSubmit);
    }

    if (signInForm) {
        signInForm.addEventListener('submit', handleSignInSubmit);
    }

    setupInputEffects();
    setupSocialLinks();

    // ===== CONFIGURACIÓN INICIAL MÓVIL =====
    handleResize(); // Aplicar configuración inicial
    window.addEventListener('resize', handleResize); // Escuchar cambios de tamaño

    // ===== DETECTAR ANCLA #registro EN LA URL =====
    const hash = window.location.hash;
    if (hash === '#registro' || hash === '#register') {
        // Cambiar automáticamente al formulario de registro
        setTimeout(() => {
            switchToSignUp();
        }, 100);
        // Limpiar el hash de la URL después de un momento
        setTimeout(() => {
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        }, 500);
    }

    // Mensaje de bienvenida solo si no hay errores
    const hasError = document.querySelector('.mensaje.error');
    const hasSuccess = document.querySelector('.mensaje.success');

    if (!hasError && !hasSuccess) {
        setTimeout(() => {
            showNotification('¡Bienvenido a Mercado Huasteco! 🛍️', 'success');
        }, 1000);
    }

    // Enfocar el primer input según el modo activo
    if (hash === '#registro') {
        setTimeout(() => {
            const firstInput = document.querySelector('.sign-up-container input[type="text"]');
            if (firstInput) {
                firstInput.focus();
            }
        }, 700);
    } else {
        const firstInput = document.querySelector('.sign-in-container input[type="email"]');
        if (firstInput) {
            setTimeout(() => {
                firstInput.focus();
            }, 500);
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', init);

// Agregar animación CSS adicional
const additionalStyles = document.createElement('style');
additionalStyles.textContent = `
    .input-group.focused {
        transform: translateY(-2px);
    }
    
    .form-container {
        will-change: transform, opacity;
    }
    
    .overlay {
        will-change: transform;
    }
`;
document.head.appendChild(additionalStyles);