---
fecha: 2026-11-19 (cerrado el 20/11/2026; Relación-Contrato se rehízo ese mismo día y después se le marcó la separación, ver abajo)
tema: Barrido de diseño del tablero por columnas, Relación-Contrato y el calendario
status: decidido (el dueño fijó el criterio; las piezas las eligió quien lo hizo y se pueden revertir de a una)
---

# Barrido de diseño: un sistema serio, simple y claro

## Qué pidió el dueño (19/11/2026)

"Trabajamos como seniors de diseño en la desaturación del tablero formato por columnas: elegancia, claridad, orden, microdetalles. Barrido completo, profundo. Todo con criterio." Después: "lo mismo Relación-Contrato" y "quitar la descripción del hover". Y el criterio, en sus palabras:

1. Claridad y orden.
2. Estética, microdetalle y elegancia.
3. Conveniente, accesible, completo sin saturar.

"En una oración: un sistema serio, simple y claro. Efectivo y elegante."

El problema de fondo, también en sus palabras: "abría el tablero formato columna y me perdía en 3 segundos"; "no debe quedar como un arroz con pollo, donde todo parece lo mismo y el ojo no sabe seguir un orden"; "¿no hay mucho microtexto sin jerarquía mezclado?". Viendo la primera versión: "si todas las etapas tienen el mismo color o no hay separación clara, todo parece parte de lo mismo; hay que buscar y forzar el orden en vez de que venga natural". Y sobre cómo trabajar: "usted es el experto, tiene a disposición toda la web"; "puede hacer investigaciones de colores, el ojo humano, el cerebro, los patrones, la memoria, el orden".

## En qué se apoya

- **Investigación externa** (qué dice cada fuente y qué hicimos con eso): `06-knowledge/layer-2-landscape/como-se-lee-una-pantalla-de-trabajo.md`. Healey 1996 (no más de siete colores se distinguen de un vistazo), Cowan 2001 (unas cuatro cosas en la cabeza a la vez), NN/g (un fondo compartido agrupa más que la cercanía; cada caja suma ruido), lean/andon (lo normal no se marca; lo anormal, sí, y pide una respuesta), Refactoring UI (las etiquetas aplanan), Ström (planillas de números), WCAG (contraste y no depender solo del color).
- **Dato propio:** el contraste de cada texto, medido en el navegador contra su fondo real. `06-knowledge/layer-0-raw/2026-11-20-contraste-medido.md`.
- **Lo que no hay:** una prueba con el equipo usando el tablero nuevo. Está en "Known unknowns".

## Las reglas que quedaron

1. **El color dice una sola cosa cada vez.** El color de la etapa es de la columna, no de cada ficha: tiñe el carril entero, muy suave, y vuelve en su línea de arriba, en el fondo de su contador y en el marco de la ficha próxima. Así se ven cinco carriles sin leer nada, y las fichas quedan blancas. Rojo es atraso o freno. Ámbar es "pide una acción" o un plazo cerca. Verde es hecho o cobrado. Lo demás, en tinta.
2. **El color de la etapa tiñe, no escribe.** Ningún texto va en el color de la etapa sobre el carril teñido (medido: no llegaba a leerse bien). El contador, "Filtrar", "Próxima" y los rótulos van en tinta o gris.
3. **Un orden de lectura.** Primero la etapa (título de columna), después el tramo de la cola (rótulo con raya), después la ficha. En cada etapa hay un solo punto de entrada: la próxima.
4. **Lo normal no se repite en cada ficha; se dice una vez, arriba.** "9 sin entregar" va en el aviso de la columna, no en nueve fichas. "Vencida" va en el título de la semana, no en cada ficha de esa semana.
5. **La ficha cerrada tiene cuatro voces, y no más de cuatro renglones:** quién (N° y nombre, negrita), qué (material), cómo está (la fecha en texto; pastilla solo si pide atención; "Hoy", en tinta; lo hecho, en tinta con un tilde verde) y la nota (una cita gris con una raya gris).
6. **Todo lo que filtra habla igual** (regla del dueño del 16/11, extendida): apagado, blanco con borde gris y el número en color; prendido, relleno.
7. **Sin descripciones al pasar el mouse.** Lo que decía el globo pasó a `aria-label`. Queda un globo solo donde explica un signo (el "?" de un N° que no está en Presupuestos, el sello de verificado, "CF") o muestra una nota cortada.
8. **Los números, prolijos.** En la planilla de contratos, sin centavos (el exacto está al abrir el contrato); plata a la derecha y texto a la izquierda. Las sumas, redondeadas.
9. **Todo el texto se lee.** Contraste de 4,5:1 o más en claro y oscuro, en computadora, tablet y celular. Se mide con un programa, no a ojo, y entra en la regresión.
10. **En una planilla, el patrón lo hacen columnas que se ven** (20/11/2026): la etapa en su color y la barra de lo cobrado. Desaturar es sacar el color que no dice nada, no todo el color.
11. **Lo que separa se tiene que ver, y se mide como el texto** (20/11/2026). Una raya, una franja o un borde que separa dos cosas se mide con un programa antes de entregarlo. Y dos niveles distintos llevan dos señales distintas (entre contratos, una raya; entre días, una franja), no la misma señal un poco más fuerte.

