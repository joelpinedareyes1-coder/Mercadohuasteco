# 🔧 Solución de Problemas - Sistema Premium

## 🚨 Problemas Comunes y Soluciones

---

## Problema 1: "Error al procesar tu suscripción"

### **Síntomas:**
- Al hacer clic en "Activar Premium" aparece error
- Mensaje: "Hubo un error al procesar tu suscripción"
- No redirige a Mercado Pago

### **Causa:**
Las credenciales de Mercado Pago no están configuradas o son inválidas.

### **Solución:**

#### **Opción A: Configurar Mercado Pago (Producción)**

1. Obtén tus credenciales en: https://www.mercadopago.com.mx/developers/panel/app

2. Edita `crear_pago_mp.php` líneas 13-18:
```php
// Reemplaza estos valores:
define('MP_ACCESS_TOKEN', 'APP_USR-1234567890-123456-abcdef...');
define('MP_PUBLIC_KEY', 'APP_USR-abcdef12-3456-7890-abcd...');
define('MP_PLAN_ID', '2c9380848e8e8e8e018e8e8e8e8e8e8e');
```

3. Edita `webhook_mercadopago.php` línea 7:
```php
define('MP_ACCESS_TOKEN', 'APP_USR-1234567890-123456-abcdef...');
```

4. Edita `gestionar_suscripcion.php` línea 11:
```php
define('MP_ACCESS_TOKEN', 'APP_USR-1234567890-123456-abcdef...');
```

#### **Opción B: Activar Premium Manualmente (Pruebas)**

Si solo quieres probar el sistema sin configurar Mercado Pago:

1. Accede a: `http://tudominio.com/activar_premium_prueba.php`
2. Selecciona los días de Premium (30 días recomendado)
3. Click en "Activar Premium Ahora"
4. Verifica que aparezca el banner verde en el panel

---

## Problema 2: Banner "Ya Eres Premium" no aparece

### **Síntomas:**
- Usuario tiene Premium activo en la BD
- Pero sigue viendo el banner morado "Activa Premium"
- No aparece el banner verde "Ya Eres Premium"

### **Diagnóstico:**

1. Accede a: `http://tudominio.com/diagnostico_premium.php`

2. Verifica estos valores:
   - **es_premium:** Debe ser `1 (Sí)`
   - **fecha_expiracion_premium:** Debe ser una fecha futura
   - **esPremiumActivo():** Debe ser `true`

### **Soluciones:**

#### **Solución 1: Verificar fecha de expiración**

```sql
-- Ver el estado actual
SELECT id, nombre, es_premium, fecha_expiracion_premium 
FROM usuarios 
WHERE id = TU_USER_ID;

-- Si la fecha es pasada o NULL, actualizar:
UPDATE usuarios 
SET es_premium = 1, 
    fecha_expiracion_premium = DATE_ADD(NOW(), INTERVAL 30 DAY)
WHERE id = TU_USER_ID;
```

#### **Solución 2: Usar el script de activación**

1. Ve a: `activar_premium_prueba.php`
2. Click en "Activar Premium Ahora"
3. Vuelve al panel del vendedor
4. Deberías ver el banner verde

#### **Solución 3: Verificar la función esPremiumActivo**

Edita `funciones_config.php` y verifica que la función exista:

```php
function esPremiumActivo($fecha_expiracion) {
    if (empty($fecha_expiracion)) {
        return false;
    }
    
    $fecha_actual = new DateTime();
    $fecha_exp = new DateTime($fecha_expiracion);
    
    return $fecha_exp > $fecha_actual;
}
```

#### **Solución 4: Limpiar caché del navegador**

1. Presiona `Ctrl + Shift + R` (Windows/Linux)
2. O `Cmd + Shift + R` (Mac)
3. Recarga la página

---

## Problema 3: Webhook no activa Premium automáticamente

### **Síntomas:**
- Usuario paga en Mercado Pago
- Pago aparece como aprobado
- Pero Premium no se activa

### **Diagnóstico:**

1. Verifica logs del webhook:
```sql
SELECT * FROM webhook_logs 
ORDER BY fecha_recepcion DESC 
LIMIT 10;
```

2. Verifica que el webhook esté configurado en Mercado Pago:
   - URL: `https://tudominio.com/webhook_mercadopago.php`
   - Eventos: `payment` y `subscription_preapproval`

### **Soluciones:**

#### **Solución 1: Verificar URL del webhook**

```bash
# Prueba que el webhook sea accesible
curl https://tudominio.com/webhook_mercadopago.php

# Debe responder con HTTP 200
```

#### **Solución 2: Verificar logs del servidor**

```bash
# Ver logs de Apache
tail -f /var/log/apache2/error.log | grep "WEBHOOK"

# Busca líneas como:
# "=== WEBHOOK MERCADO PAGO RECIBIDO ==="
# "✅ ÉXITO: Premium activado/extendido..."
```

#### **Solución 3: Probar webhook manualmente**

```bash
# Envía un webhook de prueba
curl -X POST https://tudominio.com/webhook_mercadopago.php \
  -H "Content-Type: application/json" \
  -d '{
    "type": "payment",
    "data": {
      "id": "123456789"
    }
  }'
```

#### **Solución 4: Verificar credenciales en webhook**

Edita `webhook_mercadopago.php` línea 7 y asegúrate de que el `MP_ACCESS_TOKEN` sea correcto.

---

## Problema 4: Campos Premium aparecen bloqueados aunque soy Premium

### **Síntomas:**
- Usuario es Premium
- Campos como WhatsApp, Facebook, etc. aparecen deshabilitados
- Badge dice "🔒 Solo Premium"

### **Solución:**

