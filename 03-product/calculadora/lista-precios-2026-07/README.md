# Lista de precios — julio 2026

> **Fuente oficial** del catálogo de materiales usado por la calc.
> Supersede a `../lista-precios-2026-05/` **solo en materiales** (COLORS_DB).
> Piletas (BACHAS_DB) NO cambia: sigue vigente `../lista-precios-2026-05/piletas.xlsx`.
> **Cargado en calc:** sí — v3 (definitiva, "ahora sí"), commit 2026-07-29.
> La v1 del mismo día quedó reemplazada: la v2 corrige precios (Guidoni
> baja ~25-30%, algunos granitos suben), limpia los duplicados de Pura
> que la v1 traía, y elimina 16 filas redundantes. 664 colores.

## Formato del archivo

Una sola hoja ("Copia de Para la Calculadora"), dos columnas:
- **Col A** — nombre del color (o header de sección/subgrupo, sin precio)
- **Col B** — precio FINAL para la calc (columna "caculadora")

A diferencia de la lista de mayo, ya no hay columnas intermedias: el precio
de la col B se carga tal cual (redondeado a 2 decimales).

## Resultado de la carga

691 colores (antes 559):

| Marca | Items | Currency |
|---|---|---|
| Marmol | 194 + 1 ARS | USD |
| Suprastone | 125 | USD |
| **Cuarcita** | **81** | USD |
| Neolith | 50 | USD |
| Pura | 32 | USD |
| Prima | 43 | USD |
| Dekton | 42 | USD |
| Guidoni | 29 | USD |
| Xtone | 27 | USD |
| Granito_i | 23 | USD |
| Granito_n | 17 | ARS |

## Cambio de mapeo vs mayo 2026

**Las cuarcitas ahora van al material `Cuarcita`** (agregado a la calc en
julio 2026). En mayo caían en `Marmol` porque el material no existía.
Secciones afectadas: `CUARCITAS IMPORTADOS`, `Cuarcitas y Granitos
Exoticos`, `Cuarcitas Cantera del Mundo`.

El resto del mapeo sección→marca es el mismo de
`../lista-precios-2026-05/README.md`, más:
- `Stefano -Terrazo` → Marmol (no hay material Terrazo)
- `MARMOL BLANCOS VETEADOS - CANTERAS` → Marmol

## Duplicados con precio distinto (decisión: gana la primera aparición)

El xlsx trae ~15 nombres repetidos DENTRO del mismo material con precios
distintos (distintos GRUPOS). Se conservó la primera aparición. Los más
notorios, por si hay que corregir la fuente:

- Pura: familia "(J)" duplicada entre GRUPO 3 (846,62) y GRUPO 4 (1.218,75)
- Cuarcita: Patagonia (660,66 vs 2.380,62) · Super White · Salvatore · Volga Blue · Fusion
- Marmol: Statuarietto (1.431,43 vs 1.218,75) · Grigio Carnico (1.218,75 vs 446,88)
- Suprastone: Calacatta (DP)(BM)* (1.381,25 vs 1.686,75)

Los repetidos ENTRE materiales distintos (ej. Calacatta Gold en Guidoni y
en Suprastone, Taj Mahal en Cuarcita/Prima/Marmol) son legítimos y se
conservaron todos.

## Correcciones manuales sobre la v2

- **Piedra Caliza Patagonica**: figura en una sección USD pero su precio
  (224.939) es claramente ARS (en mayo era "AR$55000"). Cargada como ARS.
- **Calacatta Plus** y **Portoro Super** a USD 7.312,50: se cargaron tal
  cual (mármoles de lujo), pero vale confirmar que no sea otro error de
  moneda en la planilla fuente.

## v3 (definitiva)

vs v2: renombra "Calacatta, Calacatta Arni" -> "Calacatta Armi", elimina la
fila con typo "Statuario Exra". Sin cambios de precio.

## Bug del parser corregido (v3-fix)

Las primeras cargas de julio dejaron **Silestone en 0 colores**. Causa: los
colores de esa marca se llaman "Silestone Blanco Zeus", "Silestone White
Storm"..., y el parser detectaba la sección con 
ANTES de mirar el precio — así que cada color se interpretaba como
encabezado de sección y se descartaba.

**Regla del parser, de acá en adelante: una fila CON precio es siempre un
color, nunca un encabezado.** El chequeo de sección solo corre sobre filas
sin precio. Con eso volvieron las 28 filas de Silestone (663 -> 691).

Es un riesgo latente para cualquier marca cuyos colores lleven el nombre
de la marca adelante (Xtone, Prima, Neolith...). Al cargar una lista
nueva, verificar que TODAS las marcas de MATS tengan colores > 0.
