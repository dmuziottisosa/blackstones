# BLACKSTONES MARMOLERÍA — BRIEF TÉCNICO COMPLETO
**Última actualización:** mayo 2026 · **Estado:** producción · **Hostinger** · `blackstones.com.ar`

> Este documento te pone al día en 3 segundos. Si lo agarra otra IA, leyéndolo entero entiende: contexto del cliente, decisiones de marca, estructura de la landing y arquitectura completa de la calculadora con sus exports a PDF/Excel.

---

## 0. CONTEXTO DEL CLIENTE — leer esto primero

**BlackStones Marmolería** (rebrand reciente desde **VenarStones** — el dominio viejo `venarstones.com.ar` y el handle `@venarstones.ar` ya no se usan; quedan como referencia en el journal pero el código está limpio).

Marmolería sofisticada en CABA, **Av. Juan Bautista Alberdi 3575**. Vende mesadas premium: granito, mármol, cuarzo (Silestone, Technistone, Purastone, Pura Prima) y piedra sinterizada (Dekton, Neolith, Xtone, Suprastone). Apunta al avatar "Carolina" (mujer 38–55, ABC1, en medio de obra) + arquitectos + desarrolladores. Tono editorial estilo revista Living, no taller de barrio.

**Contacto operativo:**
- Cel WhatsApp: `+54 9 11 2468-5820` (link `wa.me/5491124685820`)
- Oficina: `5611-5919`
- Email: `contacto@blackstones.com.ar`
- IG: `@blackstones.ar`
- Reseñas Google heredadas del rebrand: 89 reseñas (rebranding pendiente en Google Business Profile).

**El usuario** habla español argentino informal, **suele escribir en MAYÚSCULAS**, demanda iteración rápida, tiene opiniones fuertes de diseño. Si te dice "estilo Rolex no Patek" significa: prefiere lo legible y sólido sobre lo recargado-snobby. No usa emojis salvo para subrayar. No acepta lenguaje genérico de marketing. Las dos referencias documentales obligatorias son `avatar_marmoleria_caba_gba.md` (avatar + tono) y `whatsapp_flow_marmoleria.md` (flujo de venta).

---

## 1. IDENTIDAD VISUAL DE MARCA — paleta y reglas

```
#FAF8F4  crema (~70% de la superficie, fondo principal)
#1A1816  dark (~20%, headers, totales, marca)
#C4A77D  dorado (~10%, acentos, CTAs, highlights)
```

**Variantes derivadas que se usan en el código:**
- `#F2EDE3` crema-secundario · `#D4B07A` dorado-claro · `#7A6649` dorado-oscuro/texto-acento
- `#A89580` dorado-muted (texto sutil tipo m² embebidos) · `#2A2522` dark-secundario
- `#EDE5D0` ST (subtotal de sección, crema dorado) · `#F5EFE2` ST2 (subtotal de grupo, crema intermedio)

**Tipografías:** Fraunces (serif, headers editoriales), Montserrat (sans display), Manrope (UI sans).

**Reglas de tono visual:**
- Nada de gradientes, sombras pesadas, neón, brillos. Flat siempre.
- Borde dorado fino como acento (1.5px top en subtotales, 3px lateral en cards).
- Esquinas redondeadas suaves (`border-radius` 4–12px). Nada de pills con border parcial (rompe el ojo).
- Sentence case en todo. Nunca ALL CAPS salvo eyebrows muy chicos con `letter-spacing`.

---

## 2. ESTRUCTURA DEL REPO / FILESYSTEM

```
/mnt/user-data/outputs/
├── blackstones_landing.html         ← landing pública (single-file, ~6.608 líneas)
├── BlackStones_BrandBook_v1.pdf
├── dolar.php                         ← scraper dólar oficial (DolarHoy)
├── robots.txt
└── calculadora_auth/                ← calculadora protegida con login
    ├── calc.html                    ← app calculadora (single-file, ~4.704 líneas)
    ├── index.php                    ← entry point: si auth válida sirve calc.html
    ├── login.php                    ← form de password
    ├── auth_check.php               ← lógica HMAC firmado de cookie
    ├── auth_config.php              ← hash bcrypt del password + secret HMAC
    ├── logout.php
    └── .htaccess                    ← bloquea acceso directo a calc.html y a archivos sensibles
```