El problema está en la verificación de Premium en el formulario.

Edita `panel_vendedor.php` y busca esta sección (alrededor de línea 400):

```php
<?php
$stmt_premium = $pdo->prepare("SELECT es_premium FROM usuarios WHERE id = ?");
$stmt_premium->execute([$_SESSION['user_id']]);
$usuario_premium = $stmt_premium->fetch(PDO::FETCH_ASSOC);
$es_premium = $usuario_premium && $usuario_premium['es_premium'] == 1;
?>
```

Cámbialo por:

```php
<?php
// Usar la función esPremiumActivo para verificar Premium
$usuario_info_form = obtenerInfoUsuario($pdo, $_SESSION['user_id']);
$es_premium = $usuario_info_form && esPremiumActivo($usuario_info_form['fecha_expiracion_premium']);
?>
```

---

## Problema 5: Cron Job no desactiva Premium expirado

### **Síntomas:**
- Usuario tiene fecha de expiración pasada
- Pero `es_premium` sigue siendo `1`
- Premium no se desactiva automáticamente

### **Diagnóstico:**

```bash
# Verificar si el cron está configurado
crontab -l | grep "revisar_expiraciones"

# Verificar logs del cron
SELECT * FROM cron_logs 
ORDER BY fecha_ejecucion DESC 
LIMIT 10;
```

### **Soluciones:**

#### **Solución 1: Ejecutar manualmente**

```bash
php cron_revisar_expiraciones.php
```

#### **Solución 2: Configurar el cron job**

```bash
# Editar crontab
crontab -e

# Agregar esta línea (ejecutar a las 2:00 AM diariamente)
0 2 * * * /usr/bin/php /var/www/html/cron_revisar_expiraciones.php
```

#### **Solución 3: Desactivar manualmente**

```sql
-- Desactivar Premium expirado
UPDATE usuarios 
SET es_premium = 0 
WHERE es_premium = 1 
AND fecha_expiracion_premium < NOW();

-- Desactivar tiendas asociadas
UPDATE tiendas t
INNER JOIN usuarios u ON t.vendedor_id = u.id
SET t.es_premium = 0
WHERE u.es_premium = 0;
```

---

## Problema 6: Error "Usuario no encontrado"

### **Síntomas:**
- Al acceder a `crear_pago_mp.php` aparece: "Error: Usuario no encontrado"

### **Solución:**

Verifica que la función `obtenerInfoUsuario` exista en `funciones_config.php`:

```php
function obtenerInfoUsuario($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener info de usuario: " . $e->getMessage());
        return false;
    }
}
```

---

## Problema 7: Tablas no existen

### **Síntomas:**
- Error: "Table 'directorio_tiendas.suscripciones_premium' doesn't exist"
- Error: "Table 'directorio_tiendas.webhook_logs' doesn't exist"

### **Solución:**

Ejecuta el script SQL:

```bash
mysql -u root -p directorio_tiendas < agregar_campos_premium.sql
```

O desde phpMyAdmin:
1. Selecciona la base de datos `directorio_tiendas`
2. Pestaña "SQL"
3. Copia y pega el contenido de `agregar_campos_premium.sql`
4. Ejecutar

---

## 🛠️ Herramientas de Diagnóstico

### **1. Diagnóstico Completo**
```
http://tudominio.com/diagnostico_premium.php
```
Muestra el estado completo del sistema Premium.

### **2. Activar Premium Manual**
```
http://tudominio.com/activar_premium_prueba.php
```
Activa/desactiva Premium sin Mercado Pago (solo para pruebas).

### **3. Verificar Base de Datos**
```sql
-- Estado del usuario
SELECT id, nombre, es_premium, fecha_expiracion_premium 
FROM usuarios 
WHERE id = TU_USER_ID;

-- Suscripciones
SELECT * FROM suscripciones_premium 
WHERE usuario_id = TU_USER_ID;

-- Pagos
SELECT * FROM pagos_suscripcion 
WHERE usuario_id = TU_USER_ID;

-- Webhooks recibidos
SELECT * FROM webhook_logs 
ORDER BY fecha_recepcion DESC 
LIMIT 10;
```

---

## 📋 Checklist de Verificación

Antes de reportar un problema, verifica:

- [ ] Las tablas de la base de datos existen
- [ ] El usuario está logueado como vendedor
- [ ] La función `esPremiumActivo()` existe en `funciones_config.php`
- [ ] La función `obtenerInfoUsuario()` existe en `funciones_config.php`
- [ ] El archivo `panel_vendedor.php` tiene los mensajes de la URL
- [ ] Las credenciales de Mercado Pago están configuradas (si usas MP)
- [ ] El webhook está configurado en Mercado Pago (si usas MP)
- [ ] El cron job está configurado (para desactivación automática)

---

## 🆘 Soporte

Si ninguna de estas soluciones funciona:

1. Ejecuta `diagnostico_premium.php` y guarda los resultados
2. Revisa los logs del servidor: `tail -f /var/log/apache2/error.log`
3. Verifica la consola del navegador (F12) para errores JavaScript
4. Comparte la información recopilada para obtener ayuda

---

## ✅ Solución Rápida (Modo Prueba)

Si solo quieres probar el sistema rápidamente:

1. **Accede a:** `activar_premium_prueba.php`
2. **Click en:** "Activar Premium Ahora" (30 días)
3. **Ve a:** `panel_vendedor.php`
4. **Deberías ver:** Banner verde "Ya Eres Premium"
5. **Verifica:** Campos desbloqueados (WhatsApp, redes sociales, etc.)

¡Listo! El sistema está funcionando. Ahora puedes configurar Mercado Pago para pagos reales.

