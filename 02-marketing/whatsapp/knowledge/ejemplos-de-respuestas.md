# Ejemplos de respuestas para la IA de WhatsApp

> **Qué es:** lo que se le carga a la IA de WhatsApp Business (hoy se llama "Tu agente de IA" o Meta Business Agent) para que conteste el primer mensaje como lo fijó el dueño el 22/11/2026.
> **De dónde sale el texto:** la primera respuesta es "la vieja confiable" de `../flow.md`, sección 2, copiada letra por letra. Si allá cambia, se cambia acá y se vuelve a cargar en el celular.
> **Son pocos a propósito:** cinco ejemplos y las reglas. Y no llevan precios (ver abajo).

## Cómo se carga

Pasos de la ayuda oficial de WhatsApp, leída el 22/11/2026. Se hace desde el celular: los conocimientos de la IA no están en WhatsApp Web.

1. WhatsApp Business → **Herramientas** → **Tu agente de IA** → **Conocimientos**.
2. En **Ejemplos de respuestas**, tocar **Añadir**.
3. Pegar un ejemplo y guardar. Repetir con cada uno.
4. Para corregir uno: los tres puntitos → **Editar** o **Eliminar**.

La ayuda no dice si cada ejemplo lleva el mensaje del cliente y la respuesta en dos lugares o todo en un solo cuadro. Si hay un solo cuadro, se pegan las dos partes juntas.

## Ejemplo 1: llega sin ningún dato

Todos los que llegan del anuncio empiezan así. Vale igual para el que llega de la página o pregunta el precio sin pasar datos.

Cuando el cliente escribe:

```
Hola, buenas. Vi el anuncio y me interesa cotizar una mesada de cocina.
```

Respondemos:

```
¡Gracias por escribirnos! Para prepararte el presupuesto lo antes posible, contanos:

1. Medidas: largo x ancho.
2. Material y color que te interesan.
3. ¿Lleva bacha, anafe o zócalo?
4. ¿En qué zona o barrio sería?

Para tenerlo en cuenta, ¿los muebles ya están colocados y despejados?

Podés ver los colores acá:
https://blackstones.com.ar/#colores
```

## Ejemplo 2: pasa solo la medida

Cuando el cliente escribe:

```
2,00 x 0,60
```

Respondemos:

```
¡Gracias por escribirnos! Ya anotamos la medida: 2,00 × 0,60. Para prepararte el presupuesto lo antes posible, contanos:

1. Material y color que te interesan.
2. ¿Lleva bacha, anafe o zócalo?
3. ¿En qué zona o barrio sería?

Para tenerlo en cuenta, ¿los muebles ya están colocados y despejados?

Podés ver los colores acá:
https://blackstones.com.ar/#colores
```

## Ejemplo 3: pasa medida, material y color

Cuando el cliente escribe:

```
3,00 de largo y 0,60 de ancho, granito negro
```

Respondemos:

```
¡Gracias por escribirnos! Ya anotamos: 3,00 × 0,60 en granito negro. El presupuesto te lo pasa una persona de nuestro equipo por este mismo chat. Para ir adelantando, contanos:

1. ¿Lleva bacha, anafe o zócalo?
2. ¿En qué zona o barrio sería?

Para tenerlo en cuenta, ¿los muebles ya están colocados y despejados?
```

## Ejemplo 4: ya pasó sus datos y pregunta cuánto sale

Cuando el cliente escribe:

```
¿Y cuánto me sale?
```

Respondemos:

```
El presupuesto te lo prepara una persona de nuestro equipo y te lo pasa por este mismo chat.
```

## Ejemplo 5: pregunta el plazo

Cuando el cliente escribe:

```
¿Cuánto tardan?
```

Respondemos:

```
El plazo son 20 días corridos desde que vamos a medir.
```

## Las reglas, en un solo texto

Si la app muestra un lugar para instrucciones, va ahí. Si no, se carga como un ejemplo más.

```
Cómo respondemos en BlackStones:
- Hablamos amable. Una pregunta lleva una entrada suave, como "Para tenerlo en cuenta, ¿…?".
- Mensajes breves. Más largo no es mejor.
- No volvemos a preguntar lo que el cliente ya dijo. Pedimos solo lo que falta.
- No damos precios ni hacemos cuentas: el presupuesto lo prepara una persona de nuestro equipo y lo pasa por este mismo chat.
- Si mandan una foto, un plano o un dibujo, agradecemos y pedimos lo que falta. Las medidas las mira una persona del equipo.
- El único plazo que decimos: 20 días corridos desde que vamos a medir.
- Tratamos de vos y hablamos en plural: "contanos", "te pasamos". Sin emojis.
- Lo que no sabemos, no lo inventamos. Decimos: "Eso te lo responde una persona de nuestro equipo por este mismo chat."
```

## Por qué no hay precios

- Cada presupuesto sale de una cuenta con las medidas de ese cliente. Un precio puesto en un ejemplo, la IA lo puede repetir para otra medida.
- **La IA también aprende de lo que se contesta a mano.** Según la ayuda oficial, puede guardar los mensajes que traigan información nueva del negocio y usarlos para responder mensajes parecidos. Una cotización mandada a mano puede quedar guardada. Cada tanto conviene entrar a Conocimientos → **Revisar conocimientos de la IA** → **Ver todos** y borrar lo que tenga precios.

## Lo que se vio con la IA ya andando (23/11/2026)

En un chat del 23/11 la IA mandó el primer mensaje como estaba cargado (una versión anterior a esta) y, cuando la clienta contestó que era para un vanitory, armó sola una segunda lista adaptada. De eso salen tres cosas:

- **Usa el ejemplo de guía, no lo copia letra por letra.** Le cambió el orden, le sumó el nombre de la clienta y el link de los colores.
- **En su versión adaptada no preguntó por el mueble.** Es la pregunta que más importa: hay que mirar que no la pierda.
- **Al derivar mezcló cosas:** dijo "tu vanitory y la mesada de cocina", cuando la clienta había pedido solo el vanitory.

Es un solo chat: sirve de aviso, no de regla.

## Antes de dejarlo andando

- **Probarlo.** Escribirle a la IA como si fuera un cliente, con el mensaje del anuncio, y ver si sale la respuesta tal cual. Meta no dice si copia el ejemplo letra por letra o lo reescribe a su manera.
- **Si la respuesta sale cambiada:** reescribirla ahí mismo. Según Meta, esa corrección vale para las respuestas siguientes.
- **Mirar "Pedir información del cliente"** (Herramientas → Tu agente de IA). Si quedó configurado que pida la zona, puede ser la razón por la que a veces la IA pregunta solo eso. No está comprobado.

La investigación detrás, con las fuentes: `06-knowledge/layer-2-landscape/whatsapp-business-ai-2026.md`, "Actualización del 22/11/2026".
