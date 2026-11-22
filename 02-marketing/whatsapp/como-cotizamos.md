# Cómo cotizamos por WhatsApp

> Para cualquier IA o persona a la que el dueño le pegue un chat y le diga "cotizale". Armado el 17/11/2026 con las cotizaciones reales que se hicieron con el dueño entre octubre y noviembre de 2026 y con sus correcciones. El texto del mensaje está en `flow.md` (sección 4, "Formato vigente de la cotización"); acá está el método y las reglas.
>
> `status:` en uso. Lo que dice "sin confirmar" es práctica que el dueño no objetó, no una regla suya.

## Antes de cotizar: qué trajo el cliente y si tiene el mueble (dueño, 22/11/2026)

El dueño fijó ese día el primer mensaje (cinco puntos; el 5 es "¿El mueble ya está colocado y despejado?") y tres criterios: "esto nos hace entender dónde poner los esfuerzos"; "lo que no quiero es sobre-trabajar para el que no tiene el mueble listo ni siquiera"; y, del que escribe la medida y el color en una línea, "estos son los que abundan" y "los que hay que resolver rápido".

La respuesta de quien cotiza empieza diciendo, en una línea, qué trajo el cliente y si se sabe si tiene el mueble. Y según eso:

- **Trajo la medida y el color (o el tono):** la cotización corta de `flow.md`, en el momento. Un color concreto, su número y, si todavía no dijo nada del mueble, esa pregunta como última línea. No se le pide nada antes.
- **Le falta la medida o el color:** el primer mensaje de `flow.md`, solo con los puntos que faltan. De lo que ya sabemos, no se pregunta. Sin color no hay número, como siempre.
- **Dijo que ya tiene el mueble:** todo el trabajo. Opciones para comparar, ajustes y seguimiento.
- **Dijo que todavía no lo tiene:** hasta ahí. Queda el número que ya recibió, o la respuesta corta de `flow.md`. Sin opciones ni alternativas.

Si el cliente contesta lo demás y no lo del mueble, se le repite una vez, al final del mensaje siguiente. Un arquitecto o una obra con plano no se clasifican así: ahí decide el dueño. El porqué: `01-strategy/decision-log/2026-11-22-la-primera-pregunta-clasifica.md`.

## Qué entrega quien cotiza

Siempre tres cosas, en este orden:

1. **El mensaje listo para pegar**, en un bloque de código, con el formato de `flow.md`.
2. **"Para revisar antes de mandarlo"**: la cuenta (piezas, m², precio por m², traforos) y todo lo que se asumió. El dueño revisa y manda; nadie más le escribe al cliente.
3. Si sirve, cómo cargarlo en la calculadora (qué piezas, qué agujeros, qué flete).

## La cuenta

- **m² = largo × ancho de cada pieza, sumados.** Una L se parte en sus tramos. Los recortes (columna, esquina) no se descuentan.
- **Precio por m²: el de la calculadora** (`COLORS_DB` en `site/public_html/calculadora/calc.html`; listas en `03-product/calculadora/lista-precios-*`). Si el dueño dice un precio por m² en el chat ("cotizale en 640 usd x m2"), manda ese.
- **Traforo:** USD 80 cada uno, o $ 100.000 si el material está en pesos. Bacha y anafe son dos.
- **Zócalo de 5 cm:** incluido, sin cargo. **Más alto que 5 cm se cobra** como m² (metros lineales × alto) al precio del material.
- **Sinterizado: siempre con regrueso de 4 cm.** Se le suman 4 cm al largo y 4 cm al ancho de cada pieza antes de calcular, y se dice en el mensaje. Excepción hablada con el dueño: donde el peso importa, sin regrueso.
- **Flete y colocación:** CABA $ 200.000, GBA $ 300.000. Si la zona no tiene valor conocido, va **`$ ____`** en el mensaje y el aviso abajo; el número lo pone el dueño.
- **Moneda:** importados en USD, nacionales en pesos, como salen de la calculadora. En USD se puede sumar una línea "Al dólar oficial de hoy son $ …" (el oficial de DolarHoy, el mismo de la calculadora).
- **Redondeo:** en el mensaje, sin centavos. La cuenta exacta va en "Para revisar".

## Reglas del dueño

- **Sin color elegido, sin número.** Si pidió "sinterizado" y nada más, se le manda el catálogo y se le pide que elija (17/10/2026). Si dijo una familia de color ("negro", "blanco y negro"), se cotiza uno concreto de cada una y se dice cuál se eligió.
- **Granito claro y poroso (Blanco Dallas y parecidos): se cotiza y se avisa que tiende a mancharse.** Un aviso profesional, de dos líneas, sin asustar ni alargar (17/10/2026).
- **Negro Absoluto en granito es carísimo:** se dice que es importado y más costoso, y se ofrece el mismo negro en cuarzo (10/11/2026).
- **Cada material, con su verdad:** el granito aguanta la olla caliente; el cuarzo no aguanta el calor directo; el sinterizado aguanta todo. Si el cliente ya lo sabe, se le da la razón.
- **Granito: no lleva garantía, y se dice** (regla dura de `CLAUDE.md`).
- **Es un presupuesto estimado:** el técnico rectifica en obra y ahí se cierra el número. Va siempre.
- **Plazo: 20 días corridos desde la medición.** Nunca otro número.
- **Se mide con el bajo mesada colocado.**
- **Ofrecer una segunda opción** cuando hay una más económica o una que resuelve la duda del cliente. Dos o tres como mucho.
- **Fuera de CABA y GBA** (Mercedes, Capilla del Señor, Marcos Paz): no se decide acá. Se cotiza el material, el flete queda en blanco y se le avisa al dueño la distancia.

## Antes de mandar, mirar

- **Lo que ya dijo el cliente no se vuelve a preguntar.** El bot a veces repite el pedido de datos; si pasó, se pide disculpas en una línea.
- **Una medida rara se marca**, no se corrige sola (una "mesada de 0,12 × 0,60" puede ser 1,20).
- **Cocina de pie no es anafe:** entre dos piezas con una cocina de pie no hay piedra ni traforo.
- **Departamento:** preguntar por ascensor; la subida por escalera se cobra aparte.
- **Si la pileta la compra con nosotros**, se suma aparte.
- **Sin nombre**, el mensaje arranca "Hola, buen día".
- **Showroom**, si preguntan dónde estamos: Av. Juan B. Alberdi 3575, CABA. Lunes a viernes de 9 a 17, sábados de 9 a 13.

## Si el cliente todavía no pasó nada

Se manda la primera respuesta de `flow.md` (sección 2, "Formato vigente de la primera respuesta"): el mensaje de cinco puntos del dueño, sin los puntos que el cliente ya haya contestado. Los dos catálogos y la guía de tres líneas de materiales van después, si dice que no sabe qué material.

## Sin confirmar

- Dar un precio de referencia en un color popular cuando el cliente nombra una marca sin color (se hizo una vez con Silestone, después de la regla "sin color, sin número"; el dueño no dijo nada).
- La línea con la conversión al dólar oficial.

## Lo que nunca va al repo

Nombres, teléfonos ni chats de clientes. Este doc se armó con conversaciones reales y no cita ninguna.
