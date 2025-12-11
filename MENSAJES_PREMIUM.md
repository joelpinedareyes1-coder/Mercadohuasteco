# 💬 Sistema de Mensajes Premium

## 📋 Descripción

Documentación de todos los mensajes y banners que se muestran a los usuarios según su estado de suscripción Premium.

---

## 🎨 Banners en el Panel del Vendedor

### **1. Banner "Ya Eres Premium" (Usuario Premium Activo)**

**Cuándo se muestra:**
- Usuario tiene `es_premium = 1`
- `fecha_expiracion_premium` es mayor a la fecha actual

**Diseño:**
- Fondo verde degradado (#10b981 → #059669)
- Corona dorada animada
- Muestra fecha de expiración
- Muestra días restantes
- Botón para gestionar suscripción

**Información mostrada:**
- ✅ "¡Ya Eres Premium! 🎉"
- 📅 Fecha de expiración
- ⏰ Días restantes
- 🎯 Lista de funciones activas
- ⚙️ Botón "Gestionar Suscripción"

**Código:**
```php
<?php if (esPremiumActivo($usuario_info['fecha_expiracion_premium'])): ?>
    <!-- Banner verde "Ya eres Premium" -->
<?php endif; ?>
```

---

### **2. Banner "Activa Premium" (Usuario NO Premium)**

**Cuándo se muestra:**
- Usuario tiene `es_premium = 0` O
- `fecha_expiracion_premium` es menor a la fecha actual

**Diseño:**
- Fondo morado degradado (#667eea → #764ba2)
- Corona dorada
- Lista de beneficios Premium
- Precio destacado: $150 MXN/mes
- Botón CTA: "¡Activar Premium Ahora!"

**Información mostrada:**
- 👑 "¡Lleva tu Tienda al Siguiente Nivel!"
- 💰 Precio: $150 MXN/mes
- ✅ Lista de 8 beneficios Premium
- 🔒 "Pago seguro con Mercado Pago"

**Código:**
```php
<?php if (!esPremiumActivo($usuario_info['fecha_expiracion_premium'])): ?>
    <!-- Banner morado "Activa Premium" -->
<?php endif; ?>
```

---

## 📨 Mensajes de Alerta

### **Mensajes de Éxito (Verde)**

#### **1. "Ya eres Premium"**
**URL:** `panel_vendedor.php?msg=ya_premium`

**Cuándo se muestra:**
- Usuario intenta acceder a `crear_pago_mp.php` pero ya tiene Premium activo

**Mensaje:**
```
¡Ya eres Premium! Disfruta de todos los beneficios de tu suscripción activa.
```

**Código en crear_pago_mp.php:**
```php
if (esPremiumActivo($usuario['fecha_expiracion_premium'])) {
    header("Location: panel_vendedor.php?msg=ya_premium");
    exit();
}
```

---

#### **2. "Suscripción Pendiente"**
**URL:** `panel_vendedor.php?msg=suscripcion_pendiente`

**Cuándo se muestra:**
- Usuario intenta crear una nueva suscripción pero ya tiene una pendiente

**Mensaje:**
```
Ya tienes una suscripción en proceso. Por favor, completa el pago pendiente.
```

**Código en crear_pago_mp.php:**
```php
if ($suscripcion_existente) {
    header("Location: panel_vendedor.php?msg=suscripcion_pendiente");
    exit();
}
```

---

### **Mensajes de Error (Rojo)**

#### **1. "Error de Suscripción"**
**URL:** `panel_vendedor.php?error=suscripcion_error`

**Cuándo se muestra:**
- Error al crear la suscripción en Mercado Pago
- Problema de conexión con la API
- Credenciales inválidas

**Mensaje:**
```
Hubo un error al procesar tu suscripción. Por favor, intenta nuevamente o contacta a soporte.
```

**Código en crear_pago_mp.php:**
```php
if ($http_code != 201) {
    header("Location: panel_vendedor.php?error=suscripcion_error");
    exit();
}
```

---

## 🔒 Campos Bloqueados para Usuarios NO Premium

### **Campos con Badge "Solo Premium"**

Los siguientes campos muestran un badge gris y están deshabilitados si el usuario NO es Premium:

1. **WhatsApp**
   - Badge: 🔒 Solo Premium
   - Placeholder: "52181XXXXXXX (con código de país)"
   - Mensaje: "Actualiza a Premium para habilitar contacto directo por WhatsApp"

2. **Facebook**
   - Badge: 🔒 Solo Premium
   - Placeholder: "https://facebook.com/tutienda"
   - Mensaje: "Actualiza a Premium para mostrar tu Facebook"

3. **Instagram**
   - Badge: 🔒 Solo Premium
   - Placeholder: "https://instagram.com/tutienda"
   - Mensaje: "Actualiza a Premium para mostrar tu Instagram"

4. **TikTok**
   - Badge: 🔒 Solo Premium
   - Placeholder: "https://tiktok.com/@tutienda"
   - Mensaje: "Actualiza a Premium para mostrar tu TikTok"

5. **Video de Presentación**
   - Badge: 🔒 Solo Premium
   - Placeholder: "https://youtube.com/watch?v=..."
   - Mensaje: "Actualiza a Premium para agregar videos"

6. **Google Maps**
   - Badge: 🔒 Solo Premium
   - Placeholder: "Pega aquí el código de Google Maps"
   - Mensaje: "Actualiza a Premium para mostrar tu ubicación"

### **Código de Verificación:**
```php
<?php
$stmt_premium = $pdo->prepare("SELECT es_premium FROM usuarios WHERE id = ?");
$stmt_premium->execute([$_SESSION['user_id']]);
$usuario_premium = $stmt_premium->fetch(PDO::FETCH_ASSOC);
$es_premium = $usuario_premium && $usuario_premium['es_premium'] == 1;
?>

<input type="text" 
       name="telefono_wa" 
       <?php echo !$es_premium ? 'disabled' : ''; ?>
       placeholder="52181XXXXXXX">
```

---

## 🎯 Flujo de Mensajes

### **Escenario 1: Usuario NO Premium intenta activar Premium**

```
1. Usuario ve banner morado "Activa Premium"
2. Click en "¡Activar Premium Ahora!"
3. Redirige a crear_pago_mp.php
4. Verifica si ya es Premium → NO
5. Verifica si tiene suscripción pendiente → NO
6. Crea suscripción en Mercado Pago
7. Redirige a checkout de Mercado Pago
```

---

### **Escenario 2: Usuario Premium intenta activar Premium de nuevo**

```
1. Usuario ve banner verde "Ya Eres Premium"
2. Intenta acceder a crear_pago_mp.php (por URL directa)
3. Script verifica: esPremiumActivo() → SÍ
4. Redirige a panel_vendedor.php?msg=ya_premium
5. Muestra mensaje: "¡Ya eres Premium! Disfruta de todos los beneficios..."
6. Banner verde sigue visible
```

---

### **Escenario 3: Usuario con suscripción pendiente**

```
1. Usuario creó suscripción pero no completó el pago
2. Intenta crear otra suscripción
3. Script verifica: ¿Tiene suscripción pendiente? → SÍ
4. Redirige a panel_vendedor.php?msg=suscripcion_pendiente
5. Muestra mensaje: "Ya tienes una suscripción en proceso..."
```

---

### **Escenario 4: Error al crear suscripción**

```
1. Usuario intenta activar Premium
2. Error en API de Mercado Pago (credenciales, conexión, etc.)
3. Redirige a panel_vendedor.php?error=suscripcion_error
4. Muestra mensaje de error en rojo
5. Usuario puede intentar nuevamente
```

---

## 🎨 Estilos de los Mensajes

### **Mensaje de Éxito (Verde)**
```html
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    ¡Ya eres Premium! Disfruta de todos los beneficios...
</div>
```

### **Mensaje de Error (Rojo)**
```html
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle"></i>
    Hubo un error al procesar tu suscripción...
</div>
```

### **Mensaje de Información (Azul)**
```html
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Ya tienes una suscripción en proceso...
</div>
```

---

## 📊 Estados del Usuario

| Estado | es_premium | fecha_expiracion_premium | Banner Mostrado | Puede Activar |
|--------|-----------|-------------------------|-----------------|---------------|
| **NO Premium** | 0 | NULL o pasada | Morado "Activa" | ✅ Sí |
| **Premium Activo** | 1 | Futura | Verde "Ya eres" | ❌ No |
| **Premium Expirado** | 1 | Pasada | Morado "Activa" | ✅ Sí |
| **Suscripción Pendiente** | 0 | NULL | Morado "Activa" | ⚠️ Mensaje |

---

## 🔧 Personalización de Mensajes

### **Cambiar el mensaje "Ya eres Premium"**

Edita `panel_vendedor.php` línea ~255:

```php
<h2 style="color: white; margin: 0; font-weight: 800; font-size: 1.8rem;">
    ¡Ya Eres Premium! 🎉
</h2>
<p style="color: rgba(255,255,255,0.95); margin: 0.5rem 0 0 0; font-size: 1.1rem;">
    Disfruta de todas las funciones exclusivas de tu membresía
</p>
```

### **Cambiar el mensaje de alerta**

Edita `panel_vendedor.php` línea ~15:

```php
case 'ya_premium':
    $mensaje = '¡Ya eres Premium! Disfruta de todos los beneficios de tu suscripción activa.';
    break;
```

---

## ✅ Checklist de Implementación

- [x] Banner "Ya eres Premium" (verde) para usuarios Premium
- [x] Banner "Activa Premium" (morado) para usuarios NO Premium
- [x] Mensaje de alerta "Ya eres Premium"
- [x] Mensaje de alerta "Suscripción Pendiente"
- [x] Mensaje de error "Error de Suscripción"
- [x] Validación en crear_pago_mp.php
- [x] Campos bloqueados con badge "Solo Premium"
- [x] Botón "Gestionar Suscripción" en banner verde
- [x] Contador de días restantes
- [x] Fecha de expiración visible

---

## 🎉 ¡Listo!

Tu sistema ahora muestra mensajes claros y profesionales según el estado de suscripción del usuario. Los usuarios Premium verán un banner verde celebrando su membresía, mientras que los usuarios NO Premium verán un banner morado invitándolos a activar Premium.

