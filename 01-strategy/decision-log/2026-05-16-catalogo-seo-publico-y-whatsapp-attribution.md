# 2026-05-16 · Catálogo público SEO + Refactor de carpetas + WhatsApp attribution

> **Status:** ✅ Implementado
> **Decision-log:** sesión grande mayo 16, 2026
> **Commits abarcados:** `448661f` → `24cfbaf` (15 commits)

---

## Knowns (qué sabemos)

- La landing tenía **1 sola página indexable** (`index.html`) compitiendo por "marmolería CABA" contra empresas con 300+ páginas.
- Tenemos **566 materiales** en el catálogo (`COLORS_DB` del calc) más **10 docs informativos** (regrueso, bacha armada, recomendaciones por uso, etc.) que vivían solo en el repo — invisibles para Google.
- Carpetas de imágenes desordenadas: mezcla de `Granitos/` (mayúscula) + `xtone/` (minúscula), extensiones `.jpg`/`.webp`/`.png`/`.jpeg` sin criterio.
- WhatsApp links de la landing iban a `wa.me/...` sin mensaje precargado → sin atribución de origen.

## Known unknowns (qué no sabemos)

- **Tasas de conversión** por página SEO (necesitamos 30+ días de tráfico para medir).
- **Search Console keywords reales** que traen tráfico — pendiente activar Google Search Console y enviar sitemap.
- **CTR por mensaje contextual de WhatsApp** vs WhatsApp sin mensaje — no hay benchmark previo.

## Unknown unknowns (humildad institucional)

- Si Google va a indexar las 12 páginas o las va a marcar como thin content (depende de cómo el algoritmo evalúe la profundidad real).
- Si el catálogo público va a "diluir" la sensación de exclusividad de la marca.
- Si los clientes van a sentir que el mensaje precargado de WhatsApp es invasivo (es editable, pero algunos pueden percibirlo raro).

---

## Lo que se construyó

### 1. Refactor de carpetas del catálogo (commit `448661f`)

Normalización de carpetas y extensiones de imágenes:

| Antes | Después | Estado |
|---|---|---|
| `Granitos/` (mayúscula, mix de `.jpg`+`.webp`) | `granitos/` (lowercase, 100% `.webp`, 32 archivos) | ✅ Limpio |
| `Purastone/` (mayúscula) | `purastone/` (lowercase, 78 `.webp` + 2 `.jpg`, 80 archivos) | ✅ Casi limpio |
| `Suprastone/` (mayúscula) | `suprastone/` (lowercase, 52 `.webp` + 6 `.jpg`/`.jpeg`, 58 archivos) | ⚠️ Conversión parcial |
| `xtone/` (lowercase mantenido) | `xtone/` (camelCase + lowercase, 46 archivos) | ⚠️ Detail files siguen camelCase |

- **177 substituciones** de paths en `index.html` (folder case + extensiones)
- **0 referencias rotas** post-refactor
- Agregados al catálogo de granitos (commit `f383f86`): **Blanco Dallas** y **Kashmir White**

### 2. Catálogo SEO público (commit `f1c7557`)

Estructura nueva en `site/public_html/`:

```
/sitemap.xml                          (13 URLs, prioridades calibradas)
/robots.txt                           (descomenta sitemap + bloquea /presupuestos/)
/assets/seo-pages.css                 (CSS compartido entre 12 páginas SEO)

/guias/
  index.html                          (listing de 5 guías técnicas)
  regrueso-mesada-que-es/
  bacha-armada-vs-traforo/
  diferencia-granito-cuarzo-sinterizado-marmol/
  que-necesito-para-cotizar/
  cuanto-sale-una-mesada/

/materiales/
  index.html                          (listing de 5 categorías)
  sinterizado/
  cuarzo/
  granito/
  cuarcita/
  marmol/
```

