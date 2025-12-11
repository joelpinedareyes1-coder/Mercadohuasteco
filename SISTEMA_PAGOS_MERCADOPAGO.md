# 🚀 Sistema de Pagos con Mercado Pago - Membresía Premium

## 📋 Descripción General

Sistema completo de pagos integrado con Mercado Pago que permite a los vendedores activar su membresía Premium por **$150 MXN durante 30 días**.

---

## ✅ Archivos Creados

### 1. **Funciones Base**
- `funciones_config.php` - Actualizado con funciones:
  - `esPremiumActivo($fecha_expiracion)` - Verifica si el Premium está activo
  - `obtenerInfoUsuario($pdo, $user_id)` - Obtiene información completa del usuario

### 2. **Sistema de Pagos**
- `crear_pago_mp.php` - Crea la preferencia de pago en Mercado Pago
- `webhook_mercadopago.php` - Procesa las notificaciones de pago (IPN)
- `pago_exitoso.php` - Página de confirmación de pago exitoso
- `pago_fallido.php` - Página cuando el pago falla
- `pago_pendiente.php` - Página cuando el pago está pendiente

### 3. **Base de Datos**
- `agregar_campos_premium.sql` - Script SQL con:
  - Campos adicionales en tabla `usuarios`
  - Tabla `pagos_premium` para registrar transacciones
  - Tabla `webhook_logs` para auditoría

### 4. **Interfaz de Usuario**
- `panel_vendedor.php` - Actualizado con banner Premium atractivo

---

## 🔧 Instalación Paso a Paso

### **PASO 1: Ejecutar el Script SQL**

```bash
# Opción A: Desde línea de comandos
mysql -u root -p directorio_tiendas < agregar_campos_premium.sql

# Opción B: Desde phpMyAdmin
# 1. Abre phpMyAdmin
# 2. Selecciona la base de datos 'directorio_tiendas'
# 3. Ve a la pestaña "SQL"
# 4. Copia y pega el contenido de agregar_campos_premium.sql
# 5. Haz clic en "Continuar"
```

### **PASO 2: Obtener Credenciales de Mercado Pago**

1. **Crear cuenta en Mercado Pago** (si no tienes):
   - Ve a: https://www.mercadopago.com.mx/
   - Regístrate como vendedor

2. **Obtener credenciales de prueba** (para desarrollo):
   - Ve a: https://www.mercadopago.com.mx/developers/panel/app
   - Crea una aplicación
   - Ve a "Credenciales de prueba"
   - Copia:
     - `Access Token` (empieza con TEST-...)
     - `Public Key` (empieza con TEST-...)

3. **Obtener credenciales de producción** (para producción):
   - En el mismo panel, ve a "Credenciales de producción"
   - Copia:
     - `Access Token` (empieza con APP_USR-...)
     - `Public Key` (empieza con APP_USR-...)

### **PASO 3: Configurar las Credenciales**

Edita los siguientes archivos y reemplaza las credenciales:

#### **crear_pago_mp.php** (líneas 11-12):
```php
define('MP_ACCESS_TOKEN', 'TU_ACCESS_TOKEN_AQUI');
define('MP_PUBLIC_KEY', 'TU_PUBLIC_KEY_AQUI');
```

#### **webhook_mercadopago.php** (línea 6):
```php
define('MP_ACCESS_TOKEN', 'TU_ACCESS_TOKEN_AQUI');
```

**⚠️ IMPORTANTE:** 
- Para pruebas, usa las credenciales de TEST
- Para producción, usa las credenciales de producción
- **NUNCA** subas las credenciales a repositorios públicos

### **PASO 4: Configurar el Webhook en Mercado Pago**

1. Ve al panel de Mercado Pago: https://www.mercadopago.com.mx/developers/panel/app
2. Selecciona tu aplicación
3. Ve a "Webhooks"
4. Agrega una nueva URL de notificación:
   ```
   https://tudominio.com/webhook_mercadopago.php
   ```
5. Selecciona el evento: **"Pagos"**
6. Guarda

**Nota:** Para desarrollo local, puedes usar herramientas como:
- **ngrok**: https://ngrok.com/
- **localtunnel**: https://localtunnel.github.io/www/

