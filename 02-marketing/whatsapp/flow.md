# WhatsApp Flow — venta de mesada

> Estado: **layer-1 synthesis** — base del flow original del compendio mayo 2026.
> **Pendiente:** validación contra 30 conversaciones reales del último mes para calibrar templates exactos.
>
> Fuente: `06-knowledge/layer-0-raw/2026-05-compendio-original.md` + transcripts WhatsApp (pendiente ingestar).

---

## 1. El flow en 5 pasos

```
1. Primer contacto         → respondemos en < 2 hs con saludo + pregunta clave
2. Calificación             → medidas, material aproximado, zona, urgencia
3. Cotización por escrito   → < 24 hs (idealmente < 4 hs), cerrada, todo adentro
4. Follow-up                → si no responde en 24 / 72 hs
5. Cierre + medición        → coordinamos visita técnica
```

---

## 2. Etapa 1 — Primer contacto

### Mensaje pre-llenado del ad
```
Hola, buenas. Vi el anuncio y me interesa cotizar una mesada de cocina.
```

### Nuestra primera respuesta (template base)
```
Hola [nombre], cómo estás. Soy [nombre del operador] de BlackStones.

Para armarte el presupuesto, necesito 2 cosas:
1) Largo x ancho aproximado de la mesada (ej: 3.20 x 0.60).
2) ¿Tenés idea de qué material te gustaría? (granito / cuarzo / sinterizada). Si no, tranqui, te asesoro.

Con eso te paso un primer rango de precio en el día.
```

**Reglas de la primera respuesta:**
- Mencionar nombre propio (humaniza).
- Pedir datos concretos en lista numerada (la cantidad de campos invisibilizan la fricción).
- Dar permiso explícito para "no saber" (asesoramos).
- Cerrar con promesa temporal ("en el día").

---

### Formato vigente de la primera respuesta (en uso desde oct 2026)

> Es el que se viene mandando de verdad. Manda sobre el template base de arriba. Recuperado de las conversaciones reales el 2026-11-15, para que no dependa de la memoria de nadie.

Cuando el contacto llega del anuncio sin ningún dato:

```
Hola, ¡gracias por escribirnos! Para pasarte el presupuesto necesito:

◾ Medidas de la mesada (largo × ancho)
◾ Si lleva hueco para bacha, anafe o ambos
◾ Zona de la obra

Te dejo nuestro catálogo con todos los colores, separados por material:
https://blackstones.com.ar/#colores

Y si te interesa la piedra sinterizada, acá tenés la colección de Suprastone:
https://www.suprastone.com.ar/colecciones/

Para orientarte rápido:
◾ Granito: el más económico. Aguanta la olla caliente y no se raya.
◾ Cuarzo: look parejo, ideal para tonos claros. No aguanta la olla caliente directa.
◾ Sinterizado: el más resistente. No se mancha, no se raya y aguanta todo.

Decime cuál te gusta y te paso el presupuesto. Si dudás entre varios, te cotizo dos o tres para que compares.
```

Reglas del formato:
- Viñetas con ◾, nunca con guiones ni números.
- Los dos catálogos van siempre en la primera respuesta.
- Se ofrece cotizar dos o tres opciones para comparar.

---

## 3. Etapa 2 — Calificación

Información mínima para cotizar:

| Campo | Por qué importa | Cómo preguntamos si no lo da |
|---|---|---|
| Largo x ancho | Base del cálculo | Pregunta directa |
| Material aproximado | Define rango de precio | "¿Granito / cuarzo / sinterizada? Si no sabés, te ayudo a elegir" |
| Zona | Define costo de flete | "¿En qué barrio estás?" |
| Urgencia | Prioriza producción | "¿Para cuándo la necesitarías?" |
| Estado de la obra | Valida que se puede medir | "¿Los muebles ya están puestos?" |

Si el cliente no da uno de estos, **lo pedimos sin rodeos**. La velocidad es la promesa — preguntar 3 veces lo mismo la rompe.

---

## 4. Etapa 3 — Cotización por escrito

Estructura del mensaje de cotización:

```
[nombre], te paso el presupuesto:

📐 Mesada de cocina · [material] · [largo] x [ancho]m
💰 USD [monto] / ARS [monto al cambio del día]

Incluye:
✓ Asesoramiento + medición técnica en obra
✓ Fabricación con corte CNC
✓ Pulido y sellado
✓ Flete a [zona]
✓ Colocación

No incluye:
✗ Bacha
✗ Grifería
✗ Demolición de mesada anterior

Plazo: 20 días corridos desde la medición.
Forma de pago: 50% al confirmar, 50% al colocar.

Cualquier duda me decís. Si te sirve, coordinamos la medición técnica.
```

### Formato vigente de la cotización (en uso desde oct 2026)

> Manda sobre la estructura de arriba. Recuperado de las cotizaciones reales el 2026-11-15.

```
Hola [nombre], te paso el presupuesto para la mesada de [largo] × [ancho]:

◾ *[Color] ([material])*: [USD o $ monto]
[Una línea con lo que hace bien ese material.]

◾ *[Segunda opción] ([material])*: [monto]
[Una línea.]

Incluye fabricación, traforo para [bacha / bacha y anafe] y zócalo de 5 cm.

◾ *Flete y colocación en [zona]*: $ [monto]

Es un presupuesto estimado: nuestro técnico rectifica las medidas en obra antes de fabricar, y ahí cerramos el número final.

Plazo: 20 días corridos desde la medición.
```

