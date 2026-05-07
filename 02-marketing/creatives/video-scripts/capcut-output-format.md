# CapCut Output — Formato canónico para generación de video

> **Status:** `formato-canónico` — usar este documento como template para todo guion de video que vaya a producirse en CapCut.
> **Última actualización:** 2026-05-06
> **Pareja:** ElevenLabs (audio) + CapCut (edición). El audio sale de `guion-ganador-*.md` con cues `[excited]` `[happy]`. El video se arma desde este formato.

---

## Por qué existe este formato

CapCut es la herramienta de edición. Necesita instrucciones distintas a las de ElevenLabs (que solo recibe el texto con cues de entonación).

Para CapCut, el editor (humano o IA) necesita saber **3 cosas por escena**: cuánto dura, qué texto va sobre la pantalla, y qué imagen/video se muestra. El formato de abajo entrega exactamente eso.

**Sin este formato bien armado, el editor:**
- No sabe qué clips elegir o filmar
- No sabe dónde cortar
- Tiene que adivinar la jerarquía visual
- El video sale inconsistente o se pierde el mensaje

**Con este formato, el editor:**
- Recibe un brief autónomo (no necesita preguntar)
- Sabe los timings exactos
- Sabe qué texto va sobre cada escena
- Tiene la descripción visual para buscar clip o filmarlo

---

## Estructura — 3 bloques

### 1. CONCEPTO
Un párrafo. Qué es el video, estilo visual, voz, duración. Es el brief creativo en una unidad.

### 2. NARRACIÓN
El texto exacto que se va a escuchar. Todo corrido, sin cortes, sin acotaciones de escena, sin indicaciones visuales. Puro audio. Esto se le da al generador de voz (ElevenLabs) o al locutor humano.

### 3. ESCENAS VISUALES
Cada escena con:
- Rango de tiempo en segundos
- Texto en pantalla (lo que se lee como overlay/subtítulo)
- Descripción visual completa (lo que se ve)

---

## Template para copiar

```
CONCEPTO
[Qué es el video · estilo visual · voz · duración]

NARRACIÓN
[Texto corrido del audio — sin cortes, sin acotaciones de escena]

ESCENAS VISUALES
ESCENA 1 — [segundos inicio] a [segundos fin] segundos
Texto en pantalla: "[lo que aparece como overlay]"
[Descripción visual completa: qué se ve, planos, movimiento de cámara,
luz, transiciones]

ESCENA 2 — [segundos inicio] a [segundos fin] segundos
Texto en pantalla: "[overlay]"
[descripción visual]

...
```

---

## Output canónico — BlackStones general (mayo 2026)

> **Avatar:** Carolina (cocina con muebles puestos, sin mesada todavía)
> **Ángulo:** mecanismo + problem-aware
> **Status:** `ganador`
> **Locución correspondiente (ElevenLabs):** `guion-ganador-2026-05.md`

