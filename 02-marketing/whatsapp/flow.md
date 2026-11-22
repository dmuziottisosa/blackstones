# WhatsApp Flow — venta de mesada

> Estado: **layer-1 synthesis** — base del flow original del compendio mayo 2026.
> **Pendiente:** validación contra 30 conversaciones reales del último mes para calibrar templates exactos.
>
> Fuente: `06-knowledge/layer-0-raw/2026-05-compendio-original.md` + transcripts WhatsApp (pendiente ingestar).

---

## 1. El flow en 5 pasos

```
1. Primer contacto         → respondemos en < 2 hs con saludo + pregunta clave
                              (desde el 22/11/2026: el mensaje de seis puntos; los dos últimos, los muebles y si es nueva o reemplazo)
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

### Formato vigente de la primera respuesta: "la vieja confiable" (dueño, 22/11/2026)

> Es la respuesta que el dueño mandó anotar acá el 22/11/2026: "porque va a ser nuestra vieja confiable. Así no tenés que tenerla en tu memoria sino que queda anotada". **Se copia de este doc, letra por letra; no se reescribe de memoria.**
>
> Llegó a esta redacción después de varias vueltas el mismo día: no se pregunta si es recta o en L ("eso lo vemos después"), el material y el color van juntos, el mueble tiene que estar "colocado y despejado", y nada que suene a máquina o a formulario ("siendo humano me chocó"). Manda sobre el template base de arriba y sobre el formato de octubre, que quedó al final de esta sección.
>
> Lo último que sumó ese día: la pregunta de los muebles con más amabilidad ("creo que nos falta amabilidad: para tenerlo en cuenta…") y la de si es una instalación nueva o un reemplazo ("esto es importante y lo olvidé"). Por eso son seis puntos. Y el link de los colores: "el primer mensaje debe llevar esto".

Cuando el contacto llega del anuncio sin ningún dato:

```
¡Gracias por escribirnos! Para prepararte el presupuesto lo antes posible, contanos:

1. Medidas: largo x ancho.
2. Material y color que te interesan. Los podés ver acá: https://blackstones.com.ar/#colores
3. ¿Lleva bacha, anafe o zócalo?
4. ¿En qué zona o barrio sería?
5. Para tenerlo en cuenta, ¿los muebles ya están colocados y despejados?
6. ¿Es una instalación nueva o se trata de reemplazar una mesada existente?

Si tenés una foto del lugar o un plano con las medidas, también nos sirve como referencia.
```

Reglas del formato:
- Va tal cual, con sus números. Es también el primer mensaje que tiene que mandar el bot.
- **De lo que ya sabemos, no se pregunta.** Si el cliente ya pasó algo, esos puntos se sacan, se piden solo los que faltan, renumerados, y el mensaje arranca diciendo lo que ya se anotó. Ejemplo, para uno que escribió "1,50 x 0,60 con zócalo":

```
¡Gracias por escribirnos! Ya anotamos la medida: 1,50 × 0,60, con zócalo. Para prepararte el presupuesto lo antes posible, contanos:

1. Material y color que te interesan. Los podés ver acá: https://blackstones.com.ar/#colores
2. ¿Lleva bacha o anafe?
3. ¿En qué zona o barrio sería?
4. Para tenerlo en cuenta, ¿los muebles ya están colocados y despejados?
5. ¿Es una instalación nueva o se trata de reemplazar una mesada existente?

Si tenés una foto del lugar o un plano con las medidas, también nos sirve como referencia.
```

- **Los puntos 5 y 6 son los que dicen dónde poner el esfuerzo.** Si el cliente contesta lo demás y esos no, se le repiten una vez, al final del mensaje siguiente. De nuestros chats: cuando se pide una lista, vuelve más o menos la mitad de los puntos.
- **Ya no se pregunta para cuándo.** Si el cliente contesta que no y cuenta solo la fecha, se toma; si no la cuenta, queda como "todavía no".
- Si contesta que no sabe qué material, se le mandan los dos catálogos y la guía de tres líneas (formato de octubre, al final de esta sección).

Todos los que llegan del anuncio abren con el mismo mensaje: "Hola, buenas. Vi el anuncio y me interesa cotizar una mesada de cocina." (dueño, 22/11/2026). Lo que se le carga a la IA de WhatsApp, con este mensaje y sus variantes como ejemplos de respuestas, está en `knowledge/ejemplos-de-respuestas.md`.

Para pegar en las instrucciones del bot:

```
Cuando el cliente escribe por primera vez, respondé con este mensaje, tal cual:

¡Gracias por escribirnos! Para prepararte el presupuesto lo antes posible, contanos:

1. Medidas: largo x ancho.
2. Material y color que te interesan. Los podés ver acá: https://blackstones.com.ar/#colores
3. ¿Lleva bacha, anafe o zócalo?
4. ¿En qué zona o barrio sería?
5. Para tenerlo en cuenta, ¿los muebles ya están colocados y despejados?
6. ¿Es una instalación nueva o se trata de reemplazar una mesada existente?

Si tenés una foto del lugar o un plano con las medidas, también nos sirve como referencia.

Si el cliente ya dijo alguno de esos datos, no se lo vuelvas a pedir: pedí solo los que faltan. No agregues otras preguntas ni des precios.
```

### Cómo se sigue: a quién se le trabaja la cotización (dueño, 22/11/2026)

> El dueño, el mismo día:
> - "Esto nos hace entender dónde poner los esfuerzos."
> - "Lo que no quiero es sobre-trabajar para el que no tiene el mueble listo ni siquiera."
> - Del cliente que escribe la medida y el color en una línea: "Estos son los que hay que resolver rápido", "los más comunes, y los que más hay que resolver la duda", "estos son los que abundan".
>
> El porqué y lo que falta saber: `01-strategy/decision-log/2026-11-22-la-primera-pregunta-clasifica.md`. Los detalles que el dueño no dijo (dos semanas, un solo color, repetir la pregunta una sola vez) son una propuesta: los confirma él.

**Qué se manda según lo que trajo el cliente**

| Qué trajo | Qué se le manda |
|---|---|
| La medida y el color, o el tono ("negro", "bien claro"). Es el caso que abunda | La cotización corta, en el momento. No se le pide nada antes. |
| La medida sin color, o el color sin medida | El primer mensaje, solo con los puntos que faltan. |
| La medida o el plano, sin color, y pide materiales y precios | El rango de cada material y los catálogos. |
| Nada todavía | El primer mensaje entero. |

**El que abre con medidas, material y color** ("3,00 x 0,60, granito negro"). El dueño, 22/11/2026: "responder a esta apertura es más tricky, porque te está pasando medidas y color; no exacto, pero sirve". Y fijó qué se hace:

- **Se le cotiza conservador, con bacha y anafe**, pero el mensaje no lo dice: "a estos así no les digas que incluye".
- **En los dos más pedidos** del color que nombró. En granito negro son **Negro Boreal y Negro Brasil**. De los demás colores el dueño todavía no dijo cuáles son: hasta entonces, uno concreto y, si hay, uno más económico.
- **Se dice el plazo:** 20 días corridos desde que vamos a medir ("faltó decirle el plazo").
- **Se pregunta el barrio o la zona**, y nada más.
- **"El mensaje debe ser breve, más largo no es mejor."**

```
¡Gracias por escribirnos! Te pasamos el presupuesto de tu mesada de [largo] × [ancho] en [material y color], en los dos más pedidos:

◾ *[Color más pedido]*: [USD o $ monto]
◾ *[El otro más pedido]*: [USD o $ monto]

El plazo son 20 días corridos desde que vamos a medir.

¿En qué zona o barrio sería? Con eso te pasamos el flete y la colocación.
```

Lo que este mensaje no dice (qué incluye, que es un presupuesto estimado, que el granito no lleva garantía y la pregunta del mueble) va en el mensaje siguiente, cuando conteste la zona, con el formato de la sección 4. Esto último lo puso quien escribió el doc: lo confirma el dueño.

**Cocina nueva, o mueble con la mesada vieja encima.** El dueño, 22/11/2026, sobre una mesada de más de cuatro metros: "acá podemos asumir que está haciendo una cocina nueva, con mueble nuevo; pero está el caso del que ya tiene un mueble con un toper" (la mesada vieja encima).

**Un dibujo no es un mueble.** El dueño, 22/11/2026, sobre un cliente que mandó las medidas dibujadas en un cuaderno: "por más que tengan dibujitos en cuadernos u hojas, no hay que confiarse: probablemente no tengan nada". Y sobre otro que mandó un plano y un render: "preguntar por mueble despejado". Con un dibujo, un plano o un render no se asume nada: el mensaje termina con las dos preguntas, "Para tenerlo en cuenta, ¿los muebles ya están colocados y despejados?" y "¿Es una instalación nueva o se trata de reemplazar una mesada existente?".

**El que manda las medidas o el plano y pide "materiales, modelos y precios"**, sin decir color. El dueño, 22/11/2026: "mejor pasale el rango de cada material y listo".

```
¡Hola, [nombre]! Gracias por escribirnos. Por tu plano, la mesada ronda estos valores según el material:

◾ *Granito*: de $ [monto] a $ [monto] (los importados, desde USD [monto])
◾ *Cuarzo*: de USD [monto] a USD [monto]
◾ *Sinterizado*: de USD [monto] a USD [monto]