## Qué cambió respecto de decisiones anteriores del dueño

Cada una se puede volver atrás por separado:

| Antes (decisión) | Ahora | Por qué |
|---|---|---|
| "Sin entregar" en rojo en cada ficha de Por cortar y Por fabricar (16/11) | Solo "Entregado" en la que ya se entregó. Cuántas faltan lo dice el aviso de la columna, que además las filtra | En una cola donde casi todas están sin entregar, el rojo en cada ficha no distinguía nada |
| Contador "3 de 8" en cada ficha cerrada (15/11) | No va. "Lista para pasar" sigue | Era un número más en cada ficha y no cambiaba ninguna decisión; los tildes se ven al abrirla |
| Marco rojo entero si algo frena la ficha | Barra roja a la izquierda | El borde izquierdo quedó libre al sacar el color de la etapa de cada ficha: ahora dice una sola cosa |
| Banda "Próxima" pintada + marco doble | Rótulo chico en gris, marco fino con halo y número lleno | Una sola marca de entrada por etapa, sin tres refuerzos |
| Aviso "11 por avanzar" + un renglón que lo explicaba | "8 atrasadas · 3 ya medidas" en el aviso mismo | El motivo concreto en vez de una palabra que había que explicar |
| Renglón fijo "Por semana de entrega · mismo color junto" | No va | Los títulos de cada semana ya ordenan la cola |
| Notas en caja ámbar (tablero y contratos) | Cita gris con una raya gris; en el contrato, un campo como los demás | La nota se lee, pero no compite con los avisos |
| "Hoy" en dorado | "Hoy" en una pastilla de tinta | El dorado es de la marca, no un estado; y chocaba con el ámbar |
| Lo hecho ("Ya medido", "Entregado"), todo en verde | En tinta, con un tilde verde adelante | El verde pasa a ser una marca; el texto se lee mejor |
| El número de cada columna y "Próxima", en el color de la etapa | En tinta y en gris | Sobre el carril teñido, el texto de color no se leía bien (medido: entre 2,9 y 3,8 a 1) |
| Naranja de Por fabricar y verde de Por archivar | Los mismos, un punto más oscuros | Para que el texto blanco encima llegue a 4,5:1 (estaba en 4,2 y 4,1) |
| Saldo y "Por cobrar" en rojo | En tinta y negrita | Deber un saldo es lo normal de un contrato en curso, no un error |
| Barra verde y la palabra "Enlazado" en cada renglón de contratos (18/11) | La etapa, en una pastilla con su color, es el link a la ficha. Adentro del contrato sigue "Enlazado" | Que haya etapa ya dice que está enlazado; se marcan las que no |
| Montos con centavos en la planilla | Sin centavos; el exacto, al abrir el contrato | Una columna de números se compara mejor si todos tienen la misma forma |

## Corrección del 20/11/2026: Relación-Contrato se rehízo

**Qué dijo el dueño** al ver la planilla ya publicada, con sus datos: "bien lo otro. Este aún quedó desabrido, no de captación de patrón rápido, y caótico".

**Qué había pasado.** En el tablero el color se sacó de las fichas pero se puso en otro lado (el carril). En contratos se sacó y no se puso nada: la etapa quedó en un punto de 8 px, el saldo en tinta, y lo demás en gris. Sin ninguna columna que el ojo pudiera recorrer sin leer, la planilla era una pared de texto. Y el desorden ya estaba de antes y no se había tocado: cada celda centrada por su cuenta (con celdas de uno y de dos renglones, la lectura zigzagueaba), una raya de relleno en cada celda vacía (hasta seis por renglón), la fecha repetida en cada contrato del mismo día y textos cortados con "…" (medidas, procedencia, notas).

**La regla que faltaba:** en una planilla, el patrón lo hacen columnas que se ven, no texto. Desaturar es sacar el color que no dice nada, no todo el color.

**Qué cambió:**

