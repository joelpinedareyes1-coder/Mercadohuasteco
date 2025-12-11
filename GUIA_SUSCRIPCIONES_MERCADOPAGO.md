# 🔄 Sistema de Suscripciones con Mercado Pago - Cobro Automático Mensual

## 📋 Descripción General

Sistema completo de **suscripciones recurrentes** con Mercado Pago que permite a los vendedores activar su membresía Premium por **$150 MXN/mes con renovación automática**.

---

## ✅ Archivos del Sistema

### 1. **Base de Datos**
- `agregar_campos_premium.sql` - Tablas actualizadas:
  - `suscripciones_premium` - Registro de suscripciones
  - `pagos_suscripcion` - Historial de pagos mensuales
  - `webhook_logs` - Auditoría de notificaciones

### 2. **Sistema de Suscripciones**
- `crear_pago_mp.php` - Crea la suscripción en Mercado Pago
- `webhook_mercadopago.php` - Procesa notificaciones de pagos y suscripciones
- `gestionar_suscripcion.php` - Panel para ver y cancelar suscripción
- `pago_exitoso.php` - Confirmación de suscripción exitosa
- `pago_fallido.php` - Página de error
- `pago_pendiente.php` - Página de pago pendiente

### 3. **Funciones**
- `funciones_config.php` - Funciones auxiliares:
  - `esPremiumActivo($fecha_expiracion)`
  - `obtenerInfoUsuario($pdo, $user_id)`

---

## 🚀 Instalación Completa

### **PASO 1: Ejecutar el Script SQL**

```bash
# Desde línea de comandos
mysql -u root -p directorio_tiendas < agregar_campos_premium.sql

# O desde phpMyAdmin:
# 1. Selecciona la base de datos 'directorio_tiendas'
# 2. Pestaña "SQL"
# 3. Copia y pega el contenido de agregar_campos_premium.sql
# 4. Ejecutar
```

---

### **PASO 2: Crear Plan de Suscripción en Mercado Pago**

#### **Opción A: Crear Plan desde el Panel Web** (Recomendado)

1. **Inicia sesión en Mercado Pago:**
   - Ve a: https://www.mercadopago.com.mx/

2. **Accede a tu negocio:**
   - Menú → "Tu negocio" → "Suscripciones"
   - O directamente: https://www.mercadopago.com.mx/subscriptions

3. **Crear nuevo plan:**
   - Clic en "Crear plan de suscripción"
   - Completa los datos:
     - **Nombre:** Plan Premium - Mercado Huasteco
     - **Descripción:** Acceso Premium con todas las funciones
     - **Precio:** $150.00 MXN
     - **Frecuencia:** Mensual (cada 1 mes)
     - **Duración:** Sin límite (renovación automática)

4. **Guardar y obtener ID:**
   - Guarda el plan
   - Copia el **ID del Plan** (ej: `2c9380848e8e8e8e018e8e8e8e8e8e8e`)
   - Este ID lo necesitarás en el PASO 3

#### **Opción B: Crear Plan por API** (Avanzado)

```bash
curl -X POST \
  'https://api.mercadopago.com/preapproval_plan' \
  -H 'Authorization: Bearer TU_ACCESS_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "reason": "Plan Premium - Mercado Huasteco",
    "auto_recurring": {
      "frequency": 1,
      "frequency_type": "months",
      "transaction_amount": 150,
      "currency_id": "MXN"
    },
    "back_url": "https://tudominio.com/pago_exitoso.php"
  }'
```

**Respuesta:**
```json
{
  "id": "2c9380848e8e8e8e018e8e8e8e8e8e8e",
  "reason": "Plan Premium - Mercado Huasteco",
  ...
}
```

Guarda el `id` del plan.

---

### **PASO 3: Configurar Credenciales**

#### **3.1 Obtener Credenciales de Mercado Pago**

1. Ve a: https://www.mercadopago.com.mx/developers/panel/app
2. Crea una aplicación (si no tienes)
3. Obtén las credenciales:

**Para Pruebas (TEST):**
- Access Token: `TEST-1234567890-123456-abcdef...`
- Public Key: `TEST-abcdef12-3456-7890-abcd-ef1234567890`

**Para Producción:**
- Access Token: `APP_USR-1234567890-123456-abcdef...`
- Public Key: `APP_USR-abcdef12-3456-7890-abcd-ef1234567890`

#### **3.2 Configurar en los Archivos**