Convenciones que se vienen usando:
- **Zócalo de 5 cm incluido** sin cargo.
- **El sinterizado va con regrueso de 4 cm**, y se dice ("lleva regrueso de 4 cm"). El m² se calcula con el regrueso.
- **Traforo:** USD 80 o $ 100.000 cada uno, según la moneda del material.
- **Flete y colocación:** CABA $ 200.000, GBA $ 300.000.
- **Suprastone:** se manda el link de la colección para que elija el color.
- Debajo del mensaje va siempre una lista "Para revisar antes de mandarlo", con lo que se asumió.

**Reglas de la cotización:**
- **Cerrada.** No "estimativo, después confirmamos."
- **Por escrito.** Reenvíable. Compartible con la pareja / arquitecta.
- **Excluyentes explícitos.** No esconder.
- **Plazo en días corridos.** No "3-4 semanas".
- **CTA suave para medición.** No empujar — Carolina necesita tiempo de procesamiento mental.

---

## 5. Etapa 4 — Follow-up

### A las 24 hs (si no respondió a la cotización)
```
[nombre], cualquier duda con el presupuesto que te pasé, decime.
Si querés, podemos coordinar la medición esta semana.
```

### A las 72 hs
```
[nombre], cómo va? ¿Tuviste chance de mirar el presupuesto?
Si necesitás algún ajuste, otro material o algo que no entendiste, te lo aclaro.
```

### A los 7 días (último intento)
```
Hola [nombre]. Te dejo el presupuesto a mano por si lo retomás más adelante.
Cualquier cosa estoy.
```

### Reglas de follow-up
- **3 intentos máximo.** Después se considera enfriado.
- **Cada intento agrega valor**, no solo recuerda. (Ofrecer alternativa, ajuste, asesoramiento extra.)
- **Tono casual,** no comercial agresivo. Test de Living siempre.
- **Si responde "no, gracias"**, agradecemos y cerramos. Sin insistir.

---

## 6. Etapa 5 — Cierre + medición

Cuando el cliente confirma:

```
Perfecto [nombre]. Para coordinar la medición técnica:

¿Qué día de la semana te queda mejor? Salimos de Lanús — la idea es agruparte con otras visitas cercanas para optimizar tiempos.

Te paso 3 opciones de día y vos elegís.

Para la seña: [datos de transferencia / link / lo que apliquemos].
Apenas la veamos confirmada, te confirmo la medición.
```

**Reglas:**
- Coordinar día, no horario exacto (ruta optimizada después).
- Pedir seña explícita pero sin presión.
- Confirmar medición solo cuando seña esté efectiva.

---

## 7. Templates por escenario especial (pendientes de redacción detallada)

| Escenario | Status |
|---|---|
| **Cliente pide el catálogo** | **listo** (ver abajo) |
| Cliente pregunta solo precio sin medidas | pendiente |
| Cliente quiere venir al showroom | pendiente |
| Cliente pide muestra física en su casa | pendiente |
| Cliente compara presupuestos (estamos más caros) | pendiente |
| Cliente quiere financiar | pendiente |
| Arquitecta presenta cliente | pendiente |
| Cliente del rebrand antiguo (VenarStones) | pendiente |
| Reseña post-instalación (pedir reseña Google) | pendiente |
| Cliente insatisfecho post-instalación | pendiente |

→ Cada uno termina como archivo en `templates/`.

### Cliente pide el catálogo (WhatsApp o DM de Instagram)

Catálogos oficiales para compartir (dato del dueño, 2026-11-15):

- **Propio:** `https://blackstones.com.ar/#colores` — granito, cuarzo y sinterizado, con pestañas por material. No tiene pestaña de mármol.
- **Suprastone (sinterizado):** `https://www.suprastone.com.ar/colecciones/` — 8 colecciones: blancos, negros, grises, exóticos, fullbody, travertinos, 3D y beige. Placas de 1600 x 3200 mm.

```
Hola, ¿cómo estás? Te paso el catálogo:

*Todos nuestros colores* (granito, cuarzo y sinterizado):
https://blackstones.com.ar/#colores

*Sinterizado Suprastone*, colección completa:
https://www.suprastone.com.ar/colecciones/

Si alguno te gusta, mandame el nombre y las medidas de tu mesada y te paso el presupuesto cerrado por escrito.
```

**Regla:** el catálogo nunca se manda solo. Siempre cierra pidiendo el color y las medidas, que es lo que hace falta para cotizar.

---

## 8. Banco de objeciones (pendiente)

Las objeciones más comunes están listadas en `01-strategy/avatars/carolina.md` § 8. El detalle de cómo respondemos cada una vive (cuando lo escribamos) en `objection-bank.md`.

---

## 9. Métricas que importan

| Métrica | Definición | Threshold de alerta |
|---|---|---|
| Tasa de respuesta < 2 hs | leads respondidos en 2 hs / total | < 90% |
| Tasa de cotización | leads cotizados / leads contactados | < 80% |
| Tiempo medio cotización | media de horas desde primer contacto | > 6 hs |
| Tasa de cierre | mesadas vendidas / leads cotizados | hipótesis: 15-25%, **medir** |
| Tasa de cierre desde ads | mesadas / leads de ads | hipótesis: 10-15%, **medir** |
| CAC real | gasto Meta / mesadas cerradas atribuibles | hipótesis: USD 60-100, **medir** |

> Casi todas son `hypothesis` hoy. Loggeo sistemático es prioridad.

---

## 10. Trigger de revisión

- Cambia el avatar principal.
- Encontramos un escenario recurrente que no está cubierto en templates.
- Validación con 30 transcripts reales (ajustar lenguaje real del cliente).
- Default: revisión cada 60 días.
