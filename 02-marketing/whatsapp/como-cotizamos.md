# Cómo cotizamos por WhatsApp

> Para cualquier IA o persona a la que el dueño le pegue un chat y le diga "cotizale". Armado el 17/11/2026 con las cotizaciones reales que se hicieron con el dueño entre octubre y noviembre de 2026 y con sus correcciones. El texto del mensaje está en `flow.md` (sección 4, "Formato vigente de la cotización"); acá está el método y las reglas.
>
> `status:` en uso. Lo que dice "sin confirmar" es práctica que el dueño no objetó, no una regla suya.

## Antes de cotizar: qué trajo el cliente y si tiene el mueble (dueño, 22/11/2026)

El dueño fijó ese día el primer mensaje (cuatro datos, la pregunta de si los muebles ya están colocados y despejados, y el link de los colores; si es una instalación nueva o un reemplazo se pregunta en el segundo mensaje) y tres criterios: "esto nos hace entender dónde poner los esfuerzos"; "lo que no quiero es sobre-trabajar para el que no tiene el mueble listo ni siquiera"; y, del que escribe la medida y el color en una línea, "estos son los que abundan" y "los que hay que resolver rápido".

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
- **Bacha armada (hecha en el mismo material que la mesada):** no lleva traforo. Se cobra como m² al precio del material: la tapa, más el interior de la bacha (largo × ancho), más 0,60 m² fijos por cada bacha. Es la cuenta de la calculadora (sección Baño, "Bacha armada"; `03-product/calculadora/functional-map.md`, 6.1). Sale cerca del doble que con traforo y una bacha comprada aparte: si el cliente pidió algo económico, se le ofrecen las dos.
- **Zócalo de 5 cm:** incluido, sin cargo. **Más alto que 5 cm se cobra** como m² (metros lineales × alto) al precio del material.
- **Sinterizado: siempre con regrueso de 4 cm.** Se le suman 4 cm al largo y 4 cm al ancho de cada pieza antes de calcular, y se dice en el mensaje. Excepción hablada con el dueño: donde el peso importa, sin regrueso.
- **Flete y colocación:** CABA $ 200.000, GBA $ 300.000. Si la zona no tiene valor conocido, va **`$ ____`** en el mensaje y el aviso abajo; el número lo pone el dueño.
- **Moneda:** importados en USD, nacionales en pesos, como salen de la calculadora. En USD se puede sumar una línea "Al dólar oficial de hoy son $ …" (el oficial de DolarHoy, el mismo de la calculadora).
- **Redondeo:** en el mensaje, sin centavos. La cuenta exacta va en "Para revisar".

## Reglas del dueño

- **El que abre con medidas, material y color, aunque no sea exacto ("granito negro"): se cotiza conservador** (22/11/2026). Con bacha y anafe, en los dos más pedidos de ese color, y se pregunta el barrio o la zona. En granito negro, los dos más pedidos son Negro Boreal y Negro Brasil. El mensaje es breve y no dice qué incluye: "más largo no es mejor". El formato está en `flow.md`, sección 2. Vale también cuando dijo con exactitud qué agujeros lleva y un color concreto: el 26/11/2026, de una cotización así, el dueño mandó solo el saludo, el número y el plazo, y sacó la línea de "Incluye…", el aviso de manchas, lo de la garantía y las preguntas de la zona y del mueble. Eso va después, cuando el cliente sigue la charla.
- **El que manda las medidas o el plano y pide materiales y precios, sin decir color: el rango de cada material, y listo** (22/11/2026). Con los catálogos. Es la excepción a "sin color, sin número": no es un número, es un rango. El formato y de dónde sale cada rango están en `flow.md`, sección 2.
- **Se habla amable** (22/11/2026). Una pregunta lleva una entrada suave: "Para tenerlo en cuenta, ¿los muebles ya están colocados y despejados?", no la pregunta seca.
- **Sin color elegido, sin número.** Si pidió "sinterizado" y nada más, se le manda el catálogo y se le pide que elija (17/10/2026). Si dijo una familia de color ("negro", "blanco y negro"), se cotiza uno concreto de cada una y se dice cuál se eligió.
- **Granito claro y poroso (Blanco Dallas y parecidos): se cotiza y se avisa que tiende a mancharse.** Un aviso profesional, de dos líneas, sin asustar ni alargar (17/10/2026).
- **Negro Absoluto en granito es carísimo:** se dice que es importado y más costoso, y se ofrece el mismo negro en cuarzo (10/11/2026).
- **Cada material, con su verdad:** el granito aguanta la olla caliente; el cuarzo no aguanta el calor directo; el sinterizado aguanta todo. Si el cliente ya lo sabe, se le da la razón.
- **Granito: no lleva garantía, y se dice** (regla dura de `CLAUDE.md`).
- **Es un presupuesto estimado:** el técnico rectifica en obra y ahí se cierra el número. Va siempre.
- **Al cliente no se le dice "presupuesto cerrado"** (25/11/2026: "nada de presupuesto cerrado, porque post-medición pueden cambiar los m2"). Se le dice "el presupuesto". El número se cierra después de la medición, no antes. Los formatos de `flow.md` y el de `recommendation-patterns.md` ya no lo dicen. Todavía lo dicen, y lo decide el dueño: el mensaje con el que el bot de WhatsApp pasa el chat al equipo, cuatro páginas de las guías del sitio (`/guias/`) y los docs del mecanismo (`01-strategy/mechanism.md`, `00-brand/positioning/positioning.md`).
- **Plazo: 20 días corridos desde la medición**, con nuestro flete y colocación. **Si retira el cliente, 12 días corridos desde la medición** (24/11/2026). Ningún otro número.
- **Se mide con el bajo mesada colocado.**
- **Ofrecer una segunda opción** cuando hay una más económica o una que resuelve la duda del cliente. Dos o tres como mucho.
- **Fuera de CABA y GBA** (Mercedes, Capilla del Señor, Marcos Paz): no se decide acá. Se cotiza el material, el flete queda en blanco y se le avisa al dueño la distancia.