**Archivo: `crear_pago_mp.php`** (líneas 11-16)
```php
// Credenciales de Mercado Pago
define('MP_ACCESS_TOKEN', 'TU_ACCESS_TOKEN_AQUI');
define('MP_PUBLIC_KEY', 'TU_PUBLIC_KEY_AQUI');

// ID del Plan creado en el Paso 2
define('MP_PLAN_ID', '2c9380848e8e8e8e018e8e8e8e8e8e8e');
```

**Archivo: `webhook_mercadopago.php`** (línea 6)
```php
define('MP_ACCESS_TOKEN', 'TU_ACCESS_TOKEN_AQUI');
```

**Archivo: `gestionar_suscripcion.php`** (línea 11)
```php
define('MP_ACCESS_TOKEN', 'TU_ACCESS_TOKEN_AQUI');
```

---

### **PASO 4: Configurar Webhooks en Mercado Pago** ⚡

**Este es el paso MÁS IMPORTANTE.** El webhook es el "cerebro automático" que recibe las confirmaciones de Mercado Pago y activa el Premium.

#### **4.1 ¿Qué es un Webhook?**

Un webhook es una URL en tu servidor que Mercado Pago llama automáticamente cada vez que ocurre un evento importante:
- ✅ Un pago fue aprobado
- ❌ Un pago fue rechazado
- 🔄 Una suscripción fue cancelada
- 📅 Se renovó una suscripción

#### **4.2 Configurar en el Panel de Mercado Pago**

1. Ve a: https://www.mercadopago.com.mx/developers/panel/app
2. Selecciona tu aplicación
3. Ve a "Webhooks" o "Notificaciones IPN"
4. Agrega una nueva URL:
   ```
   https://mercadohuasteco.com/webhook_mercadopago.php
   ```
5. Selecciona los eventos:
   - ✅ **Pagos** (payment) ← **IMPORTANTE**
   - ✅ **Suscripciones** (subscription_preapproval)
6. Guarda

#### **4.3 Lógica del Webhook (Ya implementada en `webhook_mercadopago.php`)**

El archivo `webhook_mercadopago.php` ya está configurado y hace lo siguiente:

```php
<?php
// 1. Recibe la notificación de Mercado Pago
$notificacion = json_decode(file_get_contents('php://input'), true);

// 2. Verifica que sea un pago de suscripción aprobado
if ($notificacion['type'] == 'payment' && $notificacion['data']['status'] == 'approved') {
    
    // 3. Obtiene los datos del pago desde la API de Mercado Pago
    $pago = $mp_sdk->payment()->get($notificacion['data']['id']);
    
    // 4. Saca la ID del usuario que guardamos en external_reference
    $id_usuario_que_pago = $pago['external_reference'];
    
    // 5. ¡MAGIA! Activa el Premium por 30 días
    $conexion->query("
        UPDATE usuarios 
        SET es_premium = 1, 
            fecha_expiracion_premium = NOW() + INTERVAL 30 DAY 
        WHERE id = $id_usuario_que_pago
    ");
}

// 6. Le dice a Mercado Pago que recibió bien la info
http_response_code(200);
?>
```

**¿Qué hace cada parte?**

1. **Recibe el JSON:** Mercado Pago envía un JSON con información del evento
2. **Verifica el tipo:** Solo procesa si es un pago (`payment`) aprobado (`approved`)
3. **Consulta detalles:** Obtiene información completa del pago desde la API
4. **Identifica al usuario:** Usa el `external_reference` que guardamos al crear la suscripción
5. **Activa Premium:** Actualiza la base de datos para dar 30 días de Premium
6. **Confirma recepción:** Responde con HTTP 200 para que Mercado Pago sepa que todo está OK

#### **4.4 Para Desarrollo Local (ngrok)**

Si estás desarrollando en local, necesitas exponer tu servidor:

```bash
# Instalar ngrok (si no lo tienes)
# https://ngrok.com/download

# Exponer tu servidor local
ngrok http 80

# Salida:
# Forwarding: https://abc123.ngrok.io -> http://localhost:80
```

Usa la URL de ngrok en el webhook:
```
https://abc123.ngrok.io/webhook_mercadopago.php
```

#### **4.5 Verificar que el Webhook Funciona**

Después de configurar el webhook, puedes verificar que funciona:

**Opción 1: Revisar logs del servidor**
```bash
tail -f /var/log/apache2/error.log
# Busca líneas como:
# "=== WEBHOOK MERCADO PAGO RECIBIDO ==="
# "✅ ÉXITO: Premium activado/extendido para usuario..."
```

