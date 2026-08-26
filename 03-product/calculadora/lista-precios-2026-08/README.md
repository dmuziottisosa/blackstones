# Lista de precios — agosto 2026

> **Fuente oficial** del catálogo de materiales usado por la calc.
> Hoja del xlsx: `Diogenito 250826`.
> Supersede a `../lista-precios-2026-07/` **solo en materiales** (COLORS_DB).
> Piletas (BACHAS_DB) NO cambia: sigue vigente `../lista-precios-2026-05/piletas.xlsx`.
> **Cargado en calc:** sí — commit 2026-08-26.

## Formato del archivo

Una sola hoja, dos columnas:
- **Col A** — nombre del color (o header de sección/subgrupo, sin precio)
- **Col B** — precio FINAL para la calc (columna "precio calculadora")

El precio de la col B se carga tal cual, redondeado a 2 decimales. Sin
multiplicadores ni columnas intermedias.

## Parser

`parse-xlsx.py` (acá al lado, versionado) es el parser real que generó esta
carga. Antes vivía en `/tmp` y se perdía entre cargas — por eso ahora está
en el repo.

```
python3 parse-xlsx.py lista-precios.xlsx
```

Emite `parsed.json`. La dedup (primera aparición por marca+nombre gana) y la
escritura del bloque `COLORS_DB` en `calc.html` van aparte.

**Regla de oro del parser:** una fila CON precio es SIEMPRE un color, nunca
un encabezado. El chequeo de sección solo corre sobre filas sin precio.
(Ver el bug de Silestone en `../lista-precios-2026-07/README.md`.)

**Chequeo obligatorio post-carga:** que las 12 marcas de `MATS` tengan
colores > 0.

## Resultado de la carga

**684 colores** (antes 691).

| Marca | Items | Cambios de precio |
|---|---|---|
| Marmol | 192 | 80: 59 a ×0,8462 · 21 a ×0,8461 |
| Suprastone | 120 | 102: todos a ×0,9333 |
| Cuarcita | 81 | — sin cambios |
| Neolith | 50 | — sin cambios |
| Prima | 43 | 9 a ×0,968 |
| Dekton | 42 | — sin cambios |
| Pura | 32 | 32: 30 a ×0,968 · 1 a ×1,0925 · 1 a ×1,0505 |
| Guidoni | 29 | 29: 20 a ×0,8462 · 9 a ×0,8461 |
| Silestone | 28 | — sin cambios |
| Xtone | 27 | 27 a ×0,9333 |
| Granito_i | 23 | 23: 8 a ×0,8462 · 6 a ×0,88 · 6 a ×0,8461 · 1 a ×0,96 · 1 a ×0,9731 · 1 a ×2,1296 |
| Granito_n | 17 | 17: 16 a ×0,8462 · 1 a ×0,785 |

**319 colores cambiaron de precio · 365 quedaron igual.**

### Lectura de los multiplicadores

No son ruido: la lista se movió por bloques.

- **×0,8462 (−15,4 %)** — todo lo natural: Guidoni, mármoles, travertinos,
  granitos importados y nacionales.
- **×0,9333 (−6,7 %)** — Xtone y Suprastone (Onemar).
- **×0,968 (−3,2 %)** — Pura y parte de Prima.
- **×0,88 (−12 %)** — los granitos negros importados.
- Dekton, Neolith, Silestone y Cuarcita: **la lista no los tocó**.

## Filas que salen del catálogo (7)

| Marca | Color | Precio que tenía | Por qué |
|---|---|---|---|
| Marmol | Piedra Caliza Patagonica | ARS 224.939 | La fila ya no está en la lista del proveedor |
| Marmol | Statuario | USD 2.905,50 | Idem (siguen existiendo los otros Statuario/Estatuario) |
| Suprastone | GRISES ONEMAR | USD 660,66 | **No era un color: era un encabezado de sección** |
| Suprastone | TRAVERTINOS ONEMAR | USD 660,66 | idem |
| Suprastone | BEIGE ONEMAR | USD 660,66 | idem |
| Suprastone | Negros Marrones ONEMAR | USD 660,66 | idem |
| Suprastone | Exotic ONEMAR | USD 660,66 | idem |

### Los 5 "ONEMAR" eran basura heredada

En el xlsx de julio esas 5 filas de encabezado traían precio en la col B, así
que la regla de oro del parser ("fila con precio = color") las metió como
colores. Quedaron 5 items fantasma en la calc llamados `GRISES ONEMAR`,
`BEIGE ONEMAR`, etc. En el xlsx de agosto el encabezado va sin precio y el
precio quedó en una fila sin nombre debajo, así que el parser los descarta
solo. **Es una corrección, no una pérdida de catálogo.**

Si alguna vez alguien cotizó "GRISES ONEMAR" a un cliente, era un
Suprastone gris a USD 660,66 — que hoy vale USD 616,62.

## Cambios individuales que vale mirar

Los tres únicos precios que **suben**, más el que más baja:

| Marca | Color | Antes | Ahora | |
|---|---|---|---|---|
| Granito_i | **Blanco Ceara** | USD 593,12 | **USD 1.263,12** | ×2,13 — es el único salto grande de toda la lista. Ojo: hay otro "Blanco Ceara" en Marmol a USD 1.085,37 que no se movió. Vale confirmar con el proveedor que no sea un cruce de filas. |
| Pura | Blanco Cana 24 (N y J) | USD 505,38 | USD 552,12 | ×1,09 |
| Pura | Blanco Paloma (N y J) | USD 505,38 | USD 530,89 | ×1,05 |
| Granito_n | Gris Mara | ARS 339.453 | ARS 266.466 | ×0,785 — baja más que el resto del bloque nacional (que bajó 15,4 %) |

## Pendiente de confirmar (heredado de julio)

- **Calacatta Plus** y **Portoro Super** a USD 7.312,50 siguen igual en la
  lista nueva. Sin confirmar si es precio real o error de moneda en la
  planilla fuente.

## Duplicados dentro de la misma marca

16 filas repetidas (mismo nombre, misma marca, precio distinto por estar en
GRUPOS diferentes). Criterio de siempre: **gana la primera aparición**. Los
repetidos entre marcas distintas (Calacatta Gold en Guidoni y en Suprastone,
Taj Mahal en Cuarcita/Prima/Marmol) son legítimos y se conservan todos.

## Cross-links

- `06-knowledge/layer-1-synthesis/2026-05-recomendaciones-material-por-uso.md`
  — los rangos referenciales de precio viven ahí y quedaron desactualizados
  con esta baja del 15 % en naturales.
- `../lista-precios-2026-07/README.md` — mapeo sección→marca y el bug del
  parser de Silestone.
