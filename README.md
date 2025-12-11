# 🛍️ Mercado Huasteco - Plataforma de Directorio de Tiendas

**Mercado Huasteco** es una plataforma completa de directorio de tiendas que conecta el talento de la región con las mejores tiendas locales. Permite a los vendedores registrar sus tiendas y a los clientes descubrir, calificar y visitar establecimientos de su interés.

## 🌟 Características Principales

### 👥 Sistema de Usuarios
- ✅ Registro con roles diferenciados (Cliente/Vendedor/Admin)
- ✅ Autenticación segura con hash de contraseñas
- ✅ Recuperación de contraseña con preguntas de seguridad
- ✅ Gestión de perfiles y configuración de cuenta
- ✅ Sistema de eliminación de cuentas

### 🏪 Gestión de Tiendas
- ✅ Registro y gestión completa de tiendas
- ✅ Galería de imágenes con múltiples fotos
- ✅ Categorización por tipo de negocio
- ✅ Sistema de destacados y promociones
- ✅ Estadísticas de visitas y clics
- ✅ Estados de tienda (activa/desactivada/eliminada)

### ⭐ Sistema de Calificaciones
- ✅ Reseñas y calificaciones de 1-5 estrellas
- ✅ Comentarios de usuarios verificados
- ✅ Moderación de reseñas por administradores
- ✅ Promedio de calificaciones automático
- ✅ Animaciones y efectos visuales en estrellas

### 🔍 Búsqueda y Filtros
- ✅ Búsqueda inteligente por nombre y descripción
- ✅ Filtros por categoría y calificación
- ✅ API de búsqueda en tiempo real
- ✅ Resultados paginados y optimizados

### ❤️ Sistema de Favoritos
- ✅ Guardar tiendas favoritas
- ✅ Lista personalizada de favoritos
- ✅ Gestión completa desde el dashboard

### 📊 Dashboards Especializados
- ✅ **Cliente**: Favoritos, reseñas, perfil
- ✅ **Vendedor**: Gestión de tienda, estadísticas, galería
- ✅ **Admin**: Moderación, gestión de usuarios, reportes

## 🗂️ Estructura del Proyecto

### 📄 Páginas Principales
```
index.php              # Página de inicio
directorio.php         # Listado de tiendas con filtros
tienda_detalle.php     # Página individual de tienda
auth.php              # Sistema de autenticación (login/registro)
```

### 👤 Gestión de Usuarios
```
mi_perfil.php         # Perfil del usuario
olvide_password.php   # Recuperación de contraseña
reset_password.php    # Reset de contraseña
responder_pregunta.php # Preguntas de seguridad
eliminar_cuenta.php   # Eliminación de cuenta
logout.php           # Cerrar sesión
```

### 🏪 Dashboards
```
dashboard_cliente.php    # Panel del cliente
dashboard_vendedor.php   # Panel del vendedor
dashboard_admin.php      # Panel del administrador
panel_vendedor.php      # Panel principal del vendedor
```

### ⚙️ Gestión y Administración
```
gestionar_tienda.php     # Gestión individual de tienda
gestionar_tiendas.php    # Gestión múltiple (admin)
gestionar_usuarios.php   # Gestión de usuarios (admin)
gestionar_favoritos.php  # Sistema de favoritos
gestionar_busqueda.php   # Búsqueda avanzada
moderar_reseñas.php     # Moderación de reseñas
```

### 📈 Funcionalidades Especiales
```
estadisticas_vendedor.php # Estadísticas detalladas
galeria_vendedor.php     # Gestión de galería
mis_favoritos.php        # Lista de favoritos
reportes.php            # Sistema de reportes
configuracion.php       # Configuración del sistema
```

### 🔧 Configuración y APIs
```
config.php              # Configuración de BD y funciones
funciones_config.php    # Funciones auxiliares
api/                   # APIs para búsqueda y filtros
ajax/                  # Scripts AJAX
```

### 🎨 Recursos
```
css/                   # Estilos CSS
js/                    # JavaScript
img/                   # Imágenes del sistema
uploads/               # Archivos subidos por usuarios
includes/              # Archivos incluidos
```

## 🗄️ Base de Datos

### Tablas Principales
```sql
usuarios               # Usuarios del sistema
tiendas               # Información de tiendas
calificaciones        # Reseñas y calificaciones
favoritos             # Sistema de favoritos
configuracion         # Configuración del sistema
chispitas_dialogo     # Sistema de mensajes
```

### Características de BD
- ✅ Estructura normalizada y optimizada
- ✅ Índices para búsquedas rápidas
- ✅ Relaciones con integridad referencial
- ✅ Campos de auditoría (fechas, estados)
- ✅ Soporte para soft deletes

