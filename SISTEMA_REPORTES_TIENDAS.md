# 📋 Sistema de Reportes de Tiendas

## ✅ Implementación Completa

El sistema de reportes de tiendas ha sido implementado exitosamente. Permite a los usuarios reportar tiendas con contenido inapropiado y a los administradores gestionar estos reportes.

---

## 🗂️ Archivos Creados

### 1. **Base de Datos**
- `crear_tabla_reportes.sql` - Script SQL para crear la tabla
- `ejecutar_crear_reportes.php` - Instalador automático

### 2. **Backend**
- `procesar_reporte.php` - Procesa y guarda los reportes
- `admin_ver_reportes.php` - Panel de administración de reportes

### 3. **Frontend**
- Modal agregado en `tienda_detalle.php`
- JavaScript para validación y contador de caracteres

---

## 🚀 Instalación

### Paso 1: Crear la Tabla en la Base de Datos

Ejecuta el instalador visitando:
```
http://tu-dominio.com/ejecutar_crear_reportes.php
```

O ejecuta manualmente el SQL:
```sql
CREATE TABLE IF NOT EXISTS reportes_tienda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_tienda INT NOT NULL,
    id_usuario_reporta INT NULL,
    motivo TEXT NOT NULL,
    estado ENUM('pendiente', 'resuelto') DEFAULT 'pendiente',
    fecha_reporte DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion DATETIME NULL,
    notas_admin TEXT NULL,
    FOREIGN KEY (id_tienda) REFERENCES tiendas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario_reporta) REFERENCES usuarios(id) ON DELETE SET NULL
);
```

### Paso 2: Verificar Permisos

Asegúrate de que el usuario administrador tenga `tipo_usuario = 'admin'` en la tabla `usuarios`.

---

## 📱 Cómo Funciona

### Para Usuarios (Reportar)

1. **Abrir el Modal**
   - El usuario hace clic en el botón de la bandera (🚩) en la cabecera de la tienda
   - Se abre un modal con un formulario

2. **Llenar el Formulario**
   - Campo de texto (textarea) para describir el motivo
   - Mínimo 10 caracteres, máximo 1000
   - Contador de caracteres en tiempo real

3. **Validaciones**
   - El motivo no puede estar vacío
   - Debe tener al menos 10 caracteres
   - No puede exceder 1000 caracteres
   - No se puede reportar la misma tienda más de una vez en 24 horas

4. **Confirmación**
   - Se pide confirmación antes de enviar
   - Mensaje de éxito al completar

### Para Administradores (Gestionar)

1. **Acceder al Panel**
   ```
   http://tu-dominio.com/admin_ver_reportes.php
   ```

2. **Ver Estadísticas**
   - Total de reportes
   - Reportes pendientes
   - Reportes resueltos

3. **Filtrar Reportes**
   - Ver solo pendientes
   - Ver solo resueltos
   - Ver todos

4. **Información Mostrada**
   - Nombre de la tienda (con enlace)
   - Logo de la tienda
   - Motivo del reporte
   - Fecha del reporte
   - Usuario que reportó (o "Anónimo")
   - Estado (pendiente/resuelto)

5. **Marcar como Resuelto**
   - Botón "Marcar como Resuelto"
   - Modal para agregar notas administrativas (opcional)
   - Se registra la fecha de resolución

---

## 🔒 Seguridad Implementada

### Validaciones del Lado del Cliente
- Validación de longitud mínima/máxima
- Confirmación antes de enviar
- Contador visual de caracteres

### Validaciones del Lado del Servidor
- Verificación de datos POST
- Validación de longitud del motivo
- Verificación de existencia de la tienda
- Prevención de reportes duplicados (24 horas)
- Sanitización de entradas con `trim()`
- Uso de prepared statements (PDO)

### Protección de Datos
- Foreign keys con `ON DELETE CASCADE` y `ON DELETE SET NULL`
- Índices para optimizar consultas
- Logs de errores para debugging

---

## 📊 Estructura de la Tabla

```sql
reportes_tienda
├── id (INT, AUTO_INCREMENT, PRIMARY KEY)
├── id_tienda (INT, NOT NULL, FK → tiendas.id)
├── id_usuario_reporta (INT, NULL, FK → usuarios.id)
├── motivo (TEXT, NOT NULL)
├── estado (ENUM: 'pendiente', 'resuelto')
├── fecha_reporte (DATETIME, DEFAULT NOW)
├── fecha_resolucion (DATETIME, NULL)
└── notas_admin (TEXT, NULL)
```

