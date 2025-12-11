# 🎫 Sistema de Cupones y Ofertas Premium

## ✅ Implementado Completamente

Las tiendas Premium ahora pueden crear y gestionar sus propias ofertas y cupones promocionales.

---

## 🚀 Instalación

### Paso 1: Crear la Tabla
```
Visita: http://tu-dominio.com/ejecutar_cupones.php
```

Esto creará la tabla `cupones_ofertas` en tu base de datos.

---

## 📦 Archivos Creados

1. ✅ `crear_tabla_cupones.sql` - Script de base de datos
2. ✅ `ejecutar_cupones.php` - Instalador automático
3. ✅ `mis_ofertas.php` - Panel de gestión para vendedores
4. ✅ `ofertas.php` - Página pública de todas las ofertas
5. ✅ `cron_actualizar_ofertas.php` - Script para expirar ofertas
6. ✅ `tienda_detalle.php` - Modificado para mostrar ofertas

---

## 🎯 Características

### Para Vendedores Premium

#### Panel de Gestión (mis_ofertas.php)
- ✅ Crear ofertas ilimitadas
- ✅ Título (máx 100 caracteres)
- ✅ Descripción opcional
- ✅ Fecha de expiración
- ✅ Ver todas sus ofertas
- ✅ Pausar/Activar ofertas
- ✅ Eliminar ofertas
- ✅ Estados: Activo, Pausado, Expirado

#### Acceso al Panel
- Solo visible para usuarios Premium
- Enlace en el menú lateral del panel
- Interfaz moderna con diseño de cupones

### Para Clientes

#### Página Central de Ofertas (ofertas.php)
- ✅ Ver todas las ofertas activas
- ✅ Filtradas por Premium y activas
- ✅ Diseño de cupones con borde punteado
- ✅ Logo de la tienda
- ✅ Días restantes destacados
- ✅ Enlace directo a la tienda

#### En el Perfil de Tienda
- ✅ Sección "Ofertas Especiales"
- ✅ Solo visible si es Premium
- ✅ Solo muestra ofertas activas
- ✅ Diseño atractivo de cupones
- ✅ Fecha de expiración visible

---

## 🎨 Diseño

### Cupones en el Perfil
```
┌─────────────────────────────────────┐
│ 🏷️  2x1 en todos los productos     │
│                                     │
│ Válido en toda la tienda           │
│                                     │
│ 📅 Válido hasta: 31/12/2024        │
└─────────────────────────────────────┘
```

### Página de Ofertas
- Header dorado con gradiente
- Tarjetas de cupones con borde punteado
- Logo de la tienda en cada cupón
- Badge de "¡Últimos días!" si expira pronto
- Botón para ver la tienda

---

## 📋 Estructura de la Base de Datos

```sql
cupones_ofertas
├── id (PK)
├── id_tienda (FK → tiendas)
├── titulo (VARCHAR 100)
├── descripcion (TEXT)
├── fecha_inicio (DATE)
├── fecha_expiracion (DATE)
├── estado (ENUM: activo, expirado, pausado)
└── fecha_creacion (DATETIME)
```

---

## 🔄 Gestión de Ofertas

### Crear Oferta
1. Vendedor Premium accede a "Mis Ofertas"
2. Llena el formulario:
   - Título (obligatorio)
   - Descripción (opcional)
   - Fecha de expiración (obligatoria, futura)
3. Clic en "Crear Oferta"
4. Aparece inmediatamente en su perfil

### Pausar Oferta
- Botón "Pausar" en cada oferta activa
- La oferta deja de mostrarse públicamente
- Se puede reactivar en cualquier momento

### Eliminar Oferta
- Botón "Eliminar" con confirmación
- Eliminación permanente
- No se puede recuperar

### Expiración Automática
- Script CRON actualiza ofertas diariamente
- Cambia estado a "expirado" automáticamente
- Las ofertas expiradas no se muestran públicamente

---

## ⚙️ Configurar CRON (Opcional)

Para actualizar automáticamente las ofertas expiradas:

### En cPanel
1. Ve a "Cron Jobs"
2. Agrega nuevo cron job:
   - Frecuencia: Diaria (0 0 * * *)
   - Comando: `/usr/bin/php /home/usuario/public_html/cron_actualizar_ofertas.php`

### En servidor Linux
```bash
crontab -e
```

Agregar línea:
```
0 0 * * * /usr/bin/php /ruta/completa/cron_actualizar_ofertas.php
```

### Manualmente
También puedes ejecutar manualmente:
```
php cron_actualizar_ofertas.php
```

---

## 🔒 Seguridad

### Validaciones
- ✅ Solo usuarios Premium pueden crear ofertas
- ✅ Fecha de expiración debe ser futura
- ✅ Título máximo 100 caracteres
- ✅ Solo el dueño puede gestionar sus ofertas
- ✅ Confirmación para eliminar

### Filtros
- ✅ Solo ofertas activas se muestran públicamente
- ✅ Solo ofertas no expiradas
- ✅ Solo de tiendas activas
- ✅ Solo de usuarios Premium

---

## 💡 Casos de Uso

### Ejemplo 1: Tienda de Ropa
```
Título: 2x1 en toda la tienda
Descripción: Compra una prenda y llévate otra gratis
Expira: 31/12/2024
```

### Ejemplo 2: Restaurante
```
Título: 20% de descuento en desayunos
Descripción: Válido de lunes a viernes de 7am a 11am
Expira: 15/01/2025
```

### Ejemplo 3: Librería
```
Título: 3x2 en libros de texto
Descripción: Compra 3 libros y paga solo 2
Expira: 28/02/2025
```

---

## 📊 Beneficios

### Para el Negocio
- 🎯 Atrae más clientes
- 📈 Aumenta las ventas
- 🔄 Fideliza clientes
- 📣 Marketing gratuito
- ⭐ Destaca sobre la competencia

### Para la Plataforma
- 👥 Más visitas recurrentes
- 💎 Incentivo para ser Premium
- 📱 Contenido dinámico
- 🎯 Engagement de usuarios
- 💰 Valor agregado Premium

### Para los Clientes
- 💰 Ahorro en compras
- 🎁 Ofertas exclusivas
- 📍 Descubren nuevas tiendas
- ⏰ Ofertas por tiempo limitado
- 🛍️ Mejor experiencia de compra

---

## 🎨 Personalización

### Cambiar Colores
En `mis_ofertas.php` y `ofertas.php`, busca:
```css
background: linear-gradient(135deg, #FFD700, #FFA500);
```

Cambia por tus colores preferidos.

### Cambiar Diseño de Cupones
En `tienda_detalle.php`, línea ~2520, personaliza el estilo del cupón.

---

## 📱 Responsive

El sistema funciona perfectamente en:
- ✅ Desktop (1920px+)
- ✅ Laptop (1366px)
- ✅ Tablet (768px)
- ✅ Móvil (375px)

---

## 🐛 Solución de Problemas

### No aparece el enlace "Mis Ofertas"
- Verifica que el usuario sea Premium
- Revisa `es_premium = 1` en la tabla usuarios

### Las ofertas no se muestran en el perfil
- Verifica que el estado sea 'activo'
- Verifica que la fecha no haya expirado
- Verifica que el usuario sea Premium

### Error al crear oferta
- Verifica que la tabla exista
- Ejecuta `ejecutar_cupones.php`
- Revisa que la fecha sea futura

---

## ✨ Resumen

Ahora tienes un **sistema completo de cupones y ofertas** que:

✅ Permite a vendedores Premium crear ofertas
✅ Muestra ofertas en el perfil de la tienda
✅ Tiene una página central de todas las ofertas
✅ Gestión completa (crear, pausar, eliminar)
✅ Expiración automática con CRON
✅ Diseño atractivo de cupones
✅ Responsive y moderno
✅ Aumenta el valor de Premium

**¡Tu plataforma ahora tiene un sistema de marketing poderoso! 🎉**
