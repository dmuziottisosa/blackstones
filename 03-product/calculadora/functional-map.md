# Calculadora — mapa funcional v1.0

> **Estado:** layer-3 active belief. Extraído del código fuente al 2026-05-02. Línea base: `baseline-v1.0.md`.
>
> Lectura obligatoria antes de modificar cualquier archivo de `site/public_html/calculadora/`.

---

## 1. Para qué sirve

App interna de **cotización de mesadas en 24 hs**. La usa el equipo de BlackStones (no es pública). Genera Excel + PDF firmable que se manda al cliente por WhatsApp.

**Flujo del usuario:**

1. Login con password compartido (`roma__blue` al 2026-05-02).
2. Carga datos del cliente: nombre, dirección, DNI, celular, fecha.
3. Carga ítems en 5 secciones colapsables: **Mesada**, **Alzada**, **Mesada en L**, **Isla**, **Mesada de baño**.
4. Por cada ítem: material → color (autocomplete) → dimensiones → precio/m² → opcionales (regrueso, agujeros para bachas/anafes).
5. Suma adicionales: flete (zona-based), escalera/ángulos, zócalos >5 cm, extras custom, bachas/accesorios (catálogo en ARS).
6. Aplica IVA 21% si es con factura. Convierte USD→ARS al cambio del día (DolarHoy) si lo pide.
7. Exporta a Excel (ExcelJS) o PDF (browser print).

---

## 2. Stack y archivos

```
calculadora/
├── .htaccess              prioridad index.php · bloquea acceso directo a calc.html, auth_*, .auth_attempts.json
├── index.php              gate: auth_is_valid() ? readfile(calc.html) : redirect(login.php). Headers anti-cache.
├── login.php              form HTML + handler POST. Tema dark con logo. Diseño consistente con la marca.
├── logout.php             borra cookie + redirect a login.
├── auth_check.php         lógica HMAC + rate limiting por IP.
├── auth_config.php        ⚠️ secrets: AUTH_PASSWORD_HASH (bcrypt), AUTH_SECRET (HMAC), AUTH_COOKIE_NAME, lifetime, rate limits.
├── dolar.php              scraper DolarHoy oficial v5. Cache 5 min. Validación rango 1000-5000.
├── dolar_cache.json       runtime (regenerable). Resultado del último scraping.
├── .auth_attempts.json    runtime (regenerable). Rate limiting por IP. Limpia entradas >24 hs.
└── calc.html              ⭐ 4703 líneas, 273 KB. Toda la app: HTML + CSS + JS plano. Una sola dependencia externa (ExcelJS por CDN).
```

---

## 3. Auth — flujo completo

**Login flow:**

1. Usuario va a `/calculadora/` → Apache resuelve a `index.php` (vía `.htaccess`).
2. `index.php` llama a `auth_is_valid()`. Si falla → `header('Location: login.php')`.
3. `login.php` muestra form. POST con password.
4. `auth_check_password($pwd)` usa `password_verify` contra `AUTH_PASSWORD_HASH` (bcrypt en `auth_config.php`).
5. Si OK → `auth_login()` setea cookie `bs_auth` con valor `expires.firma_hmac`, redirect a `index.php`.
6. `index.php` valida cookie y `readfile('calc.html')`.

**Cookie:**
- Nombre: `bs_auth`
- Formato: `{timestamp_expiracion}.{hmac_sha256(timestamp, AUTH_SECRET)}`
- Atributos: `Secure`, `HttpOnly`, `SameSite=Lax`, `Path=/calculadora/`
- Vida: **60 días** (`AUTH_COOKIE_LIFETIME = 60*24*60*60`)
- Verificación: `hash_equals` contra timing attacks + check de no-expirada.

**Rate limiting:**
- Estado en `.auth_attempts.json` — diccionario `{ip: {count, last}}`.
- Tras **5 fallos** → bloqueo **15 min** desde el último intento.
- IP detectada por `HTTP_X_FORWARDED_FOR` (Hostinger detrás de proxy) con fallback a `REMOTE_ADDR`.
- Limpieza automática de entradas >24 hs cada vez que se registra un fallo.

**Lo que NO hace la auth:**
- No hay multi-usuario. Una sola password compartida por el equipo.
- No hay roles, audit log, ni "quién cotizó qué".
- No hay reset de password automático — se rota a mano editando `auth_config.php` con un nuevo hash bcrypt.

---

## 4. `dolar.php` — scraper de cotización

