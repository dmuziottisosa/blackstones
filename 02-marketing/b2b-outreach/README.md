# 02-marketing/b2b-outreach/ — B2B Reach (blueprint plug-and-play)

> **Qué es:** sistema semi-automatizado de prospección B2B fría por WhatsApp.
> Scrapea contactos de Google Maps, los normaliza en una base, y los contacta
> a ritmo humano desde una línea dedicada con uno de los cold DM de marca.
>
> **Estado a jun-2026:** `BLUEPRINT LISTO — BLOQUEADO esperando la línea dedicada.`
> No hay nada construido todavía. Este doc es el plan completo para arrancar la
> fase de construcción el día que el dueño consiga el número.

---

## 0. Cómo retomar esto (trigger para la IA)

Cuando el dueño consiga la línea, va a pegar un prompt corto (ver sección 11).
Ese prompt apunta a este archivo. **IA: al recibirlo, leé este README entero,
no rediscutas las decisiones de la sección 2, y arrancá directo por la sección
9 (construcción).**

---

## 1. Para qué sirve (y para qué NO)

- **Sí:** abrir relación con decoradores, estudios de arquitectura, constructoras
  y desarrolladoras de CABA + GBA. Que conozcan que existimos y que somos
  "alguien de confianza para la mesada". Sembrar, no cerrar en el primer mensaje.