## 🚀 Instalación

### 1. Requisitos
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, GD, mbstring

### 2. Configuración de Base de Datos
```bash
# Crear la base de datos
mysql -u root -p -e "CREATE DATABASE mercado_huasteco;"

# Importar estructura
mysql -u root -p mercado_huasteco < database.sql
```

### 3. Configuración del Sistema
```php
// Editar config.php con tus datos
$host = 'localhost';
$dbname = 'mercado_huasteco';
$username = 'tu_usuario';
$password = 'tu_contraseña';
```

### 4. Permisos de Archivos
```bash
# Dar permisos de escritura a uploads
chmod 755 uploads/
chmod 755 img/
```

## 🔐 Seguridad Implementada

### Autenticación
- ✅ Hash seguro de contraseñas con `password_hash()`
- ✅ Verificación con `password_verify()`
- ✅ Sesiones seguras con regeneración de ID
- ✅ Preguntas de seguridad para recuperación

### Protección de Datos>     <div class="navbar-logo-area">
    <img src="img/logo.png" alt="Logo Mercado Huasteco" class="logo-sombrero">
    <div>
        <h1>Mercado Huasteco</h1>
        <p>Conectando el talento de la región.</p>
        <img src="img/pareja-banca.png" alt="Pareja en banca" class="logo-pareja">
    </div>
</div>
- ✅ Consultas preparadas (PDO) contra SQL injection
- ✅ Sanitización con `htmlspecialchars()` contra XSS
- ✅ Validación de entrada en cliente y servidor
- ✅ Filtrado de archivos subidos

### Control de Acceso
- ✅ Verificación de roles y permisos
- ✅ Protección de páginas administrativas
- ✅ Validación de propiedad de recursos
- ✅ Rate limiting en APIs

## 🎨 Características de Diseño

### Interfaz Moderna
- ✅ Diseño responsive para móviles y desktop
- ✅ Paleta de colores profesional
- ✅ Tipografía optimizada para legibilidad
- ✅ Iconografía consistente con Bootstrap Icons

### Experiencia de Usuario
- ✅ Navegación intuitiva y clara
- ✅ Feedback visual en todas las acciones
- ✅ Animaciones suaves y profesionales
- ✅ Carga optimizada de imágenes

### Animaciones Especiales
- ✅ Estrellas con efectos de brillo y hover
- ✅ Transiciones suaves en botones
- ✅ Efectos de carga y estados
- ✅ Micro-interacciones en formularios

## 📱 Funcionalidades por Rol

### 👤 Cliente
- Explorar directorio de tiendas
- Buscar y filtrar establecimientos
- Calificar y reseñar tiendas
- Gestionar lista de favoritos
- Ver historial de reseñas

### 🏪 Vendedor
- Registrar y gestionar tienda
- Subir galería de imágenes
- Ver estadísticas de visitas
- Responder a reseñas
- Configurar información de contacto

### 👨‍💼 Administrador
- Moderar reseñas y contenido
- Gestionar usuarios y tiendas
- Ver reportes y estadísticas
- Configurar sistema
- Gestionar categorías

## 🔧 APIs Disponibles

### Búsqueda
```
GET /api/buscar.php?q=termino&categoria=X&calificacion=Y
```

### Filtros
```
GET /api/filtrar_tiendas.php?filtros=json
```

### Favoritos
```
POST /ajax/gestionar_favoritos.php
```

## 📊 Métricas y Estadísticas

### Para Vendedores
- ✅ Número de visitas a la tienda
- ✅ Clics en "Visitar Tienda Oficial"
- ✅ Promedio de calificaciones
- ✅ Total de reseñas recibidas
- ✅ Tendencias temporales

### Para Administradores
- ✅ Usuarios registrados por rol
- ✅ Tiendas activas vs inactivas
- ✅ Reseñas pendientes de moderación
- ✅ Estadísticas de uso general

## 🛠️ Mantenimiento

### Tareas Regulares
- Backup de base de datos
- Limpieza de archivos temporales
- Optimización de imágenes
- Revisión de logs de error

### Monitoreo
- Estado de la base de datos
- Rendimiento de consultas
- Espacio en disco
- Logs de seguridad

## 🤝 Contribución

Para contribuir al proyecto:
1. Fork el repositorio
2. Crea una rama para tu feature
3. Realiza tus cambios
4. Envía un pull request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver archivo LICENSE para más detalles.

## 📞 Soporte

Para soporte técnico o consultas:
- Email: soporte@mercadohuasteco.com
- Documentación: Ver este README
- Issues: Usar el sistema de issues del repositorio

---

**Mercado Huasteco** - Conectando el talento de la región 🛍️✨