- Apunta a la **home de dolarhoy.com** (no a la página específica del oficial — más estable).
- 3 estrategias en cascada para parsear el HTML:
  1. Anclar en `href="/cotizaciondolaroficial"` y tomar los 2 primeros `<div class="val">`.
  2. Anclar en `aria-label="Link a Dólar Oficial"`.
  3. Anclar en el texto "Dólar Oficial" entre Blue y MEP.
- **Cache**: 5 min en `dolar_cache.json`. Override con `?nocache=1`.
- **Debug mode**: `?debug=1` devuelve HTML con info del scraping.
- **Validación de sanity**: si `venta < 1000` o `venta > 5000` → 500 (rangos absurdos para Argentina ⇒ scraping rompió).
- **Output JSON**: `{compra, venta, fuente, source, fechaActualizacion}`.

**Cuándo se llama desde `calc.html`:** automáticamente al cargar la página (línea 3573). Botón de refresh manual disponible. Fallback: input numérico para tipear el dólar a mano si el scraper falla.

**Bonus:** la calc también consulta `dolarapi.com` para el blue (display-only, no entra en el cálculo).

---

## 5. `calc.html` — modelo de datos

Toda la app vive en `calc.html`. Sin frameworks, sin bundler. Una sola dependencia: ExcelJS por CDN.

### 5.1 Globales

- **`MATS`** (línea 1264): array de 11 códigos de material:
  `Guidoni, Mármol, Granito_n, Granito_i, Xtone, Pura, Prima, Dekton, Silestone, Neolith, Suprastone`.
- **`SINT`** (línea 1280): subset de "sintetizados" — fuerzan regrueso L+A por default. Son: `Xtone, Prima, Neolith, Dekton, Suprastone`.
- **`COLORS_DB`** (líneas 1283-1722): ~200 colores. Cada entrada: `{n, m, p, c}` = nombre, código de material, precio, moneda (USD/ARS).
- **`BACHAS_DB`** (líneas 1726-1862): ~120 accesorios (bachas, válvulas, llaves, etc.). Cada entrada: `{c, k, d, p}` = categoría, código, descripción, precio en ARS.

### 5.2 Estado del presupuesto en curso

- **`D`** (línea 1926-1938): el presupuesto. Estructura:
  ```js
  D = {
    m: { qty: 1, items: [] },  // Mesada
    a: { qty: 1, items: [] },  // Alzada
    l: { qty: 1, items: [] },  // Mesada en L
    i: { qty: 1, items: [] },  // Isla
    b: { qty: 1, items: [] },  // Mesada de baño
  }
  ```
  Cada item:
  ```js
  { mat, color, d1, d2, d3, d4, price, mon,
    reg,    // 'Sin' | 'L' | 'A' | 'L+A'
    rv,     // valor de regrueso en cm
    ag,     // agujeros: 'No' | 'Bacha' | 'Anafe' | 'Ambos'
    agPrice, // precio manual de agujeros (solo Alzada)
    nLat, caraInt, altoLat, // específicos de Isla
    tipo,   // 'Bacha armada' | 'Con Traforo' (solo b)
    tag     // string opcional para agrupar en exports
  }
  ```
- **`ZOCALOS`** (línea 1928): zócalos >5 cm — `{items: [], qty}`. Cada item: `{color, mat, d1, d2, price, mon}`.
- **`EXTRAS`** (línea 1933): line items custom — `{name, amount, mon}`.
- **`VARS`** (línea 1937): hasta **5 variantes de color alternativas**. Cada una: `{color, mat, price, mon, selection: {secKey: [rowIndices]}, regMode, regManualVal}`. NO suman al total — son referenciales para mostrar "opción A / opción B" en el PDF.

### 5.3 Persistencia local

- LocalStorage guarda solo: estado de accordions abiertos/cerrados + estado de adicionales abierto.
- **El presupuesto NO persiste** entre sesiones (refrescás la página → empezás de cero). Si vas a cerrar el browser sin terminar, exportá el Excel.

---

## 6. Lógica de pricing

### 6.1 Cálculo de m² por sección (`calcM2()`, líneas 2280-2323)

