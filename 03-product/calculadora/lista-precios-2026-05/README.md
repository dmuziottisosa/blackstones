# Lista de precios — mayo 2026

> **Fuente oficial** del catálogo de materiales + piletas usado por la calc.
> **Última actualización:** 2026-05-15
> **Cargado en calc:** sí (commit del 2026-05-15)

---

## Archivos

| Archivo | Contenido | Destino en calc |
|---|---|---|
| `lista-precios.xlsx` | Materiales (granitos, mármoles, sinterizados, cuarzos, cuarcitas) por marca/categoría | `COLORS_DB` en `calc.html` |
| `piletas.xlsx` | Piletas + accesorios + repuestos + adicionales (Etna, Essentia, Designia, Vesta, Económica, Zíngara, Nova, Lavadero) | `BACHAS_DB` en `calc.html` |

---

## Regla de precio

**Columna "PRECIO CON 6 CUOTAS SIN INTERES"** = precio final que se carga en la calc.

En `lista-precios.xlsx` esa es la **columna E**. En `piletas.xlsx` la columna **TOTAL FINAL** (también E).

Las otras columnas (PRECIO VENTA, AUMENTO 25%, valor base) son referencias para entender el precio pero **no se usan directamente** en la calc.

---

## Cobertura del catálogo cargado

| Marca | Items | Currency |
|---|---|---|
| Guidoni | 30 | USD |
| Marmol | 165 | USD |
| Granito_i (Granitos Importados) | 24 | USD |
| Granito_n (Granitos Nacionales + Pórfido) | 18 | ARS |
| Xtone | 29 | USD |
| Pura (Purastone) | 40 | USD |
| Prima | 43 | USD |
| Dekton | 46 | USD |
| Silestone | 27 | USD |
| Neolith | 45 | USD |
| Suprastone (Onemar) | 101 | USD |
| **Total materiales** | **523** | — |
| **Total piletas + accesorios** | **159** | ARS |

---

## Materiales NO incluidos (skipped en la migración)

Algunas filas del XLSX fueron skipeadas porque:
- Son **headers de subgrupo** sin precio (NEGROS, VERDES, BEIGE & CREMA, GRUPO 0/1/2…)
- Son **subheaders** dentro de una sección
- Tienen **precio en formato no numérico** (ej: "Piedra Caliza Patagonica" con "AR$55000" como string)
- Filas duplicadas o vacías

Si se detecta material faltante en la calc tras un cliente real, **buscar en este xlsx** primero, validar si es legítimo, y agregar manualmente al `COLORS_DB`.

---

## Flujo de actualización de precios

Cuando llegue una nueva lista de precios:

1. Reemplazar los archivos `lista-precios.xlsx` y `piletas.xlsx` en esta carpeta (mantener nombres)
2. Correr el parser `/tmp/parse_xlsx.py` (lógica documentada abajo)
3. Validar que la migración no rompió categorías
4. Reemplazar `COLORS_DB` y `BACHAS_DB` en `calc.html`
5. Probar calc localmente
6. Deploy

---

## Mapeo de categorías del XLSX → marca en calc

```
GUIDONI QUARTZ          → Guidoni     · USD
LINEA STELLAR           → Guidoni     · USD
Xtone                   → Xtone       · USD
TRAVERTINOS (USD)       → Marmol      · USD
MARMOLES IMPORTADOS     → Marmol      · USD
CUARCITAS IMPORTADOS    → Marmol      · USD (mapeo a familia mármol)
GRANITOS NACIONALES     → Granito_n   · ARS
PORFIDO LUSTRADO        → Granito_n   · ARS
Granitos Importados     → Granito_i   · USD
PURASTONE STEFANO       → Pura        · USD
DEKTON                  → Dekton      · USD
PRIMA De STEFANO        → Prima       · USD
SILESTONE CANTERA       → Silestone   · USD
NEOLITH                 → Neolith     · USD
SUPRASTONES / ONEMAR    → Suprastone  · USD
Piedras Naturales Exclusiva → Marmol  · USD
Marmoles Y Calizas      → Marmol      · USD
```

---

## Cross-links

- `06-knowledge/layer-1-synthesis/2026-05-recomendaciones-material-por-uso.md` — matriz operativa de qué recomendar por uso. **Cuando los precios cambien, este doc también debería revisarse** (los rangos referenciales viven ahí).
- `01-strategy/backlog.md` — item de actualización trimestral de precios.