---

## 🎨 Características del Diseño

### Modal de Reporte
- Diseño moderno con gradientes
- Icono de bandera en el header
- Alerta de advertencia sobre reportes falsos
- Lista de motivos válidos
- Contador de caracteres con colores:
  - Rojo: menos de 10 caracteres
  - Verde: entre 10 y 900 caracteres
  - Amarillo: más de 900 caracteres

### Panel de Administración
- Header con gradiente
- Tarjetas de estadísticas con hover effects
- Filtros visuales por estado
- Tarjetas de reportes con borde de color según estado
- Modal para resolver con notas administrativas
- Enlaces directos a las tiendas reportadas

---

## 🔗 Flujo Completo

```
Usuario ve tienda → Clic en botón 🚩 → Modal se abre
                                          ↓
                                    Llena formulario
                                          ↓
                                    Validación JS
                                          ↓
                                    Confirmación
                                          ↓
                              POST a procesar_reporte.php
                                          ↓
                                  Validaciones PHP
                                          ↓
                              INSERT en reportes_tienda
                                          ↓
                              Redirect con mensaje éxito
                                          ↓
                              Admin ve en panel
                                          ↓
                              Admin marca resuelto
                                          ↓
                              UPDATE estado + notas
```

---

## 🛠️ Personalización

### Cambiar Tiempo de Espera entre Reportes

En `procesar_reporte.php`, línea ~50:
```php
// Cambiar de 24 horas a otro valor
AND fecha_reporte > DATE_SUB(NOW(), INTERVAL 24 HOUR)
// Ejemplo: 48 horas
AND fecha_reporte > DATE_SUB(NOW(), INTERVAL 48 HOUR)
```

### Agregar Notificaciones por Email

En `procesar_reporte.php`, después del INSERT:
```php
// Enviar email al admin
$to = "admin@tudominio.com";
$subject = "Nuevo reporte de tienda";
$message = "Se ha reportado la tienda ID: $id_tienda\nMotivo: $motivo";
mail($to, $subject, $message);
```

### Agregar Categorías de Reporte

Modificar el modal para incluir un select:
```html
<select name="categoria_reporte" class="form-control">
    <option value="contenido_inapropiado">Contenido inapropiado</option>
    <option value="informacion_falsa">Información falsa</option>
    <option value="spam">Spam</option>
    <option value="otro">Otro</option>
</select>
```

---

## 📈 Mejoras Futuras Sugeridas

1. **Sistema de Notificaciones**
   - Email al admin cuando hay nuevo reporte
   - Notificación al vendedor cuando su tienda es reportada

2. **Estadísticas Avanzadas**
   - Gráficas de reportes por mes
   - Tiendas más reportadas
   - Tipos de reportes más comunes

3. **Acciones Automáticas**
   - Suspender tienda automáticamente después de X reportes
   - Sistema de strikes (3 reportes = suspensión temporal)

4. **Historial de Reportes**
   - Ver todos los reportes de una tienda específica
   - Ver todos los reportes de un usuario específico

5. **Categorización**
   - Agregar categorías de reportes
   - Filtrar por categoría en el panel admin

---

## 🐛 Solución de Problemas

### El modal no se abre
- Verificar que Bootstrap JS esté cargado
- Verificar que no haya errores en la consola
- Verificar que el ID del modal sea correcto

### Los reportes no se guardan
- Verificar que la tabla existe en la base de datos
- Verificar permisos de escritura
- Revisar logs de errores PHP

### No puedo acceder al panel de admin
- Verificar que tu usuario tenga `tipo_usuario = 'admin'`
- Verificar que estés logueado
- Revisar la sesión PHP

---

## ✨ Conclusión

El sistema de reportes está completamente funcional y listo para producción. Incluye:

✅ Validaciones completas (cliente y servidor)
✅ Diseño moderno y responsive
✅ Panel de administración completo
✅ Seguridad implementada
✅ Prevención de spam
✅ Mensajes de feedback claros
✅ Documentación completa

¡El sistema de moderación está listo para mantener tu plataforma segura y limpia! 🎉