| Sección | Fórmula |
|---|---|
| **Mesada (m)** | `(d1 + reg_L) × (d2 + reg_A)` — regrueso aplica solo si `reg` lo incluye |
| **Alzada (a)** | `d1 × d2` — sin regrueso |
| **Mesada en L (l)** | 1 pieza: `(d1 + reg) × (d3 + reg)`. 2 piezas: `(d1 + reg) × (d2 + reg) + (d3 + reg) × (d4 + reg)` |
| **Isla (i)** | Tapa: `(d1 + 2·reg) × (d2 + 2·reg)` + laterales: `nLat × (altoLat + 2·reg) × d4` + cara interior opcional |
| **Baño (b)** | **Bacha armada** (default): tapa `(d1+regL)·(d2+regA)` + interior `d3·d4` + **0,60 m² fijos**. **Cualquier otro tipo** (`Con Traforo` o futuros): solo tapa. |

**Por qué los 0,60 m² fijos en Bacha armada:** la bacha armada **no es un corte** — es una bacha construida con el mismo material de la mesada (granito, cuarzo, sinterizado), que requiere **trabajo manual técnico especializado** del marmolero: cortar las paredes interiores, lijarlas, pegarlas, sellarlas. Ese trabajo se factura como **0,60 m² adicionales fijos** independientemente del tamaño de la bacha. Es histórico — no hay UI para editarlo. Si el material o el tamaño cambian mucho, ese 0,60 fijo deja de reflejar la realidad y hay que revisarlo.

**Por qué solo `Bacha armada`:** la lógica antes tenía un bug latente — cobraba +0,60 a cualquier tipo que NO fuera `'Con Traforo'`. Si en el futuro se agrega un tipo nuevo (ej: "bacha apoyada", "bacha bajomesada simple"), incorrectamente recibiría el +0,60 sin merecerlo. Cambio aplicado el 2026-05-02: la condición se invirtió a "solo `Bacha armada` paga el +0,60". Default behavior preservado para los dos tipos existentes; defensivo frente a futuros.

`reg` viene en cm (`rv`), se convierte a metros (`/100`) en el cálculo.

**Nota sobre la nomenclatura de regrueso en Baño:** las primeras 4 secciones (Mesada, Alzada, L, Isla) usan los modos `Sin / L / A / L+A` (siglas geométricas). La sección Baño usa `Sin / Solo frente / Frente + 1 lat / Frente + 2 lat` — naming **del dominio de la marmolería** (mesadas de baño se describen con frente y laterales en taller). Es **intencional, no inconsistencia**. NO unificar al refactorizar.

### 6.2 Costo de agujeros (`agCost()`, línea 2126)

- **Items en USD**: 80 USD por agujero (Bacha o Anafe), 160 USD por ambos. Se duplica con `Ambos`.
- **Items en ARS**: 100.000 ARS por agujero, 200.000 ARS por ambos.
- **Alzada**: precio manual por fila (`agPrice`), se duplica si `Ambos`.

### 6.3 Total de cada item

```
m² × precio_por_m² + costo_agujeros
```

### 6.4 Total general (`calc()`, línea 2325)

1. Suma todas las secciones por moneda → `gusd`, `gars`.
2. **Flete** (solo ARS) — zona predefinida o monto manual.
3. **Escalera + Ángulos** — cantidad × precio unitario, cualquiera de las dos monedas.
4. **Zócalos >5 cm** — `m² × precio` por unidad.
5. **Extras custom** — nombre + monto + moneda.
6. **Bachas/accesorios** — solo ARS, código + descripción + cantidad × precio.
7. **IVA 21%** — si toggle "factura" activo. Aplica a USD y ARS independiente.
8. **Conversión USD→ARS** — opcional (`optTotalARS`). Si está activo y `DOLAR_VENTA > 0`, suma USD convertido a ARS.

**No hay**: descuentos, márgenes, fees fijos de medición/instalación. v1.0 es pure m² + recargos aditivos.

---

## 7. Catálogo de materiales (resumen)

Los precios están en `COLORS_DB`. Resumen de rangos:

| Material | Moneda | Rango aprox |
|---|---|---|
| Guidoni | USD/m² | 420–1150 |
| Mármol | USD/m² | 340–3360 (Portoro Extra es el techo) |
| Granito Nacional | ARS/m² | 189.040–308.210 |
| Granito Importado | USD/m² | 200–1200 |
| Dekton | USD/m² | 770–2300 |
| Purastone | USD/m² | 410–1020 |
| Pura Prima | USD/m² | 570–1100 |
| Neolith | USD/m² | 700–1330 |
| Silestone | USD/m² | 490–1700 |
| Suprastone | USD/m² | 540 (mayoría) – 600 (Black Bass, Crema Travertine) |
| Xtone | USD/m² | 600 (flat para todos los colores) |