**Opción 2: Revisar la base de datos**
```sql
-- Ver webhooks recibidos
SELECT * FROM webhook_logs 
ORDER BY fecha_recepcion DESC 
LIMIT 10;

-- Ver si se procesaron correctamente
SELECT * FROM webhook_logs 
WHERE procesado = 1 
ORDER BY fecha_recepcion DESC;
```

**Opción 3: Hacer una prueba real**
1. Crea una suscripción con tarjeta de prueba
2. Espera 30 segundos
3. Verifica en la BD:
```sql
SELECT id, nombre, es_premium, fecha_expiracion_premium 
FROM usuarios 
WHERE id = TU_USER_ID;
```

#### **4.6 Troubleshooting del Webhook**

**Problema: "El webhook no se ejecuta"**

✅ **Solución:**
```bash
# 1. Verifica que el archivo sea accesible
curl https://mercadohuasteco.com/webhook_mercadopago.php

# 2. Verifica permisos
chmod 644 webhook_mercadopago.php

# 3. Verifica logs de Apache
tail -f /var/log/apache2/error.log

# 4. Prueba manualmente enviando un JSON
curl -X POST https://mercadohuasteco.com/webhook_mercadopago.php \
  -H "Content-Type: application/json" \
  -d '{"type":"payment","data":{"id":"123456"}}'
```

**Problema: "Webhook se ejecuta pero no activa Premium"**

✅ **Solución:**
```sql
-- Verifica que el webhook se recibió
SELECT * FROM webhook_logs WHERE tipo = 'payment' ORDER BY fecha_recepcion DESC LIMIT 5;

-- Verifica que el pago se registró
SELECT * FROM pagos_suscripcion ORDER BY fecha_pago DESC LIMIT 5;

-- Verifica el estado del usuario
SELECT id, nombre, es_premium, fecha_expiracion_premium FROM usuarios WHERE id = TU_USER_ID;
```

**Problema: "Error: external_reference no encontrado"**

✅ **Solución:**
- Verifica que en `crear_pago_mp.php` estés guardando el `user_id` en `external_reference`
- Debe ser: `'external_reference' => (string)$_SESSION['user_id']`

---

### **PASO 5: Configurar el Cron Job de Mantenimiento** 🕐

**¿Por qué necesitamos esto?**

La función `esPremiumActivo()` ya hace que los usuarios pierdan los beneficios Premium visualmente cuando expira su suscripción. Pero para mantener la base de datos limpia y actualizada, necesitamos un script que se ejecute automáticamente.

#### **5.1 ¿Qué hace el Cron Job?**

El script `cron_revisar_expiraciones.php` se ejecuta **1 vez al día** (recomendado a las 2:00 AM) y:

1. ✅ Busca usuarios con `es_premium = 1` y `fecha_expiracion_premium < NOW()`
2. ✅ Actualiza `es_premium = 0` para esos usuarios
3. ✅ Desactiva el Premium de sus tiendas asociadas
4. ✅ Registra logs de auditoría
5. ✅ (Opcional) Envía emails de notificación

#### **5.2 Lógica del Script**

```php
<?php
// Conexión a BD
require_once 'config.php';

// Busca usuarios premium cuya fecha de expiración ya pasó
$stmt = $pdo->query("
    UPDATE usuarios 
    SET es_premium = 0 
    WHERE es_premium = 1 
    AND fecha_expiracion_premium < NOW()
");

$usuarios_actualizados = $stmt->rowCount();
error_log("Premium desactivado para $usuarios_actualizados usuarios");
?>
```

#### **5.3 Configurar el Cron Job**

**Opción A: cPanel (Hosting compartido)**

1. Accede a tu cPanel
2. Busca "Cron Jobs"
3. Agregar nuevo:
   - **Hora:** 2:00 AM
   - **Comando:**
     ```bash
     /usr/bin/php /home/tuusuario/public_html/cron_revisar_expiraciones.php
     ```

**Opción B: crontab (VPS/Servidor)**

```bash
# Editar crontab
crontab -e

# Agregar esta línea (ejecutar a las 2:00 AM diariamente)
0 2 * * * /usr/bin/php /var/www/html/cron_revisar_expiraciones.php
```

**Opción C: Ejecutar manualmente (para pruebas)**

```bash
php cron_revisar_expiraciones.php
```

#### **5.4 Verificar que funciona**

```sql
-- Ver usuarios con Premium expirado
SELECT id, nombre, es_premium, fecha_expiracion_premium 
FROM usuarios 
WHERE fecha_expiracion_premium < NOW();

-- Después de ejecutar el cron, deberían tener es_premium = 0
```

