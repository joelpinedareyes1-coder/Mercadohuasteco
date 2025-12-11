# 🚀 Instrucciones Rápidas - Sistema de Ofertas Públicas

## ✅ ¿Qué se ha creado?

Una nueva página pública **ofertas.php** que muestra todas las ofertas activas de tiendas Premium en un solo lugar.

---

## 📦 Archivos Creados

1. **ofertas.php** - Página principal
2. **css/ofertas-styles.css** - Estilos
3. **includes/header.php** - Actualizado con enlace "Ofertas"
4. **test_ofertas_sistema.php** - Script de prueba
5. **datos_prueba_ofertas.sql** - Datos de ejemplo
6. **SISTEMA_OFERTAS_PUBLICAS.md** - Documentación completa

---

## 🔧 Instalación y Prueba

### Paso 1: Verificar que todo esté instalado
```bash
# Accede al script de prueba en tu navegador:
http://tu-sitio.com/test_ofertas_sistema.php
```

Este script verificará:
- ✅ Que la tabla `cupones_ofertas` exista
- ✅ Que haya tiendas Premium
- ✅ Que la consulta SQL funcione
- ✅ Que los archivos estén en su lugar

### Paso 2: Crear datos de prueba (opcional)

Si no tienes ofertas aún, puedes:

**Opción A: Hacer Premium a un usuario existente**
```sql
-- En phpMyAdmin o tu cliente MySQL:
UPDATE usuarios SET es_premium = 1 WHERE id = 1;
```

**Opción B: Insertar ofertas de prueba**
```sql
-- Ejecuta el archivo datos_prueba_ofertas.sql
-- Ajusta los IDs de tienda según tu base de datos
```

### Paso 3: Acceder a la página
```
http://tu-sitio.com/ofertas.php
```

---

## 🎯 Uso del Sistema

### Para Clientes:
1. Hacer clic en **"Ofertas"** en el menú principal
2. Ver todas las ofertas disponibles
3. Filtrar por categoría si lo desean
4. Hacer clic en **"Ver Tienda"** para más detalles

### Para Vendedores Premium:
1. Crear ofertas desde su panel de vendedor
2. Las ofertas aparecen automáticamente en `ofertas.php`
3. Solo las ofertas activas y no expiradas se muestran

### Para Administradores:
- Las ofertas se gestionan desde el panel de cada vendedor
- Solo vendedores Premium pueden crear ofertas
- Las ofertas expiran automáticamente según la fecha

---

## 🔍 Consulta SQL Utilizada

```sql
SELECT 
    c.titulo,
    c.descripcion,
    c.fecha_expiracion,
    t.nombre_tienda,
    t.logo,
    t.id as tienda_id
FROM cupones_ofertas c
INNER JOIN tiendas t ON c.id_tienda = t.id
INNER JOIN usuarios u ON t.vendedor_id = u.id
WHERE c.estado = 'activo'
AND (c.fecha_expiracion IS NULL OR c.fecha_expiracion >= CURDATE())
AND t.activo = 1
AND u.es_premium = 1
ORDER BY c.id DESC;
```

**Filtros aplicados:**
- ✅ Solo ofertas con estado 'activo'
- ✅ Solo ofertas no expiradas (fecha >= hoy)
- ✅ Solo tiendas activas
- ✅ Solo usuarios Premium
- ✅ Ordenadas por más recientes primero

---

## 🎨 Características del Diseño

### Cards de Ofertas incluyen:
- ✅ Imagen de la tienda
- ✅ Badge "OFERTA" animado
- ✅ Título y descripción
- ✅ Logo y nombre de la tienda
- ✅ Badge Premium
- ✅ Categoría
- ✅ Fecha de expiración con alertas urgentes
- ✅ Botón "Ver Tienda"

### Sistema de Filtros:
- ✅ Filtrar por categoría
- ✅ Contador de ofertas por categoría
- ✅ Filtrado instantáneo sin recargar

### Responsive:
- ✅ Desktop: 3 columnas
- ✅ Tablet: 2 columnas
- ✅ Móvil: 1 columna

---

## 🚨 Solución de Problemas

### No aparecen ofertas
**Causa:** No hay ofertas activas de tiendas Premium

**Solución:**
1. Verifica que haya usuarios Premium: `SELECT * FROM usuarios WHERE es_premium = 1`
2. Verifica que haya ofertas activas: `SELECT * FROM cupones_ofertas WHERE estado = 'activo'`
3. Ejecuta `test_ofertas_sistema.php` para diagnóstico completo

### Error en la consulta SQL
**Causa:** La tabla `cupones_ofertas` no existe

**Solución:**
```sql
-- Ejecuta el archivo crear_tabla_cupones.sql
```

### El enlace "Ofertas" no aparece en el menú
**Causa:** El archivo `includes/header.php` no se actualizó

**Solución:**
- Verifica que el archivo tenga el enlace:
```php
<a href="ofertas.php" class="nav-btn">
    <i class="fas fa-tags"></i>
    <span>Ofertas</span>
</a>
```

---

## 📊 Valor del Sistema

### Para el Negocio:
- ✅ Aumenta el valor del Plan Premium
- ✅ Incentiva a vendedores a hacerse Premium
- ✅ Genera más tráfico al sitio
- ✅ Mejora la experiencia del usuario

### Para los Clientes:
- ✅ Descubren ofertas fácilmente
- ✅ Ahorran dinero
- ✅ Encuentran promociones de su interés

### Para los Vendedores:
- ✅ Mayor visibilidad de sus ofertas
- ✅ Atracción de nuevos clientes
- ✅ Diferenciación vs. competencia

---

## 📞 Soporte

Si tienes problemas:
1. Ejecuta `test_ofertas_sistema.php` para diagnóstico
2. Revisa `SISTEMA_OFERTAS_PUBLICAS.md` para documentación completa
3. Verifica los logs de errores de PHP

---

## ✅ Checklist de Verificación

- [ ] La tabla `cupones_ofertas` existe
- [ ] Hay al menos un usuario Premium
- [ ] Hay al menos una oferta activa
- [ ] El archivo `ofertas.php` existe
- [ ] El archivo `css/ofertas-styles.css` existe
- [ ] El enlace "Ofertas" aparece en el menú
- [ ] La página carga sin errores
- [ ] Los filtros funcionan correctamente
- [ ] El diseño es responsive

---

## 🎉 ¡Listo!

El sistema está **100% funcional** y listo para usar. Los clientes pueden empezar a descubrir ofertas inmediatamente.

**URL de acceso:** `http://tu-sitio.com/ofertas.php`