**Cada página incluye:**
- Meta tags SEO completos (`title`, `description`, `canonical`, OG, Twitter Card)
- Schema.org markup: `Article` / `CollectionPage` / `BreadcrumbList` / `LocalBusiness`
- Breadcrumb visible + estructurado
- Internal linking (cada página enlaza 3-5 relacionadas)
- CTA WhatsApp prominente
- Header + footer consistentes con landing
- **Sin precios USD/m²** (regla operativa: nunca exponer al cliente)
- **Sin referencias a `/calculadora/` ni `/presupuestos/`** (siguen bloqueados en robots.txt)

### 3. Iteraciones de calidad (commits `aff0af2`, `614fb69`)

**Bug fixes y mejoras técnicas:**

| Tipo | Cambio |
|---|---|
| Typo crítico | `"¿Se ralla?"` → `"¿Se raya?"` (cuarzo) — `rallar`=queso, `rayar`=cortar |
| Typo crítico | `"Calacata Quartz"` → `"Calacatta Quartz"` (cuarcita) — doble T correcta |
| Anglicismo | **"se etcha con ácidos"** → **"se opaca con ácidos"** — 18 reemplazos en 8 archivos |
| Anglicismo | `"productos de cal"` (ambiguo) → `"descalcificadores"` / `"limpiadores con ácido"` |
| Datos | Mármol `"sellado anual"` → `"sellado cada 6-12 meses según uso"` |
| Datos | Sinterizado `"viene de 12mm"` → `"viene en 8, 12, 20 y hasta 30 mm; 12 mm es lo más común"` |
| Datos | Cuarzo: agregado `"resina degrada por encima de ~150 °C"` (verificado con Caesarstone docs) |
| Datos | Granito: agregado `"tolera hasta ~250 °C sin marca"` |
| Datos | Sinterizado: agregada dureza Mohs 7-8 (verificada con expertos) |

**Fuentes verificadas:** Caesarstone US, Cosentino (Dekton), TheSize (Neolith), Marble World, Stone Tile Depot.

### 4. WhatsApp con mensaje precargado por página (commit `99face4`)

**40 links** actualizados en 13 páginas. Cada uno abre WhatsApp con mensaje contextual identificable por origen.

**Tabla de atribución para el equipo:**

| Mensaje entrante | Página origen |
|---|---|
| "Hola, vengo de la página y me gustaría cotizar una mesada" | Landing (4 botones: nav + CTAs) |
| "...vine de la sección de materiales..." | `/materiales/` (listing) |
| "...vine de la página de mesadas de **sinterizado**..." | `/materiales/sinterizado/` |
| "...vine de la página de mesadas de **cuarzo**..." | `/materiales/cuarzo/` |
| "...vine de la página de mesadas de **granito**..." | `/materiales/granito/` |
| "...vine de la página de mesadas de **cuarcita**..." | `/materiales/cuarcita/` |
| "...vine de la página de **mármol natural**..." | `/materiales/marmol/` |
| "...vine de las **guías técnicas**..." | `/guias/` (listing) |
| "...vine de la **guía de regrueso**..." | `/guias/regrueso-mesada-que-es/` |
| "...vine de la **guía de bacha armada**..." | `/guias/bacha-armada-vs-traforo/` |
| "...vine de la **guía comparativa de materiales**..." | `/guias/diferencia-granito-cuarzo-sinterizado-marmol/` |
| "...vine de la **guía de cotización**..." | `/guias/que-necesito-para-cotizar/` |
| "...vine de la **guía de precios**..." | `/guias/cuanto-sale-una-mesada/` |
| "Hola, soy arquitecto/estudio..." | Link arquitectos (preexistente) |

Preserva el link de arquitectos en landing que ya tenía `?text=` propio.

### 5. Dekton — 42 colores al catálogo (commit `52d7add`, fix Vigil `24cfbaf`)

Movido `Dekton.zip` → `site/public_html/dekton/` (84 archivos: 42 portadas + 42 muestras).

