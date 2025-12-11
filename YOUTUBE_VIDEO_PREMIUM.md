# 🎥 Video de YouTube para Tiendas Premium

## ✅ Implementado

Las tiendas Premium ahora pueden mostrar un video de presentación de YouTube o Vimeo en su perfil.

---

## 🚀 Instalación

### Paso 1: Agregar Columna a la Base de Datos
```
Visita: http://tu-dominio.com/ejecutar_video_premium.php
```

Esto agregará la columna `link_video` a la tabla `tiendas`.

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

**⚠️ IMPORTANTE:** No uses la URL normal del video (la que tiene `/watch?v=`), debes usar la URL de incorporar (la que tiene `/embed/`)

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

## 🎯 Características

### Para Vendedores Premium
- ✅ Campo exclusivo en el panel
- ✅ Instrucciones paso a paso incluidas
- ✅ Validación de URL de YouTube/Vimeo
- ✅ Fácil de configurar
- ✅ Sin límite de duración del video

### Para Visitantes
- ✅ Video integrado en el perfil
- ✅ Reproducción directa sin salir de la página
- ✅ Diseño moderno y responsive
- ✅ Carga optimizada (lazy loading)
- ✅ Controles de reproducción completos

---

## 🎨 Diseño

El video se muestra en una tarjeta moderna con:
- 🎥 Icono de video
- 📺 Video integrado de 450px de alto
- 🎬 Controles de reproducción
- 📱 Responsive (se adapta a móviles)

---

## 🔒 Seguridad

- ✅ Solo vendedores Premium pueden agregar videos
- ✅ URL sanitizada con `filter_var()`
- ✅ Validación de dominio (solo youtube.com y vimeo.com)
- ✅ Atributos de seguridad en iframe

---

## 📋 Archivos Modificados

1. ✅ `agregar_video_premium.sql` - Script SQL
2. ✅ `ejecutar_video_premium.php` - Instalador
3. ✅ `panel_vendedor.php` - Campo agregado con instrucciones
4. ✅ `tienda_detalle.php` - Video visible

---

## 🐛 Solución de Problemas

### El campo no aparece en el panel
- Verifica que seas usuario Premium
- Ejecuta el instalador primero

### El video no se muestra en el perfil
- Verifica que la URL sea de YouTube embed
- Debe empezar con: `https://www.youtube.com/embed/`
- No uses la URL normal del video (`/watch?v=`)
- Usa la URL de "Incorporar" (`/embed/`)

### Error al guardar
- Verifica que la columna exista en la BD
- Ejecuta `ejecutar_video_premium.php`

### El video no carga
- Verifica que el video sea público en YouTube
- Algunos videos tienen restricciones de incorporación
- Prueba con otro video

---

## 💡 Consejos

### Para Vendedores
- Usa videos cortos y atractivos (30-90 segundos idealmente)
- Muestra tus productos o servicios principales
- Asegúrate de que el video sea de buena calidad
- Usa la URL de "Incorporar", no la URL normal
- El video debe ser público en YouTube
- Considera agregar subtítulos para mejor accesibilidad

### Para Administradores
- Solo usuarios Premium pueden usar esta función
- El video mejora significativamente la presentación del negocio
- Útil para mostrar productos, servicios o tours virtuales
- Sin costo de almacenamiento (videos alojados en YouTube/Vimeo)

---

## 📊 Comparación de URLs

### ❌ URL Incorrecta (Normal)
```
https://www.youtube.com/watch?v=dQw4w9WgXcQ
https://youtu.be/dQw4w9WgXcQ
```

### ✅ URL Correcta (Embed)
```
https://www.youtube.com/embed/dQw4w9WgXcQ
```

---

## 🎬 Tipos de Videos Recomendados

### Para Tiendas
- 🏪 Tour virtual de tu tienda
- 📦 Presentación de productos
- 👥 Testimonios de clientes
- 🎯 Promociones especiales
- 📱 Tutoriales de uso

### Para Servicios
- 💼 Presentación del equipo
- 🔧 Demostración de servicios
- 📈 Casos de éxito
- 🎓 Explicación de procesos
- 🌟 Diferenciadores del negocio

---

## ✨ Beneficios

### Para el Negocio
- 🎥 Presentación visual atractiva
- 🎯 Mayor engagement con visitantes
- 💼 Aspecto más profesional
- 📈 Mejor conversión de visitas
- 🌟 Destaca sobre la competencia

### Para los Clientes
- 👀 Conocen mejor el negocio
- 🎬 Experiencia más rica
- 💯 Mayor confianza
- 📱 Contenido multimedia
- ⏱️ Información rápida y visual

---

## 🎉 Resultado

Las tiendas Premium ahora tienen:
- ✅ Video de presentación integrado
- ✅ Instrucciones paso a paso (igual que Google Maps)
- ✅ Aspecto más profesional
- ✅ Mayor credibilidad
- ✅ Mejor experiencia para visitantes

**¡Tu tienda Premium ahora puede mostrar videos como los profesionales! 🚀**