Para precios exactos por color → leer `COLORS_DB` en `calc.html` líneas 1283-1722. **Es la fuente de verdad — este doc es resumen.**

---

## 8. Exports

### 8.1 Excel (`generarExcel()`, líneas 3597-3950)

**Orden de bloques en el archivo Excel exportado** (post-fix 2026-05-02):

1. Header BlackStones + datos del cliente.
2. Items principales por sección (Mesada, Alzada, L, Isla, Baño).
3. Servicios adicionales (flete, escalera, ángulos).
4. Zócalos > 5 cm.
5. Otros conceptos (extras manuales).
6. Bachas y accesorios.
7. **Subtotal General principal** (cuando toggle "Total" OFF) ó **Subtotal + IVA + TOTAL** (cuando ON).
8. SEÑA + SALDO PENDIENTE (si toggles activos).
9. TOTAL EN ARS (USD convertidos + ARS) — si toggle activo y hay USD + cotización.
10. **Variantes (Alternativas de color)** — render diferido al final, después del cierre del presupuesto principal. **No suman al total.** Cada variante tiene su "Subtotal Variante N" interno.
11. Tipo de cambio referencia.
12. Forma de pago, Alcance, Mediciones, Materiales, Instalación (legal).
13. Footer.

**Por qué las variantes van al final:** si las variantes se renderizan ENTRE los items principales y los adicionales (orden anterior), el "Subtotal General" del presupuesto principal aparece visualmente DESPUÉS de los "Subtotal Variante N", lo que rompe la jerarquía y confunde al cliente. Renderizándolas al final, el subtotal de lo principal queda contenido en su propia sección. Implementado vía closure `_renderVarsXls()` que se invoca después del bloque "TOTAL EN ARS".

- ExcelJS v4.4.0 vía CDN.
- 6 columnas: `CANT, DETALLE, L, A, USD, ARS`.
- **Header**: branding BlackStones (rows 2-3), contacto. Rows 8-9: cliente + dir + DNI + cel + fecha.
- **Toggles**: `incSena` (seña %), `incSaldo` (saldo %), `incTotal`, `incTotalARS`.
- **Paleta**: dark `#1A1816`, cream `#FAF8F4`, gold `#C4A77D`, green `#25D366`, blue `#0EA5E9` (consistente con el sistema visual).

### 8.2 PDF (`generarPDF()`, líneas 4277-4700)

**Orden de filas en el PDF exportado** (post-fix 2026-05-02): idéntico al Excel — items principales → adicionales → totales → seña/saldo → TOTAL EN ARS → **variantes al final**.

- **No usa librería externa.** Construye HTML, abre en window nuevo, dispara `window.print()`. El usuario imprime → "Guardar como PDF" en el dialog del browser.
- Implementación del fix: el bloque variantes acumula en `items` como antes, pero inmediatamente después se extrae con `items.substring(_varsBeforeLen)` y se vuelve a inyectar al final, justo antes del `const html = \`...\``.
- Footer con tipo de cambio referencia: "USD 1 = X ARS · DolarHoy".

### 8.2.1 Regla del "Subtotal General" (cuándo se muestra)

**Cuando toggle "Total" está OFF:**
- Si hay **2 o más** "Subtotal de sección" visibles → **se muestra** "Subtotal General" (suma de todos los subtotales).
- Si hay **0 o 1** "Subtotal de sección" → **NO se muestra** (sería redundante con el único subtotal existente, o sin información que sumar).

Cuentan como "Subtotal de sección":
- Subtotal de Mesada / Alzada / Mesada en L / Isla / Mesada de baño (cuando la sección tiene items).
- Subtotal Zócalos.
- Subtotal Otros conceptos.
- Subtotal Bachas.

NO cuentan: subtotales de tag dentro de sección (ej: "Subtotal · Cocina"), subtotales internos de variantes, items individuales sin subtotal (flete, escalera, ángulos).

Implementación:
- **Excel**: contador `_subtotalCount` declarado en `generarExcel()` antes de `addSectionSubtotal()`. La función incrementa el contador cada vez que se llama. La condición del Subtotal General agrega `&& _subtotalCount >= 2`.
- **PDF**: regex inline sobre `items` (la string HTML) en el momento de evaluar la condición — `(items.match(/Subtotal de secci[oó]n|Subtotal Z[oó]calos|Subtotal Otros conceptos|Subtotal Bachas/g) || []).length >= 2`. Funciona porque al momento del check, `items` ya contiene todos los subtotales del cuerpo principal (las variantes ya fueron extraídas a `_varsHtmlPdf` para reposicionarse al final).