**Nombres preservados del zip:**
- Portadas con extensiones mixtas (`.avif`, `.webp`, `.jpg`)
- Muestras todas `.webp` con sufijo `_muestra.webp` (underscore, no hyphen — distinto a las otras carpetas)

**Distribución final por tipo:**

| Tipo | Cantidad | Colores |
|---|---|---|
| **Claros** | 13 | Ceppo, Danae, Grigio, Halo, Keon, Kreta, Marmorio, Nara, Nebbia, Nilium, Sirocco, Soke, Zenith |
| **Claros con vetas** | 19 | Aura, Aura Bookmatch, Awake, Bergen, Daze, Entzo, Kairos, Khalo, Lucid, Marina, Morpheus, Natura, Neural, Olimpo, Rem, Reverie, Salina, Vera, Vigil |
| **Oscuros** | 3 | Bromo, Domoos, Sirius |
| **Oscuros con vetas** | 7 | Kelya, Kira, Laurent, Opera, Radium, Somnia, Trilium |
| **Total** | **42** | |

**Cambios al panel sinterizado:**
- Subfilter "Todos" 52 → **94** entradas
- Nuevo chip **Dekton (42)** entre Xtone y Suprastone
- "Ver los 52 sinterizados" → "Ver los 94 sinterizados"

---

## Reglas operativas establecidas en esta sesión

### Regla 1 — Nunca exponer precio por m² al cliente
Aplicada en:
- Páginas SEO `/materiales/...` (sin precios visibles)
- Páginas SEO `/guias/cuanto-sale-una-mesada/` (factores cualitativos, no números absolutos)
- Pilar v1.4: §22 "Nunca revelar precio por m² del material como tarifa"
- Bot ejemplos USD/m² marcados con `⚠️ uso interno`

### Regla 2 — Calc y Hub son internos, no se exponen
- `robots.txt`: bloquea `/calculadora/` y `/presupuestos/`
- Ninguna página pública linkea o referencia esos paths
- Las páginas SEO leen el catálogo de COLORS_DB pero solo muestran nombre + descripción técnica, sin precios

### Regla 3 — Anti-anglicismo en docs públicos
"Etching" no existe en español RAE. Reemplazado por:
- "Se opaca con ácidos" (general)
- "Se daña con ácidos" (técnico)
- "Sensibilidad a ácidos" (cualitativo)

Aplicado en 8 archivos: 4 SEO públicas + Pilar v1.4 + Pilar v1 legacy + recommendation patterns + layer-1 synthesis.

### Regla 4 — Folder naming
- **Lowercase**: granitos/, purastone/, suprastone/, xtone/, dekton/ (todas)
- **Lowercase con hyphen** para nombres de archivo (preferido pero respetamos los del zip si vienen camelCase)
- **Sin espacios** en filenames

### Regla 5 — Imágenes .webp por default
- `.webp` para portada + muestra (carga rápida, SEO)
- `.avif` aceptable (browsers modernos lo soportan)
- `.jpg`/`.jpeg`/`.png` solo si no hay alternativa y son archivos crudos del proveedor

---

## Pendientes para próxima iteración

### Catálogo (vienen zips del cliente)
- **Silestone** (cuarzo) — pendiente cargar imágenes + agregar al panel cuarzo
- **Neolith** (sinterizado) — pendiente cargar imágenes + agregar al panel sinterizado
- **Cuarcita** — pendiente crear su propia categoría (hoy mal clasificadas como `Marmol` en COLORS_DB)
- **6 tiles sin-clasificar** (opcional): Alpinus White, Amazonite (Xtone), Brazilianite, Patagonia, Peacock, Saloran Grey (Suprastone) — aparecen en "Todos" pero no en filtros de tipo

