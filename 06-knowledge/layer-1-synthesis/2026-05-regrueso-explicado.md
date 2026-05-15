# Regrueso de mesada — explicación operativa

> **Status:** `layer-1-synthesis` — síntesis técnica derivada del código del calc + práctica BlackStones.
> **Audiencia:** equipo + IA (WhatsApp Business AI).
> **Validez:** alta confianza · validado contra `calc.html` (funciones `getR`, `calcM2`) y catálogo de materiales.

---

## En una frase

**Regrueso = hacer que el borde visible de una mesada SE VEA más grueso de lo que realmente es**, pegando piezas adicionales del mismo material debajo del frente (y/o laterales) de la pieza.

---

## ¿Por qué existe?

Las mesadas se compran por **estética**, no solo por función. Un borde de 3-5 cm se ve premium, robusto, contemporáneo. Un borde de 1.2 cm se ve débil, "barato", de oficina.

Algunos materiales vienen de fábrica con espesor **fino**:

| Material | Espesor real (de fábrica) | Espesor visible deseable |
|---|---|---|
| Sinterizado (Xtone, Neolith, Dekton, Suprastone, Prima, Pura) | **12 mm** | 3-5 cm |
| Cuarzo Silestone | 20 mm | 3-5 cm (opcional) |
| Cuarzo Guidoni | 20 mm | 3-5 cm (opcional) |
| Mármol natural | 30 mm | OK tal cual o 5 cm (opcional) |
| Granito | 30 mm | OK tal cual o 5 cm (opcional) |

**Conclusión simple:**
- Sinterizado → **casi siempre lleva regrueso** (por estética + estabilidad estructural)
- Cuarzo → lleva regrueso si el cliente quiere estética premium
- Mármol/Granito 30mm → solo si el cliente quiere un look extra-grueso (4-5 cm)

---

## ¿Cómo se hace técnicamente?

Se pega una tira del mismo material debajo del borde frontal de la pieza. Visualmente, desde afuera, parece una sola pieza maciza de 3-5 cm.

```
            ↓ vista de perfil del borde frontal de la mesada
        ┌─────────────────────────────────┐
   1.2cm│   ← placa principal             │
        ├─────────────────────────────────┤
   3.8cm│   ← tira pegada (regrueso)      │
        └─────────────────────────────────┘
        ↑ visible: 5 cm de "espesor"
```

El borde se pule y se lustra para que parezca una pieza sólida única.

---

## Tipos de regrueso por sección de mesada (lógica del calc)

### Mesada principal / Mesada en L

| Opción | Significa | Cuándo usar |
|---|---|---|
| **Sin** | sin regrueso, espesor real | cliente quiere economizar / mármol grueso que ya se ve bien |
| **L** | regrueso solo en el LARGO (frente) | quiere borde frontal grueso, pero los laterales quedan ocultos contra pared |
| **A** | regrueso solo en el ANCHO (laterales) | configuración rara — pieza vista desde el costado |
| **L+A** | regrueso en LARGO y ANCHO | mesada con frente expuesto + lateral expuesto (típico en islas o cocinas con peninsula) |

**Default para sinterizados:** `L+A` (porque sinterizado siempre lleva regrueso).

### Isla

| Opción | Default |
|---|---|
| **Sí / No** | Sinterizados → Sí automático. Otros materiales → opcional |
| **cm de regrueso** | Default 5 cm |
| **Aplicación** | Los 4 lados (tapa + alto de laterales) |

### Mesada de baño

| Opción | Significa |
|---|---|
| **Sin** | sin regrueso |
| **Solo frente** | regrueso en el borde frontal únicamente |
| **Frente + 1 lat** | borde frontal + un lateral (la otra punta queda contra pared o mueble) |
| **Frente + 2 lat** | borde frontal + ambos laterales (mesada de baño aislada, vista por los 3 lados) |

### Alzada

**No lleva regrueso.** Es la franja vertical que sube por la pared como salpicadero — se ve solo de frente, no tiene borde expuesto que justifique regrueso.

---

## Valor estándar de cm

**5 cm es el default operativo.** Es lo que el calc asigna cuando se activa regrueso sin especificar valor.

Otras medidas frecuentes que se piden:
- **3 cm** — minimalista, contemporáneo
- **4 cm** — equilibrado, más común
- **5 cm** — robusto, "casero" premium
- **6-8 cm** — look "macizo", específico de cocina rústica/industrial

---

## Impacto en el costo

El regrueso **suma metros cuadrados al cálculo del material** porque hay que comprar/cortar las tiras adicionales.

### Fórmula que aplica el calc

Para una **mesada estándar** con regrueso L+A de `R` metros:

```
m² original    =  d1 × d2                     (largo × ancho)
m² con regrueso = (d1 + R) × (d2 + R)
diferencia     = m² regrueso - m² original
```

### Ejemplo numérico

Mesada 2.40 m × 0.60 m con regrueso 5 cm (0.05 m) en L+A:

```
Sin regrueso:    2.40 × 0.60     = 1.4400 m²
Con regrueso:    2.45 × 0.65     = 1.5925 m²
Extra cobrado:                     0.1525 m²
```

Si el material vale **USD 700/m²**, el cliente paga **USD 106.75 extra** por el regrueso.

### Otro ejemplo — solo regrueso L (frente)

Mesada 2.40 m × 0.60 m con regrueso 5 cm en L (solo frente):