Modelos y texturas: https://blackstones.com.ar/#colores
Sinterizados Suprastone: https://www.suprastone.com.ar/colecciones/

El plazo son 20 días corridos desde que vamos a medir.

Decinos qué color te gusta y en qué zona sería, y te pasamos el presupuesto cerrado.
```

De dónde sale cada rango (lo puso quien escribió el doc; lo confirma el dueño): se calcula con los m² del cliente y los precios de la calculadora, con los huecos que muestre el plano. Granito, del nacional más pedido más barato (Gris Mara) al nacional más caro, y los importados desde Negro Brasil. Cuarzo, del Guidoni más barato al Purastone más caro; Silestone llega más arriba. Sinterizado, lo que cuesta Suprastone, con regrueso; Neolith y Dekton van más arriba. Se redondea.

**Qué se hace con lo que contesta del mueble**

| Qué contesta | Qué hacemos |
|---|---|
| Ya está colocado y despejado, o cuenta que lo recibe en estos días (dos semanas) | Todo el trabajo: opciones para comparar, ajustes, seguimiento (sección 5) y coordinar la medición. Va primero. |
| Todavía no: falta, o no sabe cuándo | Si ya recibió su número, queda ese y se cierra con el mensaje de abajo. Si no, la respuesta corta. Sin opciones, sin recálculos y sin seguimiento. |
| No contesta, después de repetirle la pregunta | Igual que el que no lo tiene. |

Dos casos aparte:
- **Un arquitecto o una obra con plano** no se clasifican así: preguntan con meses de anticipación y son pedidos grandes. Ahí decide el dueño.
- **Una cotización larga de armar** (varias piezas, plano, varias opciones) de alguien que no dijo si tiene el mueble: primero la pregunta. La cotización se arma con la respuesta.

**Al que no tiene el mueble**

Si ya recibió su número:

```
Gracias por contarme. El número que te pasé te sirve de referencia. Nosotros medimos con el mueble ya colocado: cuando lo tengas, escribime por acá y en 24 hs te paso el presupuesto cerrado y coordinamos la medición.
```

Si pasó medidas y color y todavía no recibió nada (un solo color, un solo número):

```
Gracias por contarme. Para que tengas una referencia, tu mesada de [largo] × [ancho] en *[color] ([material])* ronda los [USD o $ monto], con fabricación y zócalo de 5 cm. El flete y la colocación van aparte.

Nosotros medimos con el mueble ya colocado. Cuando lo tengas, escribime por acá: en 24 hs te paso el presupuesto cerrado y coordinamos la medición.
```

Si no pasó medidas o color (no se le piden: se le deja el catálogo):

```
Gracias por contarme. Nosotros medimos con el mueble ya colocado, así que tenés tiempo para elegir el color.

Te dejo el catálogo con todos los colores: https://blackstones.com.ar/#colores
Y la colección de sinterizados de Suprastone: https://www.suprastone.com.ar/colecciones/

Cuando tengas el mueble colocado, pasame las medidas y el color que te gustó, y en 24 hs te paso el presupuesto cerrado por escrito.
```

### Formato de octubre de 2026 (ya no es el primer mensaje)

> Fue la primera respuesta desde octubre hasta el 22/11/2026. Hoy sirve para el que contesta que no sabe qué material: lleva los dos catálogos y la guía de tres líneas.

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

En los mensajes que no son el primero (cotizaciones, catálogos), las viñetas siguen siendo ◾, nunca guiones.

---

## 3. Etapa 2 — Calificación

Información mínima para cotizar:

| Campo | Por qué importa | Cómo preguntamos si no lo da |
|---|---|---|
| Estado de la obra y urgencia | Desde el 22/11/2026 se pregunta en el primer mensaje: dice si se puede medir y cuánto se trabaja el chat | "Para tenerlo en cuenta, ¿los muebles ya están colocados y despejados?" y "¿Es una instalación nueva o se trata de reemplazar una mesada existente?" (sección 2) |
| Largo x ancho | Base del cálculo | Pregunta directa |
| Material aproximado | Define rango de precio | "¿Granito / cuarzo / sinterizada? Si no sabés, te ayudo a elegir" |
| Zona | Define costo de flete | "¿En qué barrio estás?" |

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

> Cómo se hace la cuenta y las reglas del dueño para cotizar: `como-cotizamos.md`.

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
- **El seguimiento es para quien ya tiene el mueble colocado** (22/11/2026, a confirmar por el dueño). Al que todavía no lo tiene se le manda la respuesta corta de la sección 2 y no se lo persigue: queda en él avisar.
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