### SEO operativo
- **Google Search Console** — verificar propiedad `blackstones.com.ar`, enviar `sitemap.xml`
- **Tracking de keywords reales** — 30-60 días para tener data confiable
- **Páginas individuales por material** (Fase 3) — solo para top 30-50 con búsqueda real, decidir basándose en data de GSC
- **OG image único por página** — hoy todas usan `foto_proyectos/proyecto-cocina-mesada.webp` (revisar si existe el archivo o usar `logo/og-image.png`)

### Mejoras opcionales (x2 por página)
Listadas en feedback de iteración 1 — pendientes de decisión usuario:
- Diagramas/imágenes en guías técnicas
- Mini-comparadores de marcas dentro de páginas categoría
- Lista de "colores top más pedidos" por material
- Sección "errores comunes / señales de cotización dudosa" en guías operativas
- Recomendador rápido con preguntas (uso, presupuesto, estética)

### Atribución de ventas
- **Acción para el equipo**: cuando llegue un mensaje por WhatsApp, leer el prefacio para identificar origen
- **Pilar (Business AI)**: puede reaccionar a esos prefacios con respuestas hiper-contextuales si se agrega sección en v1.5 del PDF

---

## Commits relevantes (orden cronológico)

| SHA | Cambio |
|---|---|
| `448661f` | Normalizar carpetas catálogo a lowercase + 177 substituciones en index.html |
| `f383f86` | Agregar Blanco Dallas + Kashmir White al catálogo granitos |
| `7d5f5fc` | Primeras 2 páginas SEO template (regrueso + sinterizado) |
| `f1c7557` | Catálogo SEO completo — 12 páginas + sitemap + robots actualizado |
| `aff0af2` | Typos + datos técnicos verificados con expertos |
| `614fb69` | Reemplazar anglicismo "etcha" → "se opaca con ácidos" (18 ocurrencias en 8 archivos) |
| `99face4` | WhatsApp con mensaje precargado contextual por página (40 links) |
| `52d7add` | Agregar 42 colores Dekton + filtros por tipo |
| `24cfbaf` | Reclasificar Vigil de claro a claro-vetas |

---

## Procedimientos de deploy

Todos los deploys se hicieron con el patrón establecido en `04-operations/deploy-snippets.md`:
- **Receta 1** (Base64 inline) para archivos chicos
- **Receta 2** (patch quirúrgico) para cambios literales en archivos grandes
- **GitHub archive download** vía `Invoke-WebRequest` para deploys masivos (200+ archivos) — usar SHA específico en URL para evitar cache CDN

**Comando tipo para próximos deploys** de catálogo masivo:
```powershell
$sha = "<commit-sha>"
$zipUrl = "https://github.com/dmuziottisosa/blackstones/archive/$sha.zip"
Invoke-WebRequest -Uri $zipUrl -OutFile $tmpZip -UseBasicParsing
Expand-Archive -Path $tmpZip -DestinationPath $tmpDir -Force
# Iterar archivos y subir via curl FTP
```

---

## Trigger de revisión

- Cuando llegue zip de Silestone o Neolith → repetir patrón Dekton (folder lowercase + clasificación + filtros)
- Cuando tengamos 30 días de Search Console data → decidir Fase 3 (páginas individuales por material)
- Cuando el equipo reporte que Pilar no atribuye bien la página de origen → actualizar mensajes de WhatsApp o reglas Pilar v1.5
- Default: revisión cada 90 días

---

## Cross-links

- `04-operations/deploy-snippets.md` — recetas PowerShell de deploy
- `02-marketing/whatsapp/knowledge/business-ai-knowledge-v1.4.md` — doc canónico Pilar
- `06-knowledge/layer-1-synthesis/2026-05-regrueso-explicado.md` — base técnica para guía regrueso
- `06-knowledge/layer-1-synthesis/2026-05-recomendaciones-material-por-uso.md` — base para guía diferencias materiales
- `06-knowledge/layer-3-active-beliefs/anti-genericness.md` — principio operativo aplicado en todas las páginas SEO
- `01-strategy/backlog.md` — items pendientes priorizados
