---
fecha: 2026-11-19 (cerrado el 20/11/2026)
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
| Barra verde y la palabra "Enlazado" en cada renglón de contratos (18/11) | La etapa (punto de color y nombre) es el link a la ficha. Adentro del contrato sigue "Enlazado" | Que haya etapa ya dice que está enlazado; se marcan las que no |
| Montos con centavos en la planilla | Sin centavos; el exacto, al abrir el contrato | Una columna de números se compara mejor si todos tienen la misma forma |

## Knowns

- Probado en local, en computadora (1180, 1440 y 1880 px), tablet y celular, claro y oscuro: `diseno.js` (31 comprobaciones sobre las tres pantallas), la medición de contraste y la regresión completa del tablero, contratos, calendario y usuarios.
- No cambió ningún dato ni ninguna regla del servidor: son las páginas (`tablero.php`, `relacion-contrato.php`, `calendario.php`, y los colores de `reportes.php`).
- Nada de lo que se sacó de la ficha cerrada se perdió: está al abrirla o en el aviso de la columna.
- Contraste, medido: en el tablero, Relación-Contrato y Control todo el texto llega a 4,5:1. La única excepción es a propósito: la raya de una celda vacía en contratos (3:1), que es un relleno y no un dato.

## Known unknowns

- Si en el taller alguien miraba el contador "3 de 8" o el "Sin entregar" de cada ficha como su lista de trabajo. Los filtros "Necesitan continuar" y "Con el cortador" siguen.
- Cómo se lee con el tablero real cargado (más fichas con notas largas que en la prueba).
- Safari de iPad: probado en Chromium con pantalla táctil emulada.
- **El calendario apaga demasiado lo que ya pasó.** La medición encontró ahí 22 textos por debajo de 4,5:1, casi todos en días pasados, días de otro mes y obras ya hechas, que se apagan a propósito (algunos quedan en 1,6:1). No se tocó: no era parte de este pedido y es una decisión de diseño anterior. Queda para decidir con el dueño, porque él sí mira los días pasados.
- **Ámbar y naranja.** "Pide una acción" (ámbar) y Por fabricar (naranja) son de la misma familia. Con el color de la etapa fuera del texto ya no se cruzan en la misma ficha, pero el carril de Por fabricar sigue siendo naranja. Si confunde, se le cambia el color a la etapa.
- **Presupuestos, Usuarios, Stock y la Calculadora** siguen con el gris y el verde de antes hasta que les toque su barrido. La diferencia casi no se ve, pero existe.

## Unknown unknowns

- Hábitos armados sobre el color viejo (buscar "lo rojo" en Relación-Contrato para ver quién debe).