| Antes (19/11) | Ahora | Por qué |
|---|---|---|
| La etapa, un punto de color y el nombre, en la anteúltima columna | Una pastilla teñida con el color de la etapa (el mismo de su columna en el tablero), todas del mismo ancho, al lado del cliente y el material | Es la columna que se recorre de un vistazo: de arriba abajo se ve en qué anda el mes, y una obra vieja que sigue en Por medir salta sola |
| Total, cobrado y saldo, tres números | Los mismos tres, y debajo de lo cobrado una barra: cuánto del contrato ya entró | La segunda columna que se lee sin leer: quién no pagó nada, quién señó, quién terminó |
| La fecha en cada renglón | Una vez por día; una raya más firme separa los días (no alcanzó: ver la segunda corrección, abajo) | Los contratos se leen en tandas, no de a uno |
| Cada celda centrada por su cuenta | Todas apoyan su primera línea a la misma altura | Se lee derecho |
| "—" en cada celda vacía y "Sin total" en la columna del saldo | Lo que no hay queda en blanco; "Sin total" se dice una vez, en su columna | Menos tinta que no dice nada |
| Debajo de la etapa, el turno y el plazo (dos renglones) | Un solo renglón, el que más importa: plazo vencido, sin turno, seña distinta, el turno o el plazo | Todos los renglones miden lo mismo |
| Debajo del nombre: teléfono, vendedor y procedencia. Debajo del material: las medidas | Teléfono y vendedor. La procedencia y las medidas, al abrir el contrato | Eran los textos que se cortaban con "…" |
| La nota que pone sola la importación ("Creado desde la ficha del tablero…") | No se muestra en la planilla (sigue en el contrato) | Era el mismo texto en muchos renglones |
| El resumen del mes, en la letra de siempre | Los números con más presencia y una barra: qué parte de lo contratado ya se cobró | Lo primero que se mira es el mes |
| "Sin fecha de medición" y "Sin turno de instalación", en gris | En rojo, como en el tablero | El mismo hecho, el mismo color en las dos pantallas |
| Los filtros de "qué les falta", con el número en gris | Con el número en ámbar | Igual que los avisos del tablero |

Lo que se mantuvo del 19/11: el saldo en tinta y negrita, los montos sin centavos, sin barra verde ni "Enlazado" en cada renglón, sin descripciones al pasar el mouse, y el contraste medido (4,5:1 o más; ya no hay excepción, porque la raya de las celdas vacías no existe).

Lo que NO cambió, a propósito: un contrato sin enlazar sigue diciendo "No existe en el tablero" (decisión del dueño del 18/11/2026). Ahora va en una pastilla hueca, en la misma columna que las etapas, con "Cargar en Por medir" debajo.

**Known unknown nuevo:** la barra de un contrato con dólares y pesos se calcula con el dólar del día; es una aproximación para la vista, los números de al lado son los exactos.

**Un error en el camino, y lo que dejó.** La barra de lo cobrado se llamó en el código igual que la franja de arriba de la pantalla (mes, buscador, "+ Nuevo contrato") y la dejó aplastada, con los botones tapados. Ninguna prueba lo notó: se vio mirando la captura. Se corrigió antes de entregar y la prueba de diseño ahora comprueba que esa franja se vea entera y se pueda tocar (y se verificó que la prueba falla si el error vuelve). Regla para adelante: las pruebas no reemplazan mirar la pantalla.

## Segunda corrección del 20/11/2026: que la separación se vea

**Qué dijo el dueño** al ver la planilla rehecha, ya publicada con sus datos: "nada mal, se nota mejoría; pero no se nota la separación de una fila de otra, menos los días".

**Qué había pasado (medido).** La raya entre contratos tenía un contraste de 1,20 a 1 contra el blanco de la planilla, y la que separaba los días, 1,34 a 1. Eran dos rayas de 1 px casi del mismo color: ninguna se veía bien, y la diferencia entre "otro contrato" y "otro día" no se podía ver. El error fue tratar la separación como un adorno ("una raya más firme") y no medirla: la regla 9 medía solo el texto. → `06-knowledge/layer-0-raw/2026-11-20-separacion-medida.md`

**Qué cambió:**

| Antes | Ahora | Por qué |
|---|---|---|
| Entre contratos, una raya de 1 px a 1,20:1 | Una raya de 1 px a 1,71:1 (1,88:1 en oscuro) | Se tiene que ver sin buscarla |
| Entre días, la misma raya apenas más oscura (1,34:1) | Una franja de 12 px de lado a lado, del tono del encabezado, con una raya arriba y otra abajo | Otro nivel pide otra señal, no la misma un poco más fuerte. Un fondo se ve aunque sea suave; una raya fina, no |
| La raya cruzaba toda la planilla | Ordenada por fecha, la raya empieza donde empieza el N°: no llega a la columna de la fecha | La fecha queda sola a la izquierda, como el lomo de todo su día |
| La fecha, en seminegrita | En negrita fuerte | Va una vez por día: es el título del día |
| El encabezado y el pie cerraban con la raya suave | Cierran con la misma raya que separa los contratos | Una sola raya en toda la planilla |