**Hosting:** Hostinger. La calculadora vive en `blackstones.com.ar/calculadora/` (el path `/calculadora/` está hardcodeado en la cookie path del `auth_config.php`).

---

## 3. LANDING PAGE — estructura y decisiones

### 3.1 Filosofía de la landing
Editorial, no comercial. El cliente no compra "una mesada" — compra **certeza de plazos** + pertenencia al circuito de diseño. Todo el copy se filtra por "¿lo leería Carolina sin cerrar la pestaña? ¿una arquitecta lo respetaría? ¿podría salir en una nota de La Nación Living?". Si las 3 son sí, está al nivel.

**Promesas estructurales que aparecen en hero y se repiten:**
- Plazos cumplidos por escrito.
- Asesoramiento previo, no venta.
- Honestidad sobre el material (granito sin garantía vs sinterizado con garantía 25 años).

### 3.2 Secciones (en orden, con sus ids)
| # | id / class | Qué contiene | Decisiones |
|---|---|---|---|
| 1 | `.visit#inicio` | Hero, propuesta, botón WhatsApp | foto cocina terminada, no mockup |
| 2 | `.instagram` | Embed feed @blackstones.ar | rebrand desde @venarstones.ar |
| 3 | `.materials#materiales` | 3 cards: Granito · Cuarzo · Sinterizado | "el clásico noble · el moderno funcional · el premium tecnológico" |
| 4 | `.services-ticker` | Marquee horizontal de servicios | "Hacemos:" + items en loop |
| 5 | `.catalog#colores` | **Catálogo principal** — 91 tiles + filtros | ver §3.3 abajo |
| 6 | `.reviews#resenas` | 8 reseñas Google curadas | testimonios reales, foto del cliente |
| 7 | `.process#proceso` | 5 pasos: Cotizás → Medimos → Aprobás → Fabricamos → Colocamos | con días asignados |
| 8 | `.gallery#galeria` | Antes/después + cocinas terminadas | con barrio + estudio de arquitectura |
| 9 | `.includes` | Qué incluye / qué no incluye | transparencia explícita |
| 10 | `.architects` | Sección B2B para estudios | puerta lateral, no en home principal |
| 11 | `.faq#faq` | FAQ que ataca objeciones reales | tiempo, presupuesto, garantía, mediciones |
| 12 | `.manifesto#por-que-nosotros` | Manifiesto: "no vendemos piedra" | tono editorial fuerte |
| 13 | `.about#nosotros` | Quiénes somos + foto del fundador | humaniza la marca |
| 14 | `.final-cta` | CTA final WhatsApp + botón flotante | wa.me directo |

### 3.3 Catálogo de colores — §clave para entender la landing
**91 colores totales** clasificados manualmente por nosotros:

```
Granito (14 tiles): 6 claros · 5 oscuros · 0 claro-vetas · 3 oscuro-vetas
Cuarzo  (40 tiles): 14 claros · 7 oscuros · 14 claro-vetas · 1 oscuro-vetas · 4 sin-clasificar
Sinterizado (52 tiles):
  Xtone:      7 claros + 3 oscuros + 8 claro-vetas + 3 oscuro-vetas + 2 sin-clasif
  Suprastone: 5 claros + 2 oscuros + 11 claro-vetas + 7 oscuro-vetas + 4 sin-clasif
```

**Filtros adaptativos:**
- Fila 1: por **marca** (Granito · Cuarzo · Sinterizado · subtipo) con counts dinámicos.
- Fila 2: por **tipo** (`Todos / Claros / Oscuros / Claros con vetas / Oscuros con vetas`) con counts dinámicos.
- Si un chip queda con count=0 según el filtro de marca → se oculta con clase `is-hidden-chip`.
- Si el chip activo se queda vacío → fallback automático a "Todos".
- Funciones JS: `updateMarcaFilterCounts()` + `updateTypeFilterCounts()`.