- **No:** blast masivo de venta. Eso quema el número, quema la lista y es lo
  opuesto al ADN de marca (`CLAUDE.md` regla 5 — anti-pushy, "servir más que
  conseguir el money"). Esto es prospección artesanal escalada, no spam.

---

## 2. Decisiones ya tomadas (NO rediscutir)

| Decisión | Valor | Por qué |
|---|---|---|
| **Línea** | Dedicada, nueva. **Nunca** el número principal. | Si se banea, no caen los ~25 leads/día del funnel real. |
| **Volumen** | Rampa: 20-30/día (sem 1) → 40-60 → 70-100 → 100-150 crucero. | Una línea nueva que arranca alto se banea en 48 hs. |
| **150+ sostenido** | Rotar 2 líneas (~75 c/u), no exprimir una. | Tope humano seguro por número ≈ 80-120/día. |
| **Targets** | Decoradores/interioristas · Constructoras/desarrolladoras · Arquitectos/estudios. | Mayor valor de derivación. **Carpinteros quedaron fuera.** |
| **Tono** | Cold DM de marca, anti-pushy ("estamos a la orden"). | `CLAUDE.md` reglas duras. |

---

## 3. Arquitectura

```
APIFY (Google Maps)            NORMALIZADOR (Python)          ENVÍO (cola rate-limited)
"decoradores CABA"        →    dedup + formato +549      →    50→150/día, delays random,
"estudios arq Palermo"         etiqueta categoría/zona         plantillas con variables,
"constructoras GBA"            + merge con Excel manual        horario hábil, opt-out
       ↓                              ↓                               ↓
  export .json/.csv          base-maestra.csv                logs de envío + respuestas
```

---

## 4. PASO 0 — Conseguir la línea (lo único bloqueante hoy)

### 4.1 — Setup del perfil de WhatsApp Business (decidido jun-2026)

| Campo | Valor exacto |
|---|---|
| **Nombre comercial** | `BlackStones Marmolería` |
| **Categoría** | Mejoras del Hogar (o "Construcción y obras" si está disponible) |
| **Descripción** *(223/256 chars)* | `Proveedor y marmolería para arquitectos, decoradores y obras. Granito, cuarzo y sinterizado. +1.000 obras en CABA y GBA. Presupuesto por escrito en 24 hs, plazos de 15 a 20 días desde la medición. Showroom Av. Alberdi 3575.` |
| **Dirección** | Av. Juan Bautista Alberdi 3575, CABA — completar mapa |
| **Horario** | Lun–Vie 9–17 · Sáb 9–13 (coincide con la landing) |
| **Web primaria** | `blackstones.com.ar` |
| **Web secundaria** | `instagram.com/blackstones.ar` |
| **Email** | `contacto@blackstones.com.ar` |
| **Foto de perfil** | Isotipo de la montaña sobre fondo crema, cuadrado 640×640+ (NO el wordmark — se recorta mal en redondo) |
| **Catálogo de WA** | 3-4 productos: granito, cuarzo, sinterizado (fotos reales de obras) |
| **Mensaje de bienvenida** | DESACTIVADO (un auto-mensaje en línea fría B2B grita "bot") |
| **Respuestas rápidas** | `/web` → `https://blackstones.com.ar` (para el segundo mensaje, no el primero) |

> **Por qué "Proveedor y marmolería":** decisión deliberada — abre con los dos
> modelos de negocio (venta de lámina **y** mesada terminada) en 3 palabras. El
> arquitecto/desarrollador entiende al instante que puede comprarte materia
> prima O cocina llave en mano. Diferenciador real frente a competidores que
> solo hacen una de las dos cosas.
>
> **Por qué "arquitectos, decoradores y obras":** lista de 3 (no 4) — ritmo de
> lectura más limpio. "Obras" es contenedor de constructoras + desarrolladoras
> + obra privada: cubre los tres targets de la sección 2 sin enumerarlos como
> directorio. Más voz de marca, menos ficha técnica.

### 4.2 — Pasos prácticos del dueño

1. **Conseguí un chip/SIM nuevo** (prepago sirve) o un número virtual estable.
   Que sea un número que puedas tener prendido y no te importe "gastar".
2. **Activá WhatsApp Business** (no el normal) en ese número, en un teléfono o
   emulador que quede disponible.
3. **Completá el perfil**: nombre "BlackStones Marmolería", logo, descripción,
   dirección del showroom, horario, link a la web. Un perfil completo levanta
   la reputación y baja el riesgo de ban.
4. **Warm-up manual los primeros días**: mandale mensajes a 5-10 contactos
   conocidos, que te respondan, guardá algunos en agenda. WhatsApp tiene que
   "ver" que es un número humano antes de que empiece la automatización.
5. Cuando lo tengas → pegá el trigger de la sección 11.

> No compres números "ya warmeados" de reventa: suelen venir flaggeados y es
> tirar la plata.

---

## 5. FASE 1 — Scraping (Apify)

- **Cuenta:** apify.com, free tier ($5 crédito/mes) alcanza para arrancar.
  Si hace falta más, plan Starter (~$39-49/mes — *verificar precio actual*).
- **Actor:** Google Maps Scraper / Google Maps Extractor.
- **Output por lead:** nombre, teléfono, dirección, categoría, web, rating.
- **Queries** (la IA arma el set fino en construcción; ejemplos):
  - `estudio de arquitectura [barrio]` × barrios ABC1 CABA + Zona Norte
  - `decoración de interiores [zona]`
  - `constructora` / `desarrolladora inmobiliaria` [CABA, GBA Norte]
- **Instagram NO** como fuente primaria: no expone teléfono confiable y scrapearlo
  es más propenso a baneo. Google Maps es superior para esto.

---

## 6. FASE 2 — Normalización (Python, gratis)

Script `normalizar.py` (la IA lo construye). Hace:

1. Toma el export de Apify (json/csv) **+ el Excel manual existente del dueño**.
2. Dedup por teléfono normalizado.
3. Formatea todos los números a `+549...` (validación AR).
4. Etiqueta cada fila: `categoria` (decorador/constructora/arquitecto), `zona`, `fuente`.
5. Descarta sin teléfono o con teléfono inválido.
6. Escupe `base-maestra.csv` listo para la cola de envío.

> **Pendiente del dueño:** pasar una fila de ejemplo del Excel manual (qué
> columnas tiene) para que el merge salga calzado. Va en el trigger (sección 11).

---

## 7. FASE 3 — Envío (warm-up + anti-ban)

### Tool — dos caminos:

| | Opción A — gestionada (recomendada) | Opción B — DIY |
|---|---|---|
| Qué | Wassenger / Whapi.io (~$30/mes) | whatsapp-web.js / Baileys + VPS (~$5/mes) |
| Pro | rate-limit, delays random y warm-up de fábrica; no hay que hostear nada | casi gratis |
| Contra | costo mensual | código a mantener + servidor prendido 24/7 |

*(Precios aprox., conocimiento corta ene-2026 — verificar antes de pagar.)*

### Rampa de envío (tope diario escalonado):

| Semana | Tope/día |
|---|---|
| 1 (warm-up) | 20-30 |
| 2 | 40-60 |
| 3 | 70-100 |
| 4+ (crucero) | 100-150 (o 2 líneas × 75) |

### Reglas anti-ban (se codifican en la cola):

- **Delays aleatorios** entre mensajes (no intervalos fijos — eso grita "bot").
- **Spintax**: 4-5 variantes de cada frase que rotan; nunca dos mensajes idénticos.
- **Solo horario hábil** AR (ej. 10-18 hs), lunes a viernes.
- **Sin link en el primer mensaje** (los links suben el score de spam). El link
  a la web se da recién cuando responden.
- **Opt-out duro**: si alguien dice "no me escribas" / bloquea → fuera de la lista
  para siempre. Honra la Ley 25.326 de Protección de Datos Personales (AR).

---

## 8. Costos (entra en el budget de $80/mes)

| Item | Gestionada | DIY |
|---|---|---|
| Apify | $0-49 | $0-49 |
| Envío | ~$30 | ~$5 (VPS) |
| Línea (chip) | costo único bajo | íd. |
| **Total/mes** | **~$30-69** | **~$5-54** |

---

## 9. CONSTRUCCIÓN — qué arma la IA (todo tool-agnóstico, sirve sí o sí)

Cuando llegue el trigger, construir en este orden:

1. **`apify-queries.md`** — set completo de queries por categoría × zona.
2. **`normalizar.py`** — script de normalización + merge con el Excel del dueño.
3. **`banco-mensajes.md`** — 3 secuencias (decorador / arquitecto / constructora),
   cada una con spintax. Base: los cold DM ya refinados en chat (v11, el de
   "si en algún momento necesitás a alguien de confianza para instalar mesadas,
   estamos a la orden"). **Reconstruirlos acá — hoy viven solo en el historial.**
4. **`cola-envio/`** — config del tool elegido (A o B) con rampa + reglas anti-ban.
5. **`opt-out.md`** — lista viva de bajas (se respeta siempre).

---

## 10. Mensajería — ángulos por segmento (a desarrollar)

- **Decorador/interiorista:** proyectos residenciales premium → "trabajamos con
  decoradores que necesitan la mesada prolija y a tiempo".
- **Arquitecto/estudio:** obras recurrentes → "proveedor de confianza, plazos
  sin sobresaltos, muestras en tu estudio".
- **Constructora/desarrolladora:** volumen/serie → "manejamos proyectos en serie".

Todos cierran en la vibe de marca: servir, no vender. "Estamos a la orden."

---

## 11. TRIGGER PROMPT (el dueño pega esto cuando tenga la línea)

```
ACTIVAR B2B REACH — FASE CONSTRUCCIÓN

Ya tengo la línea dedicada. Número: +549__________
Leé 02-marketing/b2b-outreach/README.md y arrancá la construcción
(sección 9), sin rediscutir las decisiones de la sección 2.

Tool de envío que elijo: [ Wassenger / Whapi / DIY-VPS / decidí vos ]
Mi Excel manual tiene estas columnas (pego una fila de ejemplo):
[ PEGAR FILA ]

Construí: apify-queries, normalizar.py, banco-mensajes (los cold DM
v11 que ya teníamos), y la config de la cola con la rampa de warm-up.
```

---

*Creado: jun-2026. Estado: blueprint listo, esperando línea. Próximo evento:
el dueño consigue el número y pega el trigger de la sección 11.*
