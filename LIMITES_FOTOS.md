# 📸 Límites de Fotos por Membresía

## 🎯 Límites Actuales

| Tipo de Usuario | Límite de Fotos | Beneficio |
|-----------------|-----------------|-----------|
| **Normal** 👤 | **2 fotos** | Básico |
| **Premium** ⭐ | **10 fotos** | 5x más fotos |

---

## 📊 Comparación

### Usuario Normal (Gratis)
- ✅ Hasta **2 fotos** en la galería
- ✅ Perfil básico
- ✅ Aparece en el directorio

### Usuario Premium (Pago)
- ⭐ Hasta **10 fotos** en la galería
- ⭐ Tienda destacada (aparece primero)
- ⭐ Insignia "PREMIUM" visible
- ⭐ Mayor visibilidad

---

## 💡 Ventajas de Premium

Con **5 veces más fotos**, los usuarios Premium pueden:

1. **Mostrar más productos** - Más variedad para los clientes
2. **Mayor confianza** - Más imágenes = más credibilidad
3. **Mejor presentación** - Galería completa y profesional
4. **Destacar en búsquedas** - Aparecen primero en el directorio
5. **Insignia Premium** - Diferenciación visual

---

## 🔄 Cómo Funciona

### Para Usuarios Normales:
1. Pueden subir hasta **2 fotos**
2. Al intentar subir la 3ra foto, verán un mensaje:
   > "Has alcanzado el límite de 2 fotos. ¡Actualiza a Premium para subir hasta 10 fotos!"
3. El botón de subir se deshabilita al alcanzar el límite

### Para Usuarios Premium:
1. Pueden subir hasta **10 fotos**
2. Ven un contador: "X / 10 fotos"
3. Tienen más espacio para mostrar su negocio

---

## 🎨 Interfaz

### Contador Visual:
```
┌─────────────────────────────────┐
│ ⭐ Premium        2 / 10 fotos  │
│ ¿Quieres más fotos?             │
│ Actualiza a Premium para 10     │
└─────────────────────────────────┘
```

### Botón de Subir:
- **Habilitado**: "Subir Foto (8 disponibles)"
- **Deshabilitado**: "Límite Alcanzado"

---

## 📈 Estrategia de Monetización

### Precio Sugerido:
- **Mensual**: $5-10 USD
- **Trimestral**: $12-25 USD (ahorro 20%)
- **Anual**: $40-80 USD (ahorro 33%)

### Valor Percibido:
- 2 fotos → Básico
- 10 fotos → **5x más valor**
- Destacado → Mayor visibilidad
- Insignia → Prestigio

---

## 🔧 Implementación Técnica

### Código en `galeria_vendedor.php`:
```php
// Establecer límites según membresía
$es_premium = isset($usuario_info['es_premium']) && $usuario_info['es_premium'] == 1;
$limite_fotos = $es_premium ? 10 : 2;
```

### Validación:
```php
if ($total_fotos_actual >= $limite_fotos) {
    $error = "Has alcanzado el límite de $limite_fotos fotos.";
    if (!$es_premium) {
        $error .= " ¡Actualiza a Premium para 10 fotos!";
    }
}
```

---

## ✅ Cambios Realizados

- ✅ Límite Normal: 5 → **2 fotos**
- ✅ Límite Premium: 20 → **10 fotos**
- ✅ Mensajes actualizados
- ✅ Contador actualizado
- ✅ Validaciones funcionando

---

## 🎯 Resultado

Los usuarios ahora tienen un incentivo claro para actualizar a Premium:
- **2 fotos** es suficiente para empezar
- **10 fotos** es ideal para mostrar todo el negocio
- La diferencia es significativa (5x más)
- El precio es justificable

---

**Última actualización:** 2025
**Versión:** 2.0