**Tile structure:** `<button class="color-tile" data-tipo="oscuro-vetas">` — el `data-tipo` es la clave del filtrado. El imagen del tile es un `.webp` (no PNG) para performance.

**Show-more:** `getShowMoreLabel()` arma el texto del botón en función del tipo activo (ej: "Ver 8 cuarzos claros más").

### 3.4 Decisiones de copy importantes
- **Granito sin garantía** se dice explícito: "El granito no tiene garantía de fábrica por sus características naturales". Esto suma confianza, no resta.
- En **Materiales** la nota técnica clave: "Los oscuros son menos porosos que los claros — los claros tienden a mancharse con vino, café o aceite."
- En **Reseñas** se removieron menciones a "Venar" (rebrand). Hay 0 ocurrencias de "Venar" en la landing actual. La línea original "los chicos de Venar" quedó como "los chicos"; "Conocí Venar" como "Los conocí".
- 5 instancias de `wa.me/5491124685820` distribuidas en hero, sticky button, after-reviews, before-FAQ, final-cta.

---

## 4. CALCULADORA — origen, lógica core y arquitectura

### 4.1 Cómo arrancó
Empezó como un Excel manual interno con fórmulas (v1–v7). Cuando se volvió impráctico se migró a una **app web single-file** (`calc.html`) con:
- Estado en memoria JavaScript (sin backend) — `D`, `VARS`, `ZOCALOS`, `BACHAS`.
- Exports nativos: PDF (vía `window.print` con CSS embebido) y Excel (vía librería `ExcelJS` desde CDN).
- Auth simple en PHP (HMAC firmada en cookie, no DB) para protegerla del público porque maneja precios reales.
- Scraper de dólar oficial vía `dolar.php` que pega contra DolarHoy y se cachea.

La razón de ser single-file: el dueño quería poder editarla él mismo si era necesario sin tocar build tools. Todo es HTML + CSS + JS plano, **sin frameworks, sin npm, sin bundler**. Las dependencias externas que carga el navegador son CDN: ExcelJS para Excel, nada más.

### 4.2 Auth (PHP, HMAC firmado en cookie)
- **Password actual:** `roma__blue` (hash bcrypt en `auth_config.php`).
- **AUTH_SECRET:** `cf812bc9a466251112a766a86508169f714abf979ecf54c4d8edb1e1b05c04be` (HMAC para firmar la cookie).
- **Cookie:** `bs_auth`, path `/calculadora/`, vida 60 días.
- **Rate limit:** 5 intentos fallidos por IP → bloqueo 15 min.
- **Flujo:** `index.php` chequea cookie → si OK lee `calc.html` y lo sirve con headers anti-cache → si no redirige a `login.php`.
- **`.htaccess`:** bloquea acceso directo a `calc.html`, `auth_config.php`, `auth_check.php`, archivos ocultos. `DirectoryIndex index.php index.html` para forzar prioridad.
- Para regenerar password: `php -r "echo password_hash('NUEVO', PASSWORD_BCRYPT);"`.
- Para regenerar secret: `php -r "echo bin2hex(random_bytes(32));"` (esto invalida todas las sesiones activas).

