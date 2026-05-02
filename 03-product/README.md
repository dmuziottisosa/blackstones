# 03-product/

Lo que vendemos: materiales, servicios, lógica detrás del precio.

## Subcarpetas

| Carpeta | Qué vive ahí |
|---|---|
| `calculadora/` ⭐ | Mapa funcional + baseline v1.0 de la app interna de cotización. **Lectura obligatoria antes de modificar `site/public_html/calculadora/`.** |
| `materials/` | Granito, cuarzo, sinterizado: ficha técnica + ficha comercial + cómo asesorar |
| `services/` | Qué hacemos / qué no hacemos. Asesoramiento, medición, fabricación, flete, colocación. |

## Pendientes (sin doc todavía)

- `materials/granito.md` — info técnica, marcas, garantías (sin garantía de fábrica), cuándo recomendar
- `materials/cuarzo.md` — Silestone, Technistone, Purastone, Pura Prima. Diferencias. Garantías.
- `materials/sinterizado.md` — Dekton, Neolith, Xtone, Suprastone. Reglas de regrueso. Garantía 25 años.
- `materials/comparativa.md` — tabla comparativa para asesorar al cliente
- `services/scope.md` — qué entra / qué no entra explícito
- `services/non-services.md` — coordinación de gremios, demolición, plomería: NO hacemos
- `pricing-logic.md` — cómo se construye el precio (sin números — la lógica)

## Reglas de esta carpeta

- **No precios duros acá.** Los precios viven en la calc (`site/public_html/calculadora/calc.html` → `COLORS_DB`). Esta carpeta es la lógica, no la tarifa.
- **Honestidad técnica.** Si el granito se mancha, lo decimos. Si el cuarzo no tolera calor extremo, lo decimos. Confianza > venta agresiva.
- **Cada material tiene un ángulo de venta diferente:**
  - Granito → "el clásico noble"
  - Cuarzo → "el moderno funcional"
  - Sinterizado → "el premium tecnológico"