**Ver guía completa:** `INSTALAR_CRON_EXPIRACIONES.md`

---

## 🎯 Flujo Completo del Sistema

### **1. Usuario Ve el Banner Premium**
```
panel_vendedor.php
↓
Banner solo visible si NO tiene Premium activo
↓
Botón: "¡Activar Premium Ahora!"
```

### **2. Crear Suscripción**
```
Usuario hace clic → crear_pago_mp.php
↓
Verifica: ¿Ya tiene suscripción activa? → NO
↓
Crea suscripción en Mercado Pago API
↓
Guarda en tabla: suscripciones_premium
↓
Redirige a: Checkout de Mercado Pago
```

### **3. Usuario Autoriza la Suscripción**
```
Usuario en Mercado Pago
↓
Ingresa datos de pago (tarjeta)
↓
Autoriza cobro recurrente mensual
↓
Mercado Pago procesa
```

### **4. Primer Pago (Inmediato)**
```
Mercado Pago cobra $150 MXN
↓
Envía webhook: type = "payment"
↓
webhook_mercadopago.php recibe
↓
Consulta estado del pago
↓
Si aprobado:
  - Guarda en: pagos_suscripcion
  - Actualiza: usuarios.es_premium = 1
  - Establece: fecha_expiracion_premium = +30 días
```

### **5. Pagos Mensuales Automáticos**
```
Cada 30 días:
↓
Mercado Pago cobra automáticamente $150 MXN
↓
Envía webhook: type = "payment"
↓
webhook_mercadopago.php recibe
↓
Si aprobado:
  - Guarda pago en: pagos_suscripcion
  - Extiende: fecha_expiracion_premium +30 días
```

### **6. Usuario Gestiona Suscripción**
```
gestionar_suscripcion.php
↓
Ver estado, historial de pagos
↓
Opción: Cancelar suscripción
↓
Si cancela:
  - Actualiza en Mercado Pago
  - Marca como: status = 'cancelled'
  - Premium sigue activo hasta fecha_expiracion_premium
  - No más cobros automáticos
```

---

## 🧪 Pruebas del Sistema

### **Tarjetas de Prueba**

**✅ Suscripción Aprobada:**
```
Número: 5031 7557 3453 0604
CVV: 123
Fecha: 11/25
Nombre: APRO
```

**❌ Suscripción Rechazada:**
```
Número: 5031 4332 1540 6351
CVV: 123
Fecha: 11/25
Nombre: OTHE
```

### **Simular Pago Mensual**

Para probar pagos recurrentes en TEST:

1. Crea una suscripción con tarjeta de prueba
2. En el panel de Mercado Pago TEST, ve a "Suscripciones"
3. Busca tu suscripción
4. Usa la opción "Simular cobro" para probar el webhook

---

## 📊 Verificación del Sistema

### **1. Verificar Suscripción Creada**
```sql
SELECT * FROM suscripciones_premium 
WHERE usuario_id = TU_USER_ID 
ORDER BY fecha_creacion DESC;
```

### **2. Verificar Pagos Recibidos**
```sql
SELECT * FROM pagos_suscripcion 
WHERE usuario_id = TU_USER_ID 
ORDER BY fecha_pago DESC;
```

### **3. Verificar Premium Activo**
```sql
SELECT id, nombre, email, es_premium, fecha_expiracion_premium 
FROM usuarios 
WHERE id = TU_USER_ID;
```

### **4. Verificar Webhooks**
```sql
-- Últimos webhooks recibidos
SELECT * FROM webhook_logs 
ORDER BY fecha_recepcion DESC 
LIMIT 10;

-- Webhooks procesados
SELECT * FROM webhook_logs 
WHERE procesado = 1;
```

---

## 🔧 Diferencias: Suscripción vs Pago Único

| Característica | Pago Único | Suscripción |
|----------------|------------|-------------|
| **Cobro** | Manual cada mes | Automático cada mes |
| **API Endpoint** | `/checkout/preferences` | `/preapproval` |
| **Renovación** | Usuario debe pagar de nuevo | Automática |
| **Cancelación** | No aplica | Usuario puede cancelar |
| **Webhook** | `payment` | `payment` + `subscription_preapproval` |
| **Tabla BD** | `pagos_premium` | `suscripciones_premium` + `pagos_suscripcion` |

---

## 🐛 Solución de Problemas

### **Problema: Error al crear suscripción**
```
Error: "preapproval_plan_id is invalid"
```

