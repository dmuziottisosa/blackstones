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

Plazo: 15-20 días corridos desde la medición.
Forma de pago: 50% al confirmar, 50% al colocar.

Cualquier duda me decís. Si te sirve, coordinamos la medición técnica.
```

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