Ordenada por otra columna (cliente) no hay días que separar: no hay franjas, la raya va de lado a lado y cada renglón dice su fecha. En tablet y celular no cambia nada: ahí cada contrato ya es una tarjeta.

**Por qué raya y no renglones alternados (uno blanco, uno gris).** La franja del día ya es un fondo; dos fondos para decir dos cosas se confunden. Raya para el contrato y franja para el día son dos señales que no se parecen.

**Tocar la franja no hace nada.** No es parte de ningún renglón: un toque ahí no abre el contrato de abajo (probado).

**De paso se arregló un error viejo:** en tablet y celular la primera tarjeta de la lista no tenía borde arriba (se lo sacaba una regla pensada para la planilla). Lo encontró la prueba nueva.

**En qué se apoya.** NN/g (Laubheimer, 2022): el diseño de una planilla tiene que dejar que el ojo no pierda el renglón al cruzarla, y nombra bordes, renglones alternados y resaltar al pasar el mouse. No da números: los de arriba son nuestros, medidos. Para los días, lo mismo que en el tablero: una región y aire agrupan (NN/g, Harley, 2020). → `06-knowledge/layer-2-landscape/como-se-lee-una-pantalla-de-trabajo.md`, punto 8.

**Known unknown nuevo:** cómo se ve la raya en el monitor del dueño. 1,71:1 es una raya clara pero visible en las pantallas de prueba; si en la suya sigue sin notarse, se sube un escalón (el color está en un solo lugar: `--raya`). Se midió además con el zoom de la pantalla: al 110 % y al 125 % el navegador reparte una raya de 1 px en dos píxeles y algunas quedan más lavadas (entre 1,37 y 1,49 a 1), se dibuje como se dibuje. No sabemos qué zoom usa; la raya de antes (1,20 a 1) con ese efecto quedaba todavía más lavada (por cuenta, cerca de 1,1 a 1; eso no se midió).

**Lo que no se tocó:** las otras listas del sistema que usan la raya suave de antes (la Papelera y los renglones de adentro de la ventana del contrato, Presupuestos, el registro de Control). El dueño habló de la planilla de contratos; las demás se miden con esta misma regla cuando les toque su barrido.

## Knowns

- Probado en local, en computadora (1180, 1440 y 1880 px), tablet y celular, claro y oscuro: `diseno.js` (54 comprobaciones sobre las tres pantallas; 13 son las de la planilla del 20/11 y 10, las de la separación), la medición de contraste y la regresión completa del tablero, contratos, calendario y usuarios.
- No cambió ningún dato ni ninguna regla del servidor: son las páginas (`tablero.php`, `relacion-contrato.php`, `calendario.php`, y los colores de `reportes.php`).
- Nada de lo que se sacó de la ficha cerrada se perdió: está al abrirla o en el aviso de la columna.
- Contraste, medido: en el tablero, Relación-Contrato y Control todo el texto llega a 4,5:1.

## Known unknowns

- Si en el taller alguien miraba el contador "3 de 8" o el "Sin entregar" de cada ficha como su lista de trabajo. Los filtros "Necesitan continuar" y "Con el cortador" siguen.
- Cómo se lee con el tablero real cargado (más fichas con notas largas que en la prueba).
- Safari de iPad: probado en Chromium con pantalla táctil emulada.
- **El calendario apaga demasiado lo que ya pasó.** La medición encontró ahí 22 textos por debajo de 4,5:1, casi todos en días pasados, días de otro mes y obras ya hechas, que se apagan a propósito (algunos quedan en 1,6:1). No se tocó: no era parte de este pedido y es una decisión de diseño anterior. Queda para decidir con el dueño, porque él sí mira los días pasados.
- **Ámbar y naranja.** "Pide una acción" (ámbar) y Por fabricar (naranja) son de la misma familia. Con el color de la etapa fuera del texto ya no se cruzan en la misma ficha, pero el carril de Por fabricar sigue siendo naranja. Si confunde, se le cambia el color a la etapa.
- **Presupuestos, Usuarios, Stock y la Calculadora** siguen con el gris y el verde de antes hasta que les toque su barrido. La diferencia casi no se ve, pero existe.

## Unknown unknowns

- Hábitos armados sobre el color viejo (buscar "lo rojo" en Relación-Contrato para ver quién debe).