Ejemplo con ngrok:
```bash
ngrok http 80
# Usa la URL generada: https://xxxxx.ngrok.io/webhook_mercadopago.php
```

---

## 🎯 Flujo de Funcionamiento

### **1. Usuario ve el Banner Premium**
- El vendedor entra a su panel (`panel_vendedor.php`)
- Si NO tiene Premium activo, ve un banner atractivo con:
  - Precio: $150 MXN/mes
  - Lista de beneficios
  - Botón "¡Activar Premium Ahora!"

### **2. Inicia el Pago**
- Usuario hace clic en el botón
- Se redirige a `crear_pago_mp.php`
- El sistema:
  1. Verifica que el usuario esté logueado
  2. Verifica que NO tenga Premium activo
  3. Crea una preferencia de pago en Mercado Pago
  4. Guarda el registro en la tabla `pagos_premium`
  5. Redirige al checkout de Mercado Pago

### **3. Usuario Paga**
- El usuario completa el pago en Mercado Pago
- Puede pagar con:
  - Tarjeta de crédito/débito
  - Transferencia bancaria
  - Efectivo (OXXO, 7-Eleven, etc.)
  - Mercado Pago wallet

### **4. Mercado Pago Notifica**
- Mercado Pago envía una notificación al webhook
- `webhook_mercadopago.php` recibe la notificación
- El sistema:
  1. Registra el webhook en `webhook_logs`
  2. Consulta el estado del pago a Mercado Pago
  3. Si el pago está aprobado:
     - Actualiza `usuarios.es_premium = 1`
     - Establece `fecha_expiracion_premium` a +30 días
     - Actualiza `fecha_ultimo_pago`
     - Marca el pago como aprobado en `pagos_premium`

### **5. Usuario es Redirigido**
- Según el resultado del pago:
  - **Exitoso** → `pago_exitoso.php` (auto-redirige al panel en 5 segundos)
  - **Fallido** → `pago_fallido.php` (opción de reintentar)
  - **Pendiente** → `pago_pendiente.php` (para pagos en efectivo)

---

## 🧪 Pruebas

### **Tarjetas de Prueba de Mercado Pago**

Para probar pagos en modo TEST, usa estas tarjetas:

#### ✅ **Pago Aprobado**
```
Número: 5031 7557 3453 0604
CVV: 123
Fecha: 11/25
Nombre: APRO
```

#### ❌ **Pago Rechazado**
```
Número: 5031 4332 1540 6351
CVV: 123
Fecha: 11/25
Nombre: OTHE
```

#### ⏳ **Pago Pendiente**
```
Número: 5031 4332 1540 6351
CVV: 123
Fecha: 11/25
Nombre: CONT
```

**Más tarjetas de prueba:** https://www.mercadopago.com.mx/developers/es/docs/checkout-api/testing

---

## 📊 Verificación del Sistema

### **1. Verificar que el Banner Aparece**
```php
// En panel_vendedor.php, el banner solo aparece si:
// 1. El usuario es vendedor
// 2. NO tiene Premium activo
```

### **2. Verificar Registro de Pagos**
```sql
-- Ver todos los pagos registrados
SELECT * FROM pagos_premium ORDER BY fecha_creacion DESC;

-- Ver pagos aprobados
SELECT * FROM pagos_premium WHERE status = 'approved';

-- Ver usuarios Premium activos
SELECT id, nombre, email, es_premium, fecha_expiracion_premium 
FROM usuarios 
WHERE es_premium = 1 
AND fecha_expiracion_premium > NOW();
```

### **3. Verificar Webhooks Recibidos**
```sql
-- Ver todos los webhooks
SELECT * FROM webhook_logs ORDER BY fecha_recepcion DESC LIMIT 10;

-- Ver webhooks procesados
SELECT * FROM webhook_logs WHERE procesado = 1;

-- Ver webhooks pendientes
SELECT * FROM webhook_logs WHERE procesado = 0;
```

---

## 🔒 Seguridad

### **Recomendaciones Importantes:**

1. **Proteger Credenciales**
   ```php
   // Mejor práctica: usar variables de entorno
   define('MP_ACCESS_TOKEN', getenv('MP_ACCESS_TOKEN'));
   ```