**Solución:**
1. Verifica que el `MP_PLAN_ID` sea correcto
2. Verifica que el plan exista en tu cuenta de Mercado Pago
3. Si no tienes plan, comenta estas líneas en `crear_pago_mp.php`:
```php
// if (MP_PLAN_ID !== 'TU_PLAN_ID_DE_MERCADO_PAGO') {
//     $subscription_data['preapproval_plan_id'] = MP_PLAN_ID;
// }
```

### **Problema: Webhook no se ejecuta**

**Solución:**
1. Verifica la URL del webhook en Mercado Pago
2. Verifica que el archivo sea accesible: `curl https://tudominio.com/webhook_mercadopago.php`
3. Revisa logs: `tail -f /var/log/apache2/error.log`
4. Verifica en la BD: `SELECT * FROM webhook_logs ORDER BY fecha_recepcion DESC LIMIT 5;`

### **Problema: Premium no se renueva automáticamente**

**Solución:**
```sql
-- Verificar si el webhook de pago se recibió
SELECT * FROM webhook_logs 
WHERE tipo = 'payment' 
ORDER BY fecha_recepcion DESC 
LIMIT 5;

-- Verificar si el pago se registró
SELECT * FROM pagos_suscripcion 
ORDER BY fecha_pago DESC 
LIMIT 5;

-- Extender manualmente (temporal)
UPDATE usuarios 
SET fecha_expiracion_premium = DATE_ADD(fecha_expiracion_premium, INTERVAL 30 DAY)
WHERE id = TU_USER_ID;
```

---

## 🔒 Seguridad y Mejores Prácticas

### **1. Proteger Credenciales**
```php
// Usar variables de entorno
define('MP_ACCESS_TOKEN', getenv('MP_ACCESS_TOKEN'));
define('MP_PLAN_ID', getenv('MP_PLAN_ID'));
```

### **2. Validar Webhooks**
```php
// Verificar que la petición venga de Mercado Pago
$x_signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$x_request_id = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';

// Validar firma (implementar según documentación de MP)
```

### **3. HTTPS Obligatorio**
- Mercado Pago requiere HTTPS para webhooks en producción
- Obtén certificado SSL gratuito: https://letsencrypt.org/

### **4. Logs de Auditoría**
- Todos los webhooks se registran en `webhook_logs`
- Revisa regularmente para detectar problemas

---

## 📈 Funcionalidades Adicionales

### **1. Notificaciones por Email**
```php
// Enviar email cuando se activa Premium
mail(
    $usuario['email'],
    'Bienvenido a Premium',
    'Tu suscripción Premium ha sido activada...'
);
```

### **2. Recordatorios de Renovación**
```php
// Cron job diario para verificar próximas renovaciones
// Enviar email 3 días antes de la renovación
```

### **3. Manejo de Pagos Fallidos**
```php
// Si un pago mensual falla:
// - Enviar email al usuario
// - Dar 3 días de gracia
// - Desactivar Premium si no se resuelve
```

### **4. Panel de Administración**
```php
// Ver todas las suscripciones activas
// Estadísticas de ingresos mensuales
// Cancelar suscripciones manualmente
```

---

## 📞 Recursos Útiles

- **Documentación Suscripciones:** https://www.mercadopago.com.mx/developers/es/docs/subscriptions/integration-configuration/subscription-creation
- **API Reference:** https://www.mercadopago.com.mx/developers/es/reference/subscriptions/_preapproval/post
- **Webhooks:** https://www.mercadopago.com.mx/developers/es/docs/subscriptions/additional-content/notifications
- **Soporte:** https://www.mercadopago.com.mx/developers/es/support

---

## ✨ Ventajas del Sistema de Suscripciones

✅ **Cobro automático mensual** - No requiere intervención del usuario
✅ **Renovación automática** - Premium se extiende automáticamente
✅ **Gestión fácil** - Usuario puede cancelar cuando quiera
✅ **Historial completo** - Registro de todos los pagos
✅ **Ingresos predecibles** - Flujo de caja constante
✅ **Mejor experiencia** - Usuario no tiene que recordar pagar

---

## 🎉 ¡Sistema Completo!

Tu sistema de suscripciones está listo. Los vendedores ahora pueden:

1. ✅ Suscribirse a Premium por $150 MXN/mes
2. ✅ Pago automático cada 30 días
3. ✅ Ver historial de pagos
4. ✅ Cancelar cuando quieran
5. ✅ Disfrutar de todas las funciones Premium

**¡Felicidades! 🚀**
