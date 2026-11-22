# Los chats de WhatsApp de agosto a noviembre de 2026: qué preguntamos primero y qué vuelve (22/11/2026)

Conteo crudo sobre los chats de clientes que el dueño pegó en las sesiones de trabajo para armar respuestas y cotizaciones. Motivo: el 22/11/2026 el dueño decidió que la primera pregunta del chat sea en qué momento está el cliente (muebles colocados, o para cuándo). Antes de escribir la pregunta se contó qué pasa hoy.

Qué se hizo con esto: `01-strategy/decision-log/2026-11-22-la-primera-pregunta-clasifica.md`.

Acá no hay nombres, teléfonos ni textos de clientes: solo cantidades.

## De dónde salen

- 208 pegados con formato de chat, de 162 clientes distintos. Un chat por teléfono; si el mismo se pegó varias veces, se tomó el pegado más largo.
- Por mes del primer mensaje: agosto 2, septiembre 4, octubre 77, noviembre 79.
- Mediana de 5 mensajes por chat: son, sobre todo, el arranque de la conversación.
- "La marca" es todo lo que figura escrito por BLACKSTONES: el bot y las personas, sin distinguir.

## Cómo se contó

- Un programa separa cada renglón "[hora, fecha] quién: texto" y busca palabras. Lo que encontró se leyó renglón por renglón para sacar lo que no era.
- "Contó en qué momento está": el cliente dijo, sin que se lo pregunten, si tiene los muebles, para cuándo los espera o si tiene apuro.
- "Vuelve": el dato aparece en lo que el cliente escribe justo después de la pregunta, antes de que la marca vuelva a hablar. Medidas, bacha o anafe y material o color se detectan por palabras. La zona no se pudo detectar así y no se contó.

## En qué momento está el cliente

```
  6 de 162   lo contaron solos
             (3 hablaron de los muebles: uno los recibía esa semana y dos todavía no los tenían;
              2 hablaron del apuro o de una demora del proyecto;
              1 era un recambio: la mesada estaba rota)
  3 de 162   dijeron que estaban en una remodelación, sin decir en qué punto
  5 de 162   nos preguntaron a nosotros cuánto tardamos o cuándo podíamos ir a medir
  1 de 162   chats en los que la marca preguntó por los muebles
             (en la misma oración que el pedido de medidas: volvieron las medidas, no los muebles)
  0 de 162   chats en los que la marca preguntó para cuándo la necesita
```

## Primera pregunta de la marca, y qué vuelve en la respuesta siguiente

Primera pregunta que hace la marca en cada chat, sin contar el saludo "¿Cómo podemos ayudarte?":

```
  78 chats   en el pegado la marca todavía no había preguntado nada
  26 chats   lista de 3 cosas
  14 chats   lista de 4 cosas
   9 chats   lista de 2 cosas
  26 chats   una sola cosa (material o color 11, medidas 6, zona 5, bacha o anafe 4)
   9 chats   otra pregunta
```

Qué vuelve, contando solo los chats en los que el cliente contestó algo:

```
  lista de 4 cosas   medidas  8 de 14 · bacha o anafe 7 de 14 · material o color 7 de 14
  lista de 3 cosas   medidas 15 de 23 · bacha o anafe 9 de 18 · material o color 9 de 18
  lista de 2 cosas   medidas  3 de 7  · bacha o anafe 1 de 1  · material o color 3 de 7
  una sola cosa      medidas  6 de 6  · bacha o anafe 4 de 4  · material o color 7 de 10
```

Sumando las listas: lo pedido volvió 62 veces de 116 (53 %). Sumando las preguntas de una sola cosa: 17 de 20 (85 %).

## Lo que estos números no dicen

- **No son todos los leads.** Son los chats que el dueño pegó para pedir ayuda. Un chat en el que el cliente nunca le contestó al bot casi no se pega. Por eso de acá no sale cuánta gente contesta; sale qué vuelve cuando contestan.
- **El pegado termina donde hacía falta la ayuda.** Lo que el cliente dijo después, por chat o por teléfono, no está.
- **La detección por palabras es gruesa.** Una "lista de 3 cosas" incluye algunos mensajes que no eran el primero del chat, y un "blanco" en la respuesta cuenta como color aunque el cliente hablara de los azulejos.
- **Las preguntas de una sola cosa son pocas** (de 4 a 10 chats cada una).
- **No hay cierre.** No se sabe cuáles de estos 162 terminaron en contrato.
