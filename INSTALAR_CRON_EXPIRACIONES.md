# 🕐 Instalación del Cron Job - Revisar Expiraciones Premium

## 📋 Descripción

Este cron job ejecuta automáticamente el script `cron_revisar_expiraciones.php` una vez al día para:

✅ Desactivar Premium de usuarios cuya suscripción expiró
✅ Limpiar la base de datos
✅ Mantener sincronizados usuarios y tiendas
✅ Registrar logs de auditoría

---

## 🚀 Instalación

### **Paso 1: Crear la tabla de logs (Opcional)**

```bash
mysql -u root -p directorio_tiendas < crear_tabla_cron_logs.sql
```

O desde phpMyAdmin:
1. Selecciona la base de datos `directorio_tiendas`
2. Pestaña "SQL"
3. Copia y pega el contenido de `crear_tabla_cron_logs.sql`
4. Ejecutar

---

### **Paso 2: Verificar permisos del script**

```bash
# Dar permisos de ejecución
chmod +x cron_revisar_expiraciones.php

# Verificar que funciona manualmente
php cron_revisar_expiraciones.php
```

**Salida esperada:**
```
=== CRON: Revisando expiraciones de Premium ===
✅ No hay usuarios con Premium expirado
📊 Estadísticas: 5 usuarios Premium activos
=== CRON: Finalizado correctamente ===
```

---

### **Paso 3: Configurar el Cron Job**

#### **Opción A: Usando cPanel (Hosting compartido)**

1. Accede a tu cPanel
2. Busca "Cron Jobs" o "Tareas Cron"
3. Agregar nuevo cron job:
   - **Minuto:** 0
   - **Hora:** 2 (2:00 AM)
   - **Día del mes:** *
   - **Mes:** *
   - **Día de la semana:** *
   - **Comando:**
     ```bash
     /usr/bin/php /home/tuusuario/public_html/cron_revisar_expiraciones.php
     ```
4. Guardar

#### **Opción B: Usando crontab (VPS/Servidor dedicado)**

```bash
# Editar crontab
crontab -e

# Agregar esta línea al final:
0 2 * * * /usr/bin/php /var/www/html/cron_revisar_expiraciones.php >> /var/log/cron_expiraciones.log 2>&1

# Guardar y salir (Ctrl+X, luego Y, luego Enter)
```

**Explicación del comando:**
- `0 2 * * *` = Ejecutar a las 2:00 AM todos los días
- `/usr/bin/php` = Ruta al ejecutable de PHP
- `/var/www/html/cron_revisar_expiraciones.php` = Ruta completa al script
- `>> /var/log/cron_expiraciones.log` = Guardar logs en archivo
- `2>&1` = Redirigir errores al mismo archivo de log

#### **Opción C: Usando systemd timer (Linux moderno)**

```bash
# Crear archivo de servicio
sudo nano /etc/systemd/system/revisar-expiraciones.service
```

Contenido:
```ini
[Unit]
Description=Revisar expiraciones de Premium
After=network.target

[Service]
Type=oneshot
User=www-data
ExecStart=/usr/bin/php /var/www/html/cron_revisar_expiraciones.php
```

```bash
# Crear archivo de timer
sudo nano /etc/systemd/system/revisar-expiraciones.timer
```

Contenido:
```ini
[Unit]
Description=Ejecutar revisión de expiraciones diariamente
Requires=revisar-expiraciones.service

[Timer]
OnCalendar=daily
OnCalendar=02:00
Persistent=true

[Install]
WantedBy=timers.target
```

```bash
# Activar y iniciar el timer
sudo systemctl daemon-reload
sudo systemctl enable revisar-expiraciones.timer
sudo systemctl start revisar-expiraciones.timer

# Verificar estado
sudo systemctl status revisar-expiraciones.timer
```

---

### **Paso 4: Verificar que el Cron funciona**

#### **Método 1: Ejecutar manualmente**

```bash
php cron_revisar_expiraciones.php
```

#### **Método 2: Revisar logs del sistema**

```bash
# Ver logs de Apache/PHP
tail -f /var/log/apache2/error.log | grep "CRON"

# Ver logs del cron (si configuraste redirección)
tail -f /var/log/cron_expiraciones.log
```

#### **Método 3: Revisar la base de datos**

```sql
-- Ver ejecuciones del cron
SELECT * FROM cron_logs 
ORDER BY fecha_ejecucion DESC 
LIMIT 10;

-- Ver usuarios con Premium expirado
SELECT id, nombre, email, es_premium, fecha_expiracion_premium 
FROM usuarios 
WHERE fecha_expiracion_premium < NOW()
ORDER BY fecha_expiracion_premium DESC;
```

---

## 🧪 Pruebas

### **Prueba 1: Simular expiración**

```sql
-- Crear usuario de prueba con Premium expirado
UPDATE usuarios 
SET es_premium = 1, 
    fecha_expiracion_premium = '2024-01-01 00:00:00' 
WHERE id = TU_USER_ID;

-- Ejecutar el cron manualmente
-- php cron_revisar_expiraciones.php

-- Verificar que se desactivó
SELECT id, nombre, es_premium, fecha_expiracion_premium 
FROM usuarios 
WHERE id = TU_USER_ID;
```