```
Sin regrueso:    2.40 × 0.60        = 1.4400 m²
Con regrueso L:  2.40 × (0.60 + 0.05) = 1.5600 m²
Extra:                                  0.1200 m²
```

### Isla 1.70 × 0.90 con regrueso 5 cm en los 4 lados

```
Sin regrueso:    1.70 × 0.90              = 1.5300 m²
Con regrueso:    1.80 × 1.00              = 1.8000 m²
Extra tapa:                                 0.2700 m²

(además se le suma el regrueso en alto del lateral si es sinterizado)
```

---

## Cómo explicárselo al cliente (lenguaje común)

### Si pregunta "¿qué es el regrueso?"

> El regrueso es el grosor que ves del borde de tu mesada. Las mesadas vienen de fábrica con 12 mm (sinterizado) o 20-30 mm (otros materiales), pero estéticamente queda mejor verlas con 3, 4 o 5 cm de grosor en el frente. Eso se logra pegando una tira del mismo material debajo del borde — visualmente parece una mesada maciza más gruesa.

### Si pregunta "¿es obligatorio?"

> Para sinterizado (Xtone, Neolith, Dekton, Suprastone, Prima): prácticamente sí. Sale demasiado fino sin regrueso y se nota.
> Para granito o mármol natural: no, pero queda más prolijo con 3-5 cm.

### Si pregunta "¿cuánto cuesta?"

> El regrueso se cobra como material adicional, porque hay que cortar y pegar tiras extra del mismo material. Una mesada de cocina estándar suele tener 0.10 a 0.15 m² adicionales por regrueso. Lo ves desglosado en la cotización.

### Si pregunta "¿qué cm me conviene?"

> 3 cm queda minimalista / moderno. 5 cm es lo más común y se ve premium. 6-8 cm es para look macizo de cocina rústica. Mi recomendación según el resto de tu cocina la veo cuando lleguen las medidas.

---

## Honestidad técnica obligatoria

El cliente puede notar la junta del regrueso si se acerca mucho. Decirlo antes:

> El regrueso lleva una junta horizontal apenas visible donde se pega la tira al cuerpo de la mesada. La pulimos y lustramos para que la unión sea mínima, pero a 30 cm de distancia con luz lateral se puede ver una línea fina. Estéticamente compensa porque el borde grueso se ve mucho más premium que el borde fino del espesor real.

---

## Errores comunes / qué evitar

1. **Vender mesada de sinterizado SIN regrueso** sin avisar al cliente. El borde de 12mm queda visualmente débil. El cliente la recibe y se queja. → Avisar siempre, ofrecer regrueso como default.

2. **Cobrar regrueso sin que esté en la cotización por escrito.** Cae en el dolor #4 de Carolina (extras al colocar). → Si lleva regrueso, debe estar en el presupuesto cerrado.

3. **Asumir L+A cuando va contra pared.** Una mesada con un lateral contra pared no necesita regrueso en ese lado — el cliente no lo va a ver. → Preguntar configuración de espacio antes de elegir tipo.

4. **Regrueso muy grande (>6 cm) en sinterizado.** El sinterizado en 12mm con regrueso de 8 cm hace una junta muy visible y a veces la pieza pesa más de lo recomendable. → Default 5 cm, máximo 6 cm para sinterizado.

---

## Glosario relacionado (para que la IA reconozca)

| Término | Sinónimo / variante | Qué es |
|---|---|---|
| Regrueso | regrosado, recubrimiento, "borde armado" | Lo que explica este doc |
| Espesor | grosor, calibre | El espesor real del material de fábrica |
| Frente | borde frontal, canto | El lado largo de la mesada que da al usuario |
| Lateral | costado | Los lados angostos de la mesada (a veces contra pared) |
| Bisel | chaflán, biselado | Tipo de terminación del borde (cortado en ángulo) |
| Pulido | lustrado | Terminación brillante del borde |
| Junta | unión, línea de pegado | Donde se ven dos piezas pegadas |
| Tapa | superficie principal | La cara plana superior de la mesada |

---

## Cómo lo trata el calc internamente (referencia técnica)

```
// Campo en cada item de mesada:
//   reg: 'Sin' | 'L' | 'A' | 'L+A'   (para m/l)
//        'Sí' | 'No'                   (para isla)
//        'Sin' | 'Solo frente' | 'Frente + 1 lat' | 'Frente + 2 lat'  (para baño)
//   rv: número en cm (default 5)

// La función getR(it) devuelve [aL, aA] = añadidos al ancho/largo en metros.
// La función calcM2(s, it) calcula el m² final incluyendo regrueso.
```

---

## Cross-links

- `06-knowledge/layer-1-synthesis/2026-05-recomendaciones-material-por-uso.md` — matriz de cuándo recomendar cada material
- `02-marketing/whatsapp/recommendation-patterns.md` — patrón de respuesta WhatsApp
- `03-product/calculadora/baseline-v1.0.md` — funcionalidad del calc
- `site/public_html/calculadora/calc.html` líneas 2434-2480 (funciones `getR`, `calcM2`)

---

## Trigger de revisión

- Cambia el catálogo (nuevo material con espesor distinto que requiera ajuste)
- Detectamos un patrón de queja recurrente sobre regrueso en WhatsApp/post-venta
- Cliente arquitectura pide regrueso de cm no estándar (8, 10, 12 cm) recurrentemente → ampliar opciones del calc
- Default: revisión cada 90 días
