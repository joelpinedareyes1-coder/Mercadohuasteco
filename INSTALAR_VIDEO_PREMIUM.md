# 🎥 Instalación Rápida: Video Premium

## Paso 1: Ejecutar Script SQL

Ejecuta el script de instalación:

```bash
php ejecutar_video_premium.php
```

O ejecuta manualmente en tu base de datos:

```sql
ALTER TABLE tiendas ADD COLUMN link_video VARCHAR(500) DEFAULT NULL AFTER logo;
CREATE INDEX idx_link_video ON tiendas(link_video);
```

## Paso 2: Verificar Instalación

✅ Los archivos ya están modificados:
- `panel_vendedor.php` - Campo de video agregado con instrucciones paso a paso
- `tienda_detalle.php` - Visualización de video agregada

---

## 📝 Cómo Usar (Para Vendedores Premium)

### 1. Obtener la URL del Video de YouTube

1. Ve a tu video en [YouTube](https://www.youtube.com)
2. Haz clic en **"Compartir"** debajo del video
3. Selecciona **"Incorporar"**
4. Copia la URL que está dentro de `src="..."`

**Ejemplo de URL correcta:**
```
https://www.youtube.com/embed/dQw4w9WgXcQ
```

### 2. Agregar en el Panel de Vendedor

1. Ve a tu Panel de Vendedor
2. En la sección "Video de Presentación"
3. Busca el campo **"URL del Video (YouTube o Vimeo)"**
4. Pega la URL que copiaste (la del `src=""`)
5. Guarda los cambios

### 3. Verificar en tu Perfil

1. Ve a tu perfil de tienda
2. El video aparecerá después de las Redes Sociales
3. Los visitantes podrán ver tu video de presentación

---

## Paso 3: Probar

1. **Como vendedor Premium:**
   - Ir al Panel del Vendedor
   - Buscar sección "Video de Presentación"
   - Seguir las instrucciones paso a paso que aparecen en el formulario
   - Pegar URL de YouTube embed (ej: `https://www.youtube.com/embed/dQw4w9WgXcQ`)
   - Guardar

2. **Verificar en página de tienda:**
   - Ir a tu página de tienda
   - El video debe aparecer después de Redes Sociales
   - Debe ser responsivo y reproducirse correctamente

## URLs de Ejemplo para Probar

**YouTube (formato embed):**
- `https://www.youtube.com/embed/dQw4w9WgXcQ`
- `https://www.youtube.com/embed/jNQXAC9IVRw`

**Vimeo:**
- `https://player.vimeo.com/video/148751763`

---

## 🎯 Características

### Para Vendedores Premium
- ✅ Campo exclusivo en el panel
- ✅ Instrucciones paso a paso incluidas (igual que Google Maps)
- ✅ Validación de URL de YouTube/Vimeo
- ✅ Fácil de configurar

### Para Visitantes
- ✅ Video integrado en el perfil
- ✅ Reproducción directa sin salir de la página
- ✅ Diseño moderno y responsive
- ✅ Carga optimizada

---

## ¿Qué hace esta función?

- ✅ Permite a usuarios Premium agregar un video de YouTube o Vimeo
- ✅ El video se muestra en su página de tienda
- ✅ Totalmente responsivo (se adapta a móviles)
- ✅ Sin costo de almacenamiento (videos alojados externamente)
- ✅ Usuarios normales ven el campo deshabilitado con mensaje Premium
- ✅ Instrucciones paso a paso integradas en el formulario

---

## 🐛 Solución de Problemas

### El campo no aparece en el panel
- Verifica que seas usuario Premium
- Ejecuta el instalador primero

### El video no se muestra en el perfil
- Verifica que la URL sea de YouTube embed
- Debe empezar con: `https://www.youtube.com/embed/`
- No uses la URL normal del video, usa la de "Incorporar"

### Error al guardar
- Verifica que la columna exista en la BD
- Ejecuta `ejecutar_video_premium.php`

---

## 💡 Consejos

### Para Vendedores
- Usa videos cortos y atractivos (30-90 segundos)
- Muestra tus productos o servicios principales
- Asegúrate de que el video sea de buena calidad
- Usa la URL de "Incorporar", no la URL normal

### Para Administradores
- Solo usuarios Premium pueden usar esta función
- El video mejora la presentación del negocio
- Útil para mostrar productos, servicios o tours virtuales

---

## Listo!

La función está completamente instalada y lista para usar con instrucciones paso a paso integradas. 🎉