### **Prueba 2: Verificar logs**

```sql
-- Ver última ejecución
SELECT * FROM cron_logs 
ORDER BY fecha_ejecucion DESC 
LIMIT 1;
```

---

## 📊 Horarios Recomendados

| Horario | Ventajas | Desventajas |
|---------|----------|-------------|
| **2:00 AM** | Poco tráfico, no afecta usuarios | - |
| **3:00 AM** | Muy poco tráfico | - |
| **12:00 AM** | Inicio del día | Puede haber tráfico nocturno |
| **6:00 AM** | Antes del horario laboral | Algunos usuarios madrugadores |

**Recomendación:** 2:00 AM o 3:00 AM

---

## 🔧 Configuración Avanzada

### **Ejecutar cada 12 horas**

```bash
# A las 2:00 AM y 2:00 PM
0 2,14 * * * /usr/bin/php /ruta/cron_revisar_expiraciones.php
```

### **Ejecutar cada 6 horas**

```bash
# A las 00:00, 06:00, 12:00, 18:00
0 */6 * * * /usr/bin/php /ruta/cron_revisar_expiraciones.php
```

### **Ejecutar solo los lunes**

```bash
# Lunes a las 2:00 AM
0 2 * * 1 /usr/bin/php /ruta/cron_revisar_expiraciones.php
```

---

## 🐛 Solución de Problemas

### **Problema: El cron no se ejecuta**

✅ **Solución:**
```bash
# 1. Verificar que el cron está configurado
crontab -l

# 2. Verificar logs del sistema
grep CRON /var/log/syslog

# 3. Verificar permisos
ls -la cron_revisar_expiraciones.php

# 4. Probar manualmente
php cron_revisar_expiraciones.php
```

### **Problema: Error de permisos**

✅ **Solución:**
```bash
# Dar permisos correctos
chmod 755 cron_revisar_expiraciones.php
chown www-data:www-data cron_revisar_expiraciones.php
```

### **Problema: No encuentra config.php**

✅ **Solución:**
```bash
# Usar ruta absoluta en el cron
0 2 * * * cd /var/www/html && /usr/bin/php cron_revisar_expiraciones.php
```

### **Problema: No se registran logs**

✅ **Solución:**
```sql
-- Verificar que la tabla existe
SHOW TABLES LIKE 'cron_logs';

-- Si no existe, crearla
SOURCE crear_tabla_cron_logs.sql;
```

---

## 📧 Notificaciones por Email (Opcional)

Puedes agregar notificaciones por email cuando expira una suscripción:

```php
// En cron_revisar_expiraciones.php, dentro del foreach:
mail(
    $usuario['email'],
    'Tu suscripción Premium ha expirado - Mercado Huasteco',
    "Hola {$usuario['nombre']},\n\n" .
    "Tu suscripción Premium ha expirado el {$usuario['fecha_expiracion_premium']}.\n\n" .
    "Para seguir disfrutando de los beneficios Premium, renueva tu suscripción:\n" .
    "https://mercadohuasteco.com/gestionar_suscripcion.php\n\n" .
    "Beneficios Premium:\n" .
    "✅ Fotos ilimitadas\n" .
    "✅ Video de presentación\n" .
    "✅ Estadísticas avanzadas\n" .
    "✅ Cupones y ofertas\n" .
    "✅ Insignia Premium\n\n" .
    "Saludos,\n" .
    "Equipo de Mercado Huasteco",
    'From: noreply@mercadohuasteco.com'
);
```

---

## 📈 Monitoreo y Estadísticas

### **Dashboard de Cron Jobs**

Puedes crear un archivo `admin_cron_logs.php` para ver las ejecuciones:

```php
<?php
require_once 'config.php';

if (!es_admin()) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->query("
    SELECT * FROM cron_logs 
    ORDER BY fecha_ejecucion DESC 
    LIMIT 50
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Historial de Cron Jobs</h2>
<table>
    <tr>
        <th>Script</th>
        <th>Fecha</th>
        <th>Usuarios Procesados</th>
        <th>Resultado</th>
    </tr>
    <?php foreach ($logs as $log): ?>
    <tr>
        <td><?= $log['script'] ?></td>
        <td><?= $log['fecha_ejecucion'] ?></td>
        <td><?= $log['usuarios_procesados'] ?></td>
        <td><?= $log['resultado'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
```

---

## ✅ Checklist de Instalación

- [ ] Tabla `cron_logs` creada (opcional)
- [ ] Script `cron_revisar_expiraciones.php` tiene permisos correctos
- [ ] Script probado manualmente y funciona
- [ ] Cron job configurado en cPanel/crontab
- [ ] Logs del sistema verificados
- [ ] Primera ejecución automática confirmada
- [ ] Notificaciones por email configuradas (opcional)

---

## 🎉 ¡Listo!

Tu sistema ahora tiene mantenimiento automático. Los usuarios que no renueven su suscripción perderán automáticamente el acceso Premium cuando expire su periodo de 30 días.

**Ventajas:**
✅ Automático - No requiere intervención manual
✅ Limpio - Mantiene la base de datos actualizada
✅ Justo - Los usuarios solo pagan por el tiempo que usan
✅ Auditable - Todos los cambios quedan registrados

