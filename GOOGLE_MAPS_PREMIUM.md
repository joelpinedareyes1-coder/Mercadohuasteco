# 🗺️ Google Maps para Tiendas Premium

## ✅ Implementado

Las tiendas Premium ahora pueden mostrar su ubicación con Google Maps en su perfil.

---

## 🚀 Instalación

### Paso 1: Agregar Columna a la Base de Datos
```
Visita: http://tu-dominio.com/ejecutar_google_maps.php
```

Esto agregará la columna `google_maps_src` a la tabla `tiendas`.

---

## 📝 Cómo Usar (Para Vendedores Premium)

### 1. Obtener la URL del Mapa

1. Ve a [Google Maps](https://www.google.com/maps)
2. Busca tu negocio o dirección
3. Haz clic en **"Compartir"**
4. Selecciona **"Incorporar un mapa"**
5. Copia la URL que está dentro de `src="..."`

**Ejemplo de URL correcta:**
```
https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3762...
```

### 2. Agregar en el Panel de Vendedor

1. Ve a tu Panel de Vendedor
2. En la sección "Información Básica"
3. Busca el campo **"URL de Google Maps (Embed)"**
4. Pega la URL que copiaste
5. Guarda los cambios

### 3. Verificar en tu Perfil

1. Ve a tu perfil de tienda
2. El mapa aparecerá después de la galería de fotos
3. Los visitantes podrán ver tu ubicación

---

## 🎯 Características

### Para Vendedores Premium
- ✅ Campo exclusivo en el panel
- ✅ Instrucciones paso a paso incluidas
- ✅ Validación de URL de Google Maps
- ✅ Fácil de configurar

### Para Visitantes
- ✅ Mapa interactivo en el perfil
- ✅ Botón para abrir en Google Maps
- ✅ Diseño moderno y responsive
- ✅ Carga optimizada (lazy loading)

---

## 🎨 Diseño

El mapa se muestra en una tarjeta moderna con:
- 📍 Icono de ubicación
- 🗺️ Mapa interactivo de 450px de alto
- 🔗 Botón para abrir en Google Maps
- 📱 Responsive (se adapta a móviles)

---

## 🔒 Seguridad

- ✅ Solo vendedores Premium pueden agregar mapas
- ✅ URL sanitizada con `filter_var()`
- ✅ Validación de dominio (solo google.com/maps)
- ✅ Atributos de seguridad en iframe

---

## 📋 Archivos Modificados

1. ✅ `agregar_google_maps.sql` - Script SQL
2. ✅ `ejecutar_google_maps.php` - Instalador
3. ✅ `panel_vendedor.php` - Campo agregado
4. ✅ `tienda_detalle.php` - Mapa visible

---

## 🐛 Solución de Problemas

### El campo no aparece en el panel
- Verifica que seas usuario Premium
- Ejecuta el instalador primero

### El mapa no se muestra en el perfil
- Verifica que la URL sea de Google Maps
- Debe empezar con: `https://www.google.com/maps/embed`
- No uses la URL normal, usa la de "Incorporar"

### Error al guardar
- Verifica que la columna exista en la BD
- Ejecuta `ejecutar_google_maps.php`

---

## 💡 Consejos

### Para Vendedores
- Usa el zoom adecuado antes de copiar el código
- Asegúrate de que tu negocio sea visible
- Verifica que la dirección sea correcta

### Para Administradores
- Solo usuarios Premium pueden usar esta función
- El mapa mejora la confianza del cliente
- Útil para negocios físicos

---

## ✨ Beneficios

### Para el Negocio
- 📍 Los clientes encuentran tu ubicación fácilmente
- 🎯 Aumenta la confianza y credibilidad
- 🚗 Facilita que te visiten físicamente
- 📱 Funciona en todos los dispositivos

### Para los Clientes
- 🗺️ Ven exactamente dónde estás
- 🚶 Pueden planear su visita
- 📍 Obtienen direcciones con un clic
- 💯 Mayor confianza en el negocio

---

## 🎉 Resultado

Las tiendas Premium ahora tienen:
- ✅ Mapa interactivo de Google Maps
- ✅ Ubicación visible para clientes
- ✅ Aspecto más profesional
- ✅ Mayor credibilidad

**¡Tu tienda Premium ahora se ve aún más profesional! 🚀**