### 4.3 Modelo de datos en memoria
```javascript
// Secciones principales (5 tipos de mesadas)
const D = {
  m: {qty:1, items:[]},   // Mesada de cocina
  a: {qty:1, items:[]},   // Alzada
  l: {qty:1, items:[]},   // Mesada en L (1 o 2 piezas, controlado por #pzL)
  i: {qty:1, items:[]},   // Isla
  b: {qty:1, items:[]}    // Mesada de baño (Bacha armada o Con Traforo)
};
// Zócalos > 5cm (parte de adicionales, sumun al total)
const ZOCALOS = {qty:1, items:[]};
// Variantes (alternativas de color que NO suman al total — solo referenciales)
const VARS = {items:[]};
// Bachas y accesorios (precios fijos en ARS desde catálogo BACHAS_DB)
const BACHAS = {items:[]};

// Shape de un item de mesada:
function mk(mat='') {
  return {
    mat,        // material: 'Guidoni'|'Granito_n'|'Granito_i'|'Marmol'|'Xtone'|'Pura'|'Prima'|'Dekton'|'Silestone'|'Neolith'|'Suprastone'
    color: '',  // string libre, autocompletado desde COLORS_DB
    d1:0, d2:0, d3:0, d4:0,  // dimensiones (ver §4.4)
    price: 0,   // $/m²
    mon: 'USD', // 'USD' o 'ARS'
    reg: 'Sin', rv: 0,        // regrueso (cm de borde extra)
    ag: 'Sin', agPrice: 0,    // agujeros: 'Sin'|'Bacha'|'Anafe'|'Ambos'
    nLat: 1, caraInt: 'No', altoLat: 0.90,  // específico de Isla
    tipo: 'Bacha armada',     // específico de Mesada de baño: 'Bacha armada'|'Con Traforo'
    tag: ''                   // etiqueta libre para agrupar items (ej: "Cocina principal", "Cocina secundaria")
  };
}
```

### 4.4 Convenciones de dimensiones (críticas para entender los exports)
| Sección | d1 | d2 | d3 | d4 |
|---|---|---|---|---|
| Mesada (`m`), Alzada (`a`) | Largo | Ancho | — | — |
| **Mesada en L 1 pieza** | Largo total | (no se usa) | Ancho | — |
| **Mesada en L 2 piezas** | Largo A | Ancho A | Largo B | Ancho B |
| Isla (`i`) | L tapa | A tapa | — | A lateral |
| Mesada de baño Bacha armada (`b`) | L exterior | A exterior | L interior | A interior |
| Mesada de baño Con Traforo | L | A | — | — |

La función `calcM2(secKey, item)` devuelve los m² calculados según esta convención. **Esto es el corazón de toda la calc**.

### 4.5 Bases de datos embebidas en `calc.html`
- **`COLORS_DB`** — 438 entradas de colores con `{n: nombre, m: material, p: precio, c: moneda}`. Power el autocompletado del input "color" (función `searchColors(query)` con scoring fuzzy + aliases tipo `'pure white' → 'blanco puro'`).
- **`BACHAS_DB`** — 137 bachas con `{c: categoría, k: código, d: descripción, p: precio en ARS}`. Power el autocompletado de la sección Bachas (`searchBachas(query)`).
- **`MAT_LABELS`** — mapeo de keys cortas (`'Pura'`) a labels presentables (`'Purastone'`).
- **`SINT`** — array `['Xtone','Prima','Neolith','Dekton','Suprastone']` para identificar materiales sinterizados (que tienen reglas específicas de regrueso).

### 4.6 UI de la calculadora — secciones visibles
1. **Cliente** — datos del presupuesto (n°, fecha, cliente, DNI, dirección, celular).
2. **5 secciones de cotización** (Mesada / Alzada / Mesada en L / Isla / Mesada de baño) — cada una con select de cantidad y, en el caso de L, también select de piezas (1/2).
3. **Servicios adicionales** — flete (con select de zona predefinida o monto manual), subidas de escalera, ángulos/ménsulas.
4. **Zócalos > 5cm** — sub-bloque de adicionales (zócalos hasta 5cm vienen sin cargo, mayores se cotizan aparte).
5. **Otros conceptos** — extras manuales (nombre + monto + moneda).
6. **Bachas y accesorios** — buscador de catálogo BACHAS_DB.
7. **Variantes** — alternativas de color que se cotizan en paralelo pero **no suman al total** (referenciales).
8. **Desglose** — grand total con USD/ARS, IVA condicional, opciones de descarga.