```
CONCEPTO
Video general de presentación de marca para Meta Ads. BlackStones se
presenta como marmolería confiable para el cliente que ya tiene los
muebles de cocina puestos y necesita la mesada. El video muestra proceso
real de trabajo, social proof concreto, y cierra con CTA de baja
fricción dirigido a WhatsApp. Diseñado para silent viewing con texto
en pantalla en cada escena.
Estilo visual: Fotográfico real, luz natural, tomas de taller y cocinas
reales. Nada generado por AI.
Voz: Masculina, conversacional, argentina, tono seguro sin ser soberbio.
Ritmo de charla, no de locutor.
Duración estimada: treinta y tres segundos.

NARRACIÓN
Si ya tenés los muebles puestos y todavía no tenés mesada... esto es
para vos. Somos BlackStones. Más de quinientas mesadas colocadas en
cocinas y baños de CABA y GBA. Funciona así. Nos mandás el largo y el
ancho por WhatsApp, y en menos de veinticuatro horas tenés presupuesto
cerrado, por escrito. Con medición, fabricación y colocación. Todo
adentro. Granito. Cuarzo. Sinterizada. Te decimos cuál te conviene y
cuánto sale. Después de la medición técnica, te damos fecha de entrega.
En días corridos. No en "tres a cuatro semanas". En días. Mandanos las
medidas y hoy tenés cotización.

ESCENAS VISUALES
ESCENA 1 — cero a tres segundos
Texto en pantalla: "Si ya tenés los muebles y todavía no tenés mesada..."
Plano abierto de una cocina con muebles bajo mesada instalados pero sin
superficie arriba. Los muebles son nuevos, prolijos, pero la parte de
arriba está vacía. Se ven las cañerías tapadas donde irá la bacha. Luz
natural de ventana entrando por la izquierda. La imagen comunica
instantáneamente: está casi lista pero no se puede usar. Push-in lento
hacia los muebles vacíos.

ESCENA 2 — tres a seis segundos
Texto en pantalla: "Somos BlackStones · +500 mesadas colocadas en CABA
y GBA"
Logo de BlackStones centrado sobre fondo crema durante un segundo.
Transición rápida a montaje de cuatro planos de medio segundo cada uno:
cocina terminada con mesada de granito gris, cocina con isla de cuarzo
blanco, baño con mesada de mármol oscuro, cocina con sinterizada
premium. Cada plano es una mesada real colocada, distinto barrio,
distinto material, distinto estilo. Volumen visible en medio segundo
por plano.

ESCENA 3 — seis a diez segundos
Texto en pantalla: "Mandanos largo x ancho · Presupuesto en 24hs"
Plano cerrado de un celular en mano. Pantalla de WhatsApp abierta. Se
ve el teclado y un mensaje siendo escrito: "Hola, la mesada es de 2.40
x 0.60". El mensaje se envía. Transición a la respuesta de BlackStones
en el mismo chat: presupuesto estructurado con rangos de precio,
materiales listados, "todo incluido" visible. Tildes azules. Respondido.
Claro. Rápido.

ESCENA 4 — diez a quince segundos
Texto en pantalla: "Medición · Fabricación · Colocación · Todo adentro"
Tres planos de un segundo y medio cada uno. Primero: profesional con
metro láser midiendo una cocina real, el haz rojo proyectándose sobre
el mueble bajo mesada, anotando en tablet. Segundo: máquina CNC
cortando una placa de cuarzo, chorro de agua impactando la piedra, la
fresa avanzando con precisión milimétrica por la línea de corte.
Tercero: dos instaladores bajando con cuidado una mesada pulida sobre
los muebles de cocina, el momento exacto del encaje, manos ajustando
la posición final.

ESCENA 5 — quince a diecinueve segundos
Texto en pantalla: "Granito · Cuarzo · Sinterizada"
Tres planos de un segundo cada uno, extremo close-up: mano pasando
lentamente sobre granito gris mara pulido, los cristales minerales
visibles bajo la luz cálida. Mano pasando sobre cuarzo blanco con vetas
grises suaves, superficie uniforme y lisa. Mano pasando sobre
sinterizada oscura con vetas doradas, brillo de espejo reflejando la
luz. Cada material se siente táctil. La mano sobre la piedra es
deliberada: transmite calidad que se puede tocar.

ESCENA 6 — diecinueve a veinticinco segundos
Texto en pantalla: "Fecha de entrega en días corridos. No en 'semanas'.
En días."
Timeline gráfico limpio sobre fondo crema: "Medición técnica" con
ícono → flecha → "Día quince a veinte → Colocación" con ícono. Simple,
lineal, concreto. Transición a plano real: dos instaladores caminando
con la mesada terminada hacia la puerta de una casa. La pieza entra por
la puerta. Plano detalle de la mesada descendiendo sobre los muebles.
El nivel burbuja sobre la superficie confirmando la nivelación. Todo
calza.

ESCENA 7 — veinticinco a treinta segundos
Texto en pantalla: "Tu cocina lista"
La misma cocina de la escena uno, misma ventana, misma luz, mismos
muebles. Pero ahora con la mesada de cuarzo blanco colocada arriba. La
bacha conectada. Una mano abre la canilla y el agua corre por primera
vez. Alguien apoya un mate y un termo sobre la mesada nueva. Una mano
pasa sobre la superficie sintiendo el acabado. La cocina está viva.
Cálida, real, habitada. El contraste con la escena uno cierra el arco
visual.

ESCENA 8 — treinta a treinta y tres segundos
Texto en pantalla: "Mandanos largo x ancho por WhatsApp · Hoy tenés
cotización"
Logo BlackStones centrado en fondo crema. Debajo: "Mesadas de cocina y
baño". Debajo: "blackstones.com.ar". Ícono de WhatsApp verde a la
derecha del URL. Frame limpio, estático, tres segundos de respiro. El
CTA en pantalla repite la acción exacta: mandá dos números, largo por
ancho, y hoy tenés respuesta.
```