## Cómo se sigue después del presupuesto (dueño, 24/11/2026)

Lo fijó el dueño al contestarle a un cliente que, con el número ya en la mano, preguntó cuánto se tarda desde que contrata hasta que queda instalada:

1. **Primero, la seña del 50 %.** Es la que habilita la visita del técnico, que va al domicilio a rectificar las medidas. Sin seña no hay visita.
2. **Desde esa medición, 20 días corridos para entregar.**
3. **Si el cliente no contrata nuestro flete y colocación, a los 12 días corridos de la medición puede retirar.**

Cuando el dueño pide explicarlo en una cotización, va el bloque "Cómo seguimos" de `flow.md`, sección 6: al final, antes de la pregunta de los muebles. Cuando el cliente pregunta cuánto se tarda, primero se le contesta eso en una línea ("Desde la medición, en 20 días corridos queda instalada") y después van los tres pasos.

**Si la conversación ya viene andando, va solo la línea del plazo (dueño, 25/11/2026).** A un cliente que todavía estaba pidiendo números y preguntó cuánto demora se le armaron la línea y los tres pasos, y el dueño los sacó: "pasa como que ya llevamos conversación; no le digas cómo seguimos". Ahí se contesta el plazo en una línea y nada más, y el mensaje se escribe como una respuesta más de la charla: sin saludo y sin repetir lo que ya se le dijo ("Incluyen lo mismo que la anterior"). Los tres pasos quedan para cuando el dueño los pide. Lectura de quien escribió esto, sin confirmar: también para el cliente que ya tiene el número, no pide nada más y pregunta cómo se contrata, que fue el caso del 24/11.

**El otro 50 % se paga con la entrega e instalación (dueño, 27/11/2026).** Lo dijo para contestarle a un cliente que preguntó qué tenía que llevar para avanzar: "explicarle la seña del 50 %, tomamos las medidas y empezamos la fabricación; el resto, 50 %, con entrega de instalación". El mensaje que salió: la seña del 50 % con su monto; con la seña se agenda la visita del técnico, que toma las medidas, y se empieza la fabricación; el otro 50 %, con la entrega e instalación.

Lo que el dueño todavía no dijo, y por eso no va en el mensaje: cuándo se entrega la bacha del cliente, si la seña se calcula con el flete adentro y si con tarjeta hay recargo. Si el cliente lo pregunta, lo contesta el dueño.

## Antes de mandar, mirar

- **Lo que ya dijo el cliente no se vuelve a preguntar.** El bot a veces repite el pedido de datos; si pasó, se pide disculpas en una línea.
- **Lo que el cliente escribió no se pisa con lo que se ve en una foto.** Una foto de referencia muestra el estilo, no la cantidad: si escribió "dos bachas", no se cuentan cuatro porque la foto tiene bacha doble (dueño, 25/11/2026: "es claro, justo te confundiste en donde fue claro").
- **Si falta un dato menor (la medida de la bacha), se infiere, se cotiza y el mensaje dice qué se asumió, para que el cliente corrija** (dueño, 25/11/2026: "vamos a cotizarle así, si no que después nos corrija; no nos pasó medidas de la bacha, pero las inferimos"). Ese día primero dijo "mejor preguntarle y que sea ella que nos aclare" y enseguida eligió cotizar: preguntar antes queda para cuando de verdad no se entiende qué pide. Al dueño se le dejan las cuentas de las otras lecturas posibles, por si el cliente corrige.
- **Una medida rara se marca**, no se corrige sola (una "mesada de 0,12 × 0,60" puede ser 1,20).
- **Cocina de pie no es anafe:** entre dos piezas con una cocina de pie no hay piedra ni traforo.
- **Departamento:** preguntar por ascensor; la subida por escalera se cobra aparte.
- **Si la pileta la compra con nosotros**, se suma aparte.
- **Sin nombre**, el mensaje arranca "Hola, buen día".
- **Showroom**, si preguntan dónde estamos: Av. Juan B. Alberdi 3575, CABA. Lunes a viernes de 9 a 17, sábados de 9 a 13.

## Si el cliente todavía no pasó nada

Se manda la primera respuesta de `flow.md` (sección 2, "Formato vigente de la primera respuesta"): la vieja confiable, sin los puntos que el cliente ya haya contestado. Los dos catálogos y la guía de tres líneas de materiales van después, si dice que no sabe qué material.

## Sin confirmar

- Dar un precio de referencia en un color popular cuando el cliente nombra una marca sin color (se hizo una vez con Silestone, después de la regla "sin color, sin número"; el dueño no dijo nada).
- La línea con la conversión al dólar oficial.
- Regrueso de 15 cm en el frente y el lateral en una mesada de baño con bacha armada. El dueño lo pidió así en una cotización del 25/11/2026 (en la calculadora: Baño, "Frente + 1 lat", 15 cm); no dijo si vale para todas.

## Lo que nunca va al repo

Nombres, teléfonos ni chats de clientes. Este doc se armó con conversaciones reales y no cita ninguna.