**Opciones del bloque de descarga (4 checkboxes):** Total · Seña · Saldo pendiente · Total en ARS. Controlan qué bloques aparecen en PDF/Excel.

### 4.7 Sistema de etiquetas (tag) y subtotales agrupados
Cada item puede tener un `tag` (string libre). En PDF/Excel:
- Si NO hay tags → items se listan derecho.
- Si hay 1 tag → se muestra header `▸ Cocina principal` pero no subtotal por grupo.
- Si hay 2+ tags distintos → cada grupo muestra subtotal `Subtotal · {tag}` con fondo crema intermedio (#F5EFE2 en light).

Función central: `groupItemsByTag(secKey, validator)` retorna `[{tag, items: [{idx, it}]}]`.

### 4.8 Variantes (alternativas de color)
Una "variante" es: un color + material + precio + moneda + selección de qué items principales reemplaza (`v.selection = {m: [0,1], i: [0]}` significa "aplicar este color a items 0 y 1 de Mesada y al item 0 de Isla").

`calcVariantTotal(v)` retorna `{total, m2, mon, hasItems, sections: [{key, name, rows: [{variantItem, m2, total}], ...}]}`.

En el desglose se muestran como "Alternativas de color · referenciales (no suman al total)" con header dorado oscuro.

### 4.9 Dólar oficial
- Endpoint propio: `dolar.php` scrapea DolarHoy.com y devuelve JSON con `{venta, compra, fecha}`.
- En la calc: variable global `DOLAR_VENTA` se carga al inicio. Si > 0, se habilita el bloque "Total en ARS" que convierte USD → ARS al cambio del día y suma con los ARS nativos.
- Si el scraper falla → `DOLAR_VENTA = 0` y se muestra "consultar al momento del pago".

---

## 5. EXPORTS PDF Y EXCEL — formato actual (post-refactor de mayo)

### 5.1 La decisión clave de mayo 2026
**Antes:** cada item ocupaba 2 filas (una con dimensiones, otra con color/m²/precio). Tabla de 7 columnas. Era prolijo en items aislados pero confuso visualmente con muchos items.

**Ahora:** cada item ocupa **1 sola fila** con detalle consolidado. Tabla de **6 columnas**:

```
# | Detalle (concepto · color · material) | L (m) | A (m) + m² embebido | USD | ARS
```

### 5.2 Convenciones del nuevo formato
- **Detalle consolidado** ej: `Mesada de cocina · Negro Brasil (Granito Estándar)`. El color en bold, el material en italic dorado entre paréntesis.
- **Sub-línea italic** debajo del Detalle para info extra (en italic dorado #7A6649, font 10px PDF / 8.5px Excel):
  - Mesada en L 2 piezas: `2 piezas (largos 3.00 m + 1.80 m)`
  - Bacha armada: `interior 0.45×0.35`
  - Isla: `+ 2 laterales (alto 0.90m × ancho 0.40m) · cara ext+int`
  - Adicionales: `+ ag. bacha`, `+ traforo gas + traforo tomas`, etc.
- **m² embebido** debajo del A (m) en gris claro #A89580, fuente más chica (9px PDF, 8px Excel italic).
- **Solo USD o ARS** según moneda del item — la otra columna muestra "—".

### 5.3 PDF (HTML printable via `window.print`)
- CSS embebido en el HTML que se abre en nueva ventana.
- Formato A4 portrait, margen 10mm.
- Header con logo + datos cliente + n° presupuesto.
- Tabla principal con header dark `#1A1816` + texto crema.
- Subtotales con borde superior dorado 1.5px y fondo `#EDE5D0` (sección) o `#F5EFE2` (grupo).
- Bloques finales: Subtotal / IVA (condicional) / TOTAL (verde USD + azul ARS) / SEÑA (50% editable) / SALDO PENDIENTE / TOTAL EN ARS combinado (si está activado).
- Notas finales: Forma de pago · Alcance · Mediciones · Materiales · Instalación.

### 5.4 Excel (vía ExcelJS desde CDN)
- 6 columnas con widths: `[6, 52, 10, 11, 15, 17]`.
- Mismo flujo que PDF pero adaptado a celdas:
  - **richText** para celdas con sub-línea (Detalle) o m² embebido (A m²).
  - `mergeCells` ajustadas: `A:F` para headers de sección, `A:D` para merge de subtotales (USD col 5 + ARS col 6).
  - `topBorderR(r)` aplica borde superior dorado a toda la fila para subtotales de sección.
  - Altura de fila: 34px si hay sub-línea, 26px sin.
- Formulas dinámicas: SALDO = TOTAL - SEÑA usando `{formula: 'E${totalRow}-E${senaRow}'}`.
- `numFmt` por columna: `'"USD" #,##0.00'` para USD, `'$ #,##0'` para ARS.

### 5.5 Funciones core de los exports
**PDF (`generarPDF()` en calc.html línea ~4252):**
- `addSec(key, label)` — pinta una sección completa con todos sus items + subtotales por grupo + subtotal de sección.
- Bloque de variantes con loop sobre `validVarsPdf` y `calcVariantTotal(v).sections.forEach(...)`.
- Bloques separados para Servicios, Zócalos, Otros conceptos, Bachas (cada uno con su estructura particular pero respetando el formato 6-col).

**Excel (`generarExcel()` en calc.html línea ~3597):**
- `_buildItemDetail(secKey, it, catLabel)` — helper que retorna `{mainText, subText, dimL, dimA}` aplicando la lógica especial de cada sección. **Esto es el helper más importante del Excel** porque centraliza la construcción del detalle.
- `addItem(r, it, m2, total, catLabel, secKey)` — pinta UNA fila por item usando `_buildItemDetail`. Suma a `sumUSD` o `sumARS` según moneda.
- `renderSectionGrouped(secKey, catLabel)` — renderiza una sección entera con tags + subtotales. Reemplazó a 5 funciones viejas (`_descMesada`, `_descAlzada`, etc.) que ya no existen.
- Para variantes: se llama a `addItem` pero con un `variantIt = {...rowData.variantItem, color: v.color, mat: v.mat, mon: v.mon}` y se **revierte el side-effect** de las sumas (`sumUSD = beforeUSD; sumARS = beforeARS`) porque las variantes no suman al total.

### 5.6 Estado de los exports (mayo 2026)
- ✅ PDF totalmente migrado a 6 columnas: header, addSec, variantes, servicios, zócalos, otros, bachas, totales, separadores.
- ✅ Excel totalmente migrado a 6 columnas: header, helpers (`_buildItemDetail`, `addItem`, `addSectionSubtotal`, `addTagHeader`, `addGroupSubtotal`), `renderSectionGrouped`, variantes, servicios, zócalos, otros, bachas, totales, notas finales.
- ✅ Test runtime con ExcelJS confirmó generación correcta. Caso de prueba: Mesada de cocina + Mesada en L (1 pieza) + Bacha armada + Isla con 2 laterales — todos los m² calculados bien y todas las celdas richText renderizadas.
- ✅ JS sintaxis OK (`node --check`).

---

## 6. SANEAMIENTO ESTÉTICO DE LA CALCULADORA (en pantalla, antes de los exports)

Pasada de polish que ya está aplicada en la UI:
- **Cards de cliente, flete, opciones de descarga, desglose** con barra dorada lateral 3px.
- **Card del desglose** con h3 acompañado de punto dorado decorativo.
- **Botón principal de cálculo** con borde dorado en hover + sombra dorada + active state con `transform:translateY(0)`.
- **Grand total** con marco dorado interior + sombra externa profunda + inset shadow superior.
- **Header de variantes** (vars-head) con barra dorada lateral en fondo crema.
- **Filas de subtotal en pantalla**: subt-sec con bg #EDE5D0 + border-top dorado, subt-grp con bg #F5EFE2.
- **Dark mode** soportado: subt-sec → bg #3A332A texto #D4B07A, subt-grp → bg #2F2A24 texto #A89580.

---

## 7. WORKFLOW DE TRABAJO CON ESTE USUARIO (prácticas)

1. **Antes de tocar código grande** — armar mockup en visualize:show_widget para que vea el cambio. Si lo aprueba, recién ahí cambiar el HTML.
2. **Preguntar antes de inventar** — cuando hay decisiones de diseño con más de 1 opción razonable, usar `ask_user_input_v0` con 2–3 alternativas concretas. No mostrar 5 ideas vagas.
3. **Iteración por bloques** — el archivo `calc.html` es grande (270k+); cambios masivos se hacen con `str_replace` apuntando a bloques bien acotados. Después siempre `node --check` sobre el `<script>` extraído para confirmar que no rompiste sintaxis.
4. **Test runtime de Excel** cuando se toca la generación: `npm install exceljs --silent` + script Node que reproduce el flow con datos de prueba. Confirma que las celdas no quedan en columnas inexistentes ni con mergeCells inválidos.
5. **Naming consistente entre PDF y Excel** — si cambiás algo en uno, cambialo en el otro. Los dos exports tienen que decir lo mismo. El test mental es "si imprimo el PDF y lo comparo con el Excel, ¿son la misma información?".
6. **No agregar dependencias** salvo que no haya manera. ExcelJS está cargado por necesidad real. Todo lo demás es plain JS/CSS/HTML.
7. **Cuando termines un cambio** — usar `present_files` con el `calc.html` actualizado al final. No explicar mucho, el archivo habla solo.

---

## 8. PENDIENTES ABIERTOS (al cierre de mayo 2026)

- [ ] **Google Business Profile** — rebrand VenarStones → BlackStones (89 reseñas a migrar).
- [ ] **Robots.txt + sitemap.xml** + Google Search Console.
- [ ] **GA4** medible con eventos de WhatsApp clicks.
- [ ] **Mobile**: verificar gap del lado derecho en algunas tablas de la calc.
- [ ] **Eventualmente**: pasar la calc a multi-currency más fino (hoy es USD/ARS, podría sumar EUR si entra Purastone directo).

---

## 9. CHEAT-SHEET RÁPIDO

```
Quiero tocar la landing                  → /mnt/user-data/outputs/blackstones_landing.html
Quiero tocar la calc                     → /mnt/user-data/outputs/calculadora_auth/calc.html
Quiero tocar auth                        → /mnt/user-data/outputs/calculadora_auth/auth_*.php
Quiero tocar el scraper de dólar         → /mnt/user-data/outputs/dolar.php
Empezar nueva sesión                     → leer este .md + journal.txt + transcript de la última sesión
Cambiar password de la calc              → php -r "echo password_hash('NUEVO', PASSWORD_BCRYPT);"
Agregar un color al catálogo de la calc  → buscar `const COLORS_DB=[` y agregar entrada `{n,m,p,c}`
Agregar una bacha al catálogo            → buscar `const BACHAS_DB=[` y agregar entrada `{c,k,d,p}`
Agregar un material nuevo                → tocar MAT_LABELS + (si es sinterizado) sumarlo a SINT
```

> **Nota del repo (mayo 2026):** este cheat-sheet se refiere al chat anterior (paths `/mnt/user-data/outputs/`). En este repositorio los paths equivalentes son:
> - landing → `site/public_html/index.html`
> - calc → `site/public_html/calculadora/calc.html`
> - auth → `site/public_html/calculadora/auth_*.php`
> - dolar → `site/public_html/calculadora/dolar.php`

**Filosofía resumida:**
> Single-file siempre. Plain JS/HTML/CSS. Editorial sobre comercial. Honestidad técnica sobre marketing vacío. Si una decisión visual no aporta a la confianza del cliente final, no va.

---

*Documento generado en mayo 2026 como handoff técnico — diseñado para que cualquier IA lo lea y entienda en 3 segundos sin contexto previo.*