---

## Reglas de esta estructura

| Regla | Por qué |
|---|---|
| **CONCEPTO siempre primero, NARRACIÓN siempre después, ESCENAS al final** | Brief → Audio → Imagen. Es el flujo de producción. No alterar el orden |
| **Texto en pantalla SIEMPRE entre comillas** | Para que el editor copie literal sin reinterpretar |
| **Tiempos en segundos, no minutos** | Videos sociales son < 60s. Granularidad de segundo es lo que necesita el editor |
| **Descripción visual descriptiva, no abstracta** | "Plano cerrado de mano sobre granito gris pulido bajo luz cálida" SÍ. "Algo de calidad" NO |
| **Texto de pantalla puede sintetizar la narración**, no copiarla | El subtítulo lee 2x más rápido que la voz. Sintetizá: "+500 mesadas colocadas en CABA y GBA" en lugar de "Más de quinientas mesadas colocadas en cocinas y baños de CABA y GBA" |
| **Estilo visual declarado en CONCEPTO** | "Fotográfico real, luz natural" es declaración. El editor no puede improvisar entre AI y real, ni entre estudio y exterior |
| **Voz declarada en CONCEPTO** | El que va a generar la voz necesita saber masculina/femenina, edad, tono, ritmo. Nada implícito |

---

## Cómo se conecta con ElevenLabs

El bloque **NARRACIÓN** de este documento es lo que se procesa con ElevenLabs **PERO** con cues de entonación agregados (ver `guion-ganador-2026-05.md`).

Flujo:
1. Escribís el guion en este formato CapCut (sin cues)
2. Copiás el bloque NARRACIÓN
3. Le agregás los cues `[excited]` `[happy]` `[thoughtful]` `[surprised]` etc. → eso vive en `guion-ganador-*.md`
4. ElevenLabs procesa la versión con cues → genera el audio
5. CapCut recibe: el audio (de ElevenLabs) + las ESCENAS VISUALES (de este doc) + texto en pantalla (de este doc)

**Las dos versiones se mantienen sincronizadas** — si cambia la NARRACIÓN, hay que regenerar el audio de ElevenLabs y actualizar ambos archivos.

---

## Variantes / nuevos guiones

Cuando se cree un nuevo guion CapCut:
- Archivo nuevo en `video-scripts/capcut-{nombre-corto}-{YYYY-MM}.md`
- Mismo formato (los 3 bloques)
- Header con avatar, ángulo, status, fecha
- Cross-link al archivo correspondiente de ElevenLabs

Ejemplos posibles:
- `capcut-cliente-recurrente-2026-06.md` — para angle de upsell a cliente que ya entregó
- `capcut-celestial-blue-2026-05.md` — guion específico para mostrar pieza estrella
- `capcut-arquitecta-2026-07.md` — angulo para avatar arquitecta

---

## Trigger de revisión del formato

Solo se revisa este documento si:
- Cambia la herramienta de edición (CapCut → otra)
- Cambia el formato de input que CapCut acepta
- Aprendemos que un campo extra mejora la consistencia del output (ej: agregar "audio adicional" o "música de fondo")

Default: revisión cada 6 meses.