Cuando toggle "Total" está ON: siempre se muestra Subtotal + IVA + TOTAL, regla anterior no aplica.

### 8.3 Estado "dirty"

Si después de exportar el usuario modifica algo, los botones de export se marcan **dirty** (visualmente: alguna indicación que el archivo bajado ya no refleja el presupuesto actual). Tiene que volver a exportar.

---

## 9. UI

- **Header dark**: logo + toggle de tema (sol/luna) + logout.
- **Client bar**: nombre, dir, cel, DNI, fecha.
- **Dolar bar**: cotización actual + botón refresh + input manual.
- **5 accordions colapsables** (Mesada / Alzada / L / Isla / Baño), cada uno con badge de cantidad.
- **Adicionales**: collapsible separado con flete, escalera, ángulos, zócalos, extras, bachas.
- **Variantes**: hasta 5 colores alternativos.
- **Totales**: m², USD, ARS, IVA, USD→ARS.
- **Desglose**: tabla detallada de cada concepto.
- **Export buttons**: Excel + PDF.
- **Footer legal**: disclaimers sobre características de piedras naturales y garantías (Dekton/Silestone con garantía vs piedras naturales sin garantía).
- **Mobile-first**: media queries desde línea 514.

---

## 10. Fragilidad conocida (cuidado al modificar)

| # | Riesgo | Dónde |
|---|---|---|
| 1 | Hardcoded de materiales — agregar uno toca al menos `MATS`, `MAT_LABELS`, `SINT`, `COLORS_DB`, y la rama de `isSint()`. | líneas 1264-1280 + scattered |
| 2 | Magic numbers en agujeros (USD 80, ARS 100.000). Sin tabla de lookup. | línea 2134 |
| 3 | Constante 0,60 m² fija en bacha (representa trabajo manual técnico especializado). Sin UI para editar. | línea ~2321 |
| 4 | Color autocomplete es substring. "Blanco" matchea "Blanco Fiesta" pero no "Fiesta Blanco". | líneas 1901-1921 |
| 5 | Zócalos >5 cm: precio manual. No hay lookup en `COLORS_DB`. | línea 2523 |
| 6 | Lógica de regrueso esparcida en `getR()`, `updMat()`, `applyVariantToItem()`. Cambiar una regla puede dejar inconsistencias. | 2273, 2597, 2692 |
| 7 | LocalStorage falla silencioso si quota llena. | líneas 1947, 2062 |
| 8 | Tipos de bacha (Armada / Con Traforo) hardcoded en múltiples branches de render y calc. | 2262, 2305, 2357, 3689, 4324 |
| 9 | Excel/PDF dependen de inputs de UI (`incTotal`, `incSena`, etc.). Cambiar el form rompe el export. | scattered |
| 10 | URL de `dolar.php` es relativa — funciona solo si calc vive en `/calculadora/`. Cambiar de path rompe silencioso. | línea 3547 |

---

## 11. Cosas que están bien (y no hay que tocar)

- **Sin referencias a VenarStones.** Rebrand limpio.
- **Sin TODOs ni código comentado.** Polished.
- **Auth con HMAC + rate limiting por IP.** Sólida para una app interna.
- **Scraper con 3 fallbacks + validación de sanity.** Bien armado.
- **Cookie `Secure + HttpOnly + SameSite=Lax`.** Correcta.
- **`.htaccess` bloquea acceso directo a `calc.html` y archivos sensibles.** Bien.
- **Headers anti-cache en `index.php`.** Cambios se reflejan inmediato.
- **Mobile-first responsive.** No es solo desktop.

---

## 12. Diferencia entre "modificar la calc" y "regenerarla"

- **Modificar** (cambio quirúrgico — corregir un precio, agregar un color, ajustar un texto): editar el bloque correspondiente, deployar el archivo cambiado vía PowerShell (Receta 2 de `04-operations/deploy-snippets.md`).
- **Regenerar** (reescribir lógica de pricing, sumar nuevos tipos de mesada, refactorizar): NO. Antes de eso → entry en `01-strategy/decision-log/`, plan, y test paralelo. La calc tiene 4703 líneas porque cada caso de borde costó horas de iteración con clientes reales. Reescribir desde cero perdería ese conocimiento implícito.