2. **Validar Webhooks**
   - El webhook actual acepta todas las notificaciones
   - Para producción, considera validar la firma del webhook
   - Documentación: https://www.mercadopago.com.mx/developers/es/docs/checkout-api/webhooks

3. **HTTPS Obligatorio**
   - Mercado Pago requiere HTTPS para webhooks en producción
   - Obtén un certificado SSL (Let's Encrypt es gratis)

4. **Logs de Auditoría**
   - Todos los webhooks se registran en `webhook_logs`
   - Revisa regularmente para detectar problemas

---

## 🐛 Solución de Problemas

### **Problema: El banner no aparece**
**Solución:**
```php
// Verificar que el usuario NO sea Premium
$stmt = $pdo->prepare("SELECT es_premium, fecha_expiracion_premium FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
var_dump($user); // Debe mostrar es_premium = 0 o fecha_expiracion_premium vencida
```

### **Problema: Error al crear el pago**
**Solución:**
1. Verifica las credenciales en `crear_pago_mp.php`
2. Revisa los logs de PHP: `tail -f /var/log/apache2/error.log`
3. Verifica que curl esté habilitado: `php -m | grep curl`

### **Problema: El webhook no se ejecuta**
**Solución:**
1. Verifica que la URL del webhook esté configurada en Mercado Pago
2. Verifica que el archivo `webhook_mercadopago.php` sea accesible públicamente
3. Revisa los logs: `SELECT * FROM webhook_logs ORDER BY fecha_recepcion DESC LIMIT 5;`
4. Prueba manualmente: `curl -X POST https://tudominio.com/webhook_mercadopago.php`

### **Problema: El Premium no se activa después del pago**
**Solución:**
```sql
-- Verificar si el webhook se recibió
SELECT * FROM webhook_logs WHERE payment_id = 'TU_PAYMENT_ID';

-- Verificar si el pago se registró
SELECT * FROM pagos_premium WHERE payment_id = 'TU_PAYMENT_ID';

-- Activar manualmente (temporal)
UPDATE usuarios 
SET es_premium = 1, 
    fecha_expiracion_premium = DATE_ADD(NOW(), INTERVAL 30 DAY),
    fecha_ultimo_pago = NOW()
WHERE id = TU_USER_ID;
```

---

## 📈 Mejoras Futuras

### **Funcionalidades Adicionales:**

1. **Renovación Automática**
   - Implementar suscripciones recurrentes
   - Usar Mercado Pago Subscriptions API

2. **Descuentos y Cupones**
   - Agregar códigos de descuento
   - Promociones especiales

3. **Múltiples Planes**
   - Plan Básico: $100/mes
   - Plan Premium: $150/mes
   - Plan Empresarial: $300/mes

4. **Notificaciones por Email**
   - Confirmar pago exitoso
   - Recordar renovación (5 días antes)
   - Notificar expiración

5. **Panel de Administración**
   - Ver todos los pagos
   - Estadísticas de ingresos
   - Gestionar suscripciones

---

## 📞 Soporte

### **Recursos Útiles:**

- **Documentación Mercado Pago:** https://www.mercadopago.com.mx/developers/es/docs
- **Comunidad Mercado Pago:** https://www.mercadopago.com.mx/developers/es/support
- **Status de Mercado Pago:** https://status.mercadopago.com/

### **Contacto:**

Si tienes problemas con la integración:
1. Revisa esta documentación
2. Consulta los logs del sistema
3. Revisa la documentación oficial de Mercado Pago
4. Contacta al soporte de Mercado Pago

---

## ✨ Características del Banner Premium

El banner incluye:
- ✅ Diseño atractivo con gradiente morado
- ✅ Icono de corona dorada
- ✅ Lista de 8 beneficios Premium
- ✅ Precio destacado: $150 MXN/30 días
- ✅ Botón llamativo con animación
- ✅ Indicador de pago seguro
- ✅ Responsive (se adapta a móviles)
- ✅ Solo visible para usuarios sin Premium activo

---

## 🎉 ¡Listo!

Tu sistema de pagos está completo y listo para usar. Los vendedores ahora pueden:
1. Ver el banner Premium en su panel
2. Hacer clic y pagar $150 MXN
3. Activar automáticamente su membresía Premium por 30 días
4. Disfrutar de todas las funciones exclusivas

**¡Felicidades! 🚀**
