# WhatsApp Business AI 2026 — investigación previa

> **Status:** `layer-2-landscape` — investigación externa, mayo 2026.
> **Validez:** alta confianza en feature disponibility y restricciones 2026 · media en specs exactas de file upload (algunas fuentes confunden límites de WhatsApp Media vs Business AI knowledge).
> **Trigger de creación:** la marca va a crear su propio documento de conocimiento para entrenar la Business AI. Antes hay que entender qué acepta y qué prohíbe.

---

## El feature

**Nombre oficial:** "Business AI" (también referido como "Meta Business AI" o "Meta AI business assistant").

Es la respuesta oficial de Meta a la avalancha de chatbots de terceros (ChatGPT wrappers, etc.) que invadieron WhatsApp en 2024-2025. Meta lo lanzó globalmente en **Q1 2026** y lo expandió a 17 mercados de Latinoamérica.

**No es Meta AI consumer** (el chatbot azul para uso personal). Business AI es la versión para empresas, entrenable con documentos propios, integrada al número de WhatsApp Business.

---

## Disponibilidad en Argentina

✅ **Disponible desde 2026.** Argentina está en los 17 mercados de LATAM confirmados:
Argentina · Brasil · México · Colombia · Chile · Perú · Ecuador · Bolivia · Paraguay · Uruguay · Venezuela · Guatemala · Honduras · El Salvador · Costa Rica · Panamá · República Dominicana.

✅ **Soporta español.**

---

## Política AI de WhatsApp 2026 — qué se puede y qué NO

⚠️ **Crítico para BlackStones:** desde el 15 de enero 2026 hay nuevas reglas. Aplican a TODA AI en WhatsApp (tanto Business AI oficial como integraciones de terceros).

### ❌ PROHIBIDO

- AI conversacional de propósito general (open-domain)
- Wrappers de ChatGPT, Claude, Perplexity, etc. usando WhatsApp como canal
- Asistentes que generan contenido arbitrario fuera del servicio del negocio
- Cualquier IA que recolecte data de usuarios para entrenar otros modelos

### ✅ PERMITIDO (BlackStones cae acá)

- AI task-specific atado al servicio del negocio
- Customer support / triaje de tickets
- Confirmación de reservas / cotizaciones
- Updates de estado de pedidos
- Calificación de leads
- FAQ específicas del producto/servicio
- Recomendación de productos del catálogo propio

**Veredicto BlackStones:** el caso de uso (responder consultas técnicas sobre materiales, plazos, proceso, derivar a humano para cotización) está 100% dentro de lo permitido. La IA tiene que ser "ancillary to a legitimate business service" — y lo es.

---

## Eligibility y costo

| Item | Detalle |
|---|---|
| Meta Business Manager verificado | Requisito |
| Documentación legal del negocio | Registro, facturación, utility bill |
| Tiempo de verificación | 1-5 días hábiles |
| WhatsApp Business app vs Business Platform/API | Business AI funciona en ambos. La versión "rica" (más knowledge, más reglas) requiere Business Platform |
| Costo del feature | Gratis · solo se pagan los mensajes estándar de WhatsApp Business API |

**Importante:** el WhatsApp Business app gratuito (el que se instala en celular) ya permite Business AI básico sin necesidad de Business Suite. Para configuración más profunda → Meta Business Suite.

---

## Qué se puede cargar como knowledge

Según fuentes consultadas (no oficiales todas), Business AI acepta múltiples fuentes:

### Fuentes de conocimiento aceptadas

- **URLs / website** — el bot crawlea y aprende del sitio público
- **FAQs** estructuradas
- **Catálogo** del Meta Commerce (productos con descripciones)
- **Documentos** subidos directamente (ver formatos abajo)
- **Texto plano** (instrucciones, políticas, scripts de respuesta)
- **Sample messages** para entrenar tono y estilo

### Formatos de archivo aceptados

> ⚠️ **Nota de confianza:** estas specs aparecen consistentemente en las fuentes de terceros, pero no encontré documentación oficial 100% específica para Business AI knowledge uploads. Algunos límites pueden corresponder a WhatsApp Media (uso general) más que al Business AI específicamente. **Validar con Meta Business Suite al momento de configurar.**

| Tipo | Formatos | Límite tamaño |
|---|---|---|
| Documentos | PDF, DOC/DOCX, XLS/XLSX | hasta **100 MB** |
| Imágenes | JPEG, PNG | hasta 5 MB |
| Video | MP4 | hasta 16 MB |
| Audio | OGG, MP3 | hasta 16 MB |

**Formatos que probablemente NO funcionan bien o no se aceptan:**
- TXT plano (mejor convertir a PDF o Word)
- Markdown (mejor exportar a PDF)
- HTML directo (mejor usar URL del sitio)
- CSV (mejor convertir a XLSX)

---

## Estructura recomendada para nuestro documento (cuando arranquemos)

Cuando vos digas, el documento de BlackStones que entrene al Business AI debería tener:

1. **Identidad y rol del bot**
   - Quién es (asistente de BlackStones)
   - Qué hace (responder consultas de cotización, asesoramiento básico)
   - Qué NO hace (no cerrar venta, no dar precios exactos, no comprometer plazos)
   - Cuándo escalar a humano

2. **Mecanismo BlackStones**
   - Cotización cerrada por escrito en 24 hs
   - Medición técnica
   - Plazo 15-20 días corridos desde medición
   - Las 5 piezas del mecanismo (ver `01-strategy/mechanism.md`)

3. **Catálogo simplificado**
   - Granito · Cuarzo · Sinterizado · Mármol — diferencias
   - Cuándo recomendar cada uno
   - Honestidad técnica por material (ver `06-knowledge/layer-1-synthesis/2026-05-recomendaciones-material-por-uso.md`)

4. **Política operativa**
   - Zonas (CABA + GBA, sale desde Lanús)
   - NO coordinamos gremios
   - Medimos cuando los muebles ya están puestos
   - Sin garantía explícita de granito

5. **FAQ de Carolina** (top 10 preguntas reales)
   - ¿Cuánto sale? → no precio, sí proceso
   - ¿Cuándo me lo entregan? → plazo real
   - ¿Coordinan plomería? → no, sin disculparse
   - ¿Y si no me gusta el color? → fotos / showroom
   - ¿Qué pasa si se mancha? → honestidad por material
   - ¿Me cobran extras? → no, presupuesto cerrado
   - (otros desde `01-strategy/avatars/carolina.md` § 8)

6. **Plantillas de respuesta**
   - Saludo inicial
   - Pedido de medidas + zona
   - Respuesta a "qué me recomendás" (ver `02-marketing/whatsapp/recommendation-patterns.md`)
   - Derivación a humano

7. **Tono de voz**
   - Rioplatense, "vos"
   - Sin jerga marketing
   - Anti-genericness (ver `06-knowledge/layer-3-active-beliefs/anti-genericness.md`)

8. **Reglas de escalado**
   - Cliente pide precio exacto → derivar a humano para cotización
   - Cliente complejo / múltiples piezas → derivar
   - Cliente con queja / problema → derivar siempre
   - Cliente arquitecto/desarrollador (potencial volumen) → derivar prioridad alta

---

## Setup tentativo (cuando arranquemos)

1. Validar que el número de WhatsApp Business está verificado en Meta Business Manager
2. Asegurar que el catálogo de productos (si existe en Meta Commerce) está actualizado
3. Crear el documento (estructura propuesta arriba) — formato PDF
4. Subirlo a Meta Business Suite → WhatsApp → Business AI
5. Definir roles y guardrails (qué responde vs qué escala)
6. Testear con preguntas de Carolina simuladas
7. Iterar el doc hasta que el AI responda como lo haría Orlando
8. Activar en producción
9. Monitorear 7 días + ajustar
10. Después de 7 días: tracking de % de conversaciones manejadas autónomamente vs escaladas

---

## Pendientes de validación (cuando empecemos)

- [ ] Confirmar **dentro de Meta Business Suite** los formatos exactos aceptados (las fuentes de terceros pueden tener data desactualizada)
- [ ] Confirmar si el catálogo viene del Meta Commerce o se puede pasar como documento
- [ ] Probar si acepta documento con tablas (importante para el catálogo y matriz de recomendaciones)
- [ ] Verificar el comportamiento del "escalado a humano" → ¿el cliente recibe notificación?
- [ ] Confirmar si guarda historial de conversaciones (relevante para auditoría legal Ley 25.326)

---

## Cross-links

- `02-marketing/whatsapp/recommendation-patterns.md` — patrón de respuesta humano (input para entrenar el bot)
- `06-knowledge/layer-1-synthesis/2026-05-recomendaciones-material-por-uso.md` — matriz técnica (input)
- `06-knowledge/layer-3-active-beliefs/anti-genericness.md` — tono que el bot tiene que respetar
- `01-strategy/avatars/carolina.md` — § 8 objeciones (FAQ base)
- `01-strategy/mechanism.md` — las 5 piezas del mecanismo (corazón del bot)

---

## Trigger de revisión

- Meta cambia política AI de WhatsApp
- Cambian los formatos aceptados
- Sale documentación oficial más detallada
- Default: revisión cada 90 días

---

## Sources consultadas (mayo 2026)

- Meta Business · WhatsApp Help Center · Faq.whatsapp.com FAQ 1153795669452207, 291930066973116, 1337427890552510
- Meta Business News · "Meta AI business assistant expands globally with more languages"
- Meta for Developers · WhatsApp Business Platform docs
- Chatsell · "WhatsApp Business AI llega a Argentina y 16 países de LATAM en 2026"
- Imbrace · "WhatsApp's 2026 AI Policy"
- Chatarmin · "Meta AI for WhatsApp 2026"
- Robylon · WhatsApp Chatbot Guide 2026
