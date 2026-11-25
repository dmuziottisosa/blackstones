---
fecha: 2026-11-25
tema: Las notas del contrato y de la ficha se ven en los dos lados
status: decidido (dueño)
---

# Las notas se ven en los dos lados

## Qué se decidió

El dueño, 25/11/2026: "Debería poder ver las notas que pongo en Relación-Contrato, en tablero, y viceversa, como append por supuesto. Si borro en Relación-Contrato se debe borrar su append correspondiente. Igual append no es lo correcto porque hay un solo box de notas, pero ya sabés, es una forma mía de decir."

Hasta ese día el camino era de una sola mano: la nota de la ficha llegaba al contrato como el renglón "Tablero: ..." (`2026-11-19-la-sena-y-la-nota-pasan-al-contrato.md`), y lo que se escribía en el contrato no se veía en el tablero.

1. **La nota del contrato se ve desde su ficha del tablero.** Ahí solo se lee; se escribe en Relación-Contrato. *(Primero se mostró en la ficha, debajo de la nota de la ficha y empezada con "Contrato:". Horas después el dueño la pasó, con la de la ficha, a "Ver notas": ver "Más tarde ese día".)*
2. **Borrar una nota borra su copia del otro lado.** Si se borra la del contrato, deja de verse en la ficha. Si se borra la de la ficha, su renglón "Tablero: ..." se va del contrato. Esto último cambia la regla del 19/11, que lo dejaba.
3. **Cada lado sigue teniendo su propia nota.** No pasa a haber una sola nota compartida: el dueño pidió que se sumen, no que se pisen.

## Qué pasa y qué no

| Situación | Qué hace |
|---|---|
| Se escribe o se cambia la nota del contrato | "Ver notas" de esa ficha la muestra, con sus renglones. El tablero se pone al día solo, en un minuto como mucho |
| Se borra la nota del contrato | Desaparece de "Ver notas". La nota de la ficha queda |
| Se escribe o se cambia la nota de la ficha | Como desde el 19/11: llega al contrato como el renglón "Tablero: ..." y, si cambia, se reemplaza ese renglón |
| Se borra la nota de la ficha | Su renglón "Tablero: ..." se va del contrato. El resto de la nota del contrato queda letra por letra |
| En el contrato alguien cambió a mano el renglón "Tablero: ..." | Dejó de ser la copia: borrar la nota de la ficha no lo toca |
| En el contrato alguien borró a mano el renglón "Tablero: ..." | No vuelve solo (como antes) |
| El contrato tiene un renglón que dice lo mismo que la nota de la ficha | La ficha no lo repite |
| La nota del contrato es solo el renglón "Tablero: ..." | En la ficha no aparece nada del contrato: lo que vino del tablero no vuelve al tablero |
| El contrato tiene la nota que pone sola la importación ("Creado desde la ficha del tablero: no estaba en la planilla. Falta el total.") | Ese renglón no se muestra en la ficha. Lo que alguien haya escrito debajo, sí |
| Ficha, cerrada o abierta | No muestra ninguna nota. Si tiene alguna (la suya o la de su contrato), los tres puntitos llevan un punto |
| Rol Planos y bacha | No ve la nota del contrato. La de la ficha la lee en "Ver notas", sin poder cambiarla |
| El contrato se desenlaza | La ficha deja de mostrar su nota. El renglón "Tablero: ..." queda en el contrato, como texto |

## Más tarde ese día: las notas pasan a los tres puntitos

El dueño, al ver las fichas con las dos notas a la vista: "Para ser más eficientes y más modernos: que las notas en tablero se puedan ver al dar en los 3 puntitos de la ficha, ver notas. Así no saturamos la ficha o tenemos que darle más y buscar la nota abajo."

1. **"Ver notas" es lo primero del menú de los tres puntitos.** Abre una ventana con la nota de la ficha, que ahí mismo se escribe, se corrige o se borra ("Guardar", o Enter), y la del contrato, que se lee, con el link al contrato para cambiarla. Sin notas, el renglón dice "Agregar nota".
2. **La ficha deja de mostrar las notas**, cerrada y abierta. La cerrada queda en tres renglones como mucho.
3. **Un punto en los tres puntitos avisa que hay algo para leer.** Lo puso quien lo hizo, no lo pidió el dueño: sin esa marca habría que abrir el menú de cada ficha para saber si tiene nota.
4. **El campo "Nota" de "Más" sigue estando.** El dueño no pidió sacarlo; lo confirma él.
5. **En la ventana de la ficha que abre el calendario las notas siguen a la vista**, la de la ficha y debajo la del contrato: esa ventana no tiene tres puntitos.

Qué se pierde y se sabe: ya no se lee de un vistazo qué dice la nota de cada ficha ("Entrega: martes"). El buscador las sigue encontrando.

## Por qué así

- **La nota del contrato no se copia en la ficha: se lee del contrato cada vez.** Así "si borro, se borra su append" sale solo y no puede quedar una copia vieja. Una copia guardada en la ficha habría que mantenerla al día en cada cambio, y la nota de la ficha es un renglón de 300 letras, mientras que la del contrato tiene hasta 4.000 y varios renglones.
- **En la ficha no se muestran los renglones "Tablero: ..." del contrato.** Vinieron de esa misma ficha: mostrarlos sería leer la misma nota dos veces. Vale para todos los que empiecen así, porque en producción puede haber renglones de notas que se borraron en la ficha antes de este cambio (hasta hoy quedaban) y volverían a aparecer en el tablero.
- **El renglón de la importación tampoco.** Los contratos que se crearon desde su ficha nacen con "Creado desde la ficha del tablero: ...". No lo escribió nadie y la planilla de Relación-Contrato ya no lo muestra; en el tablero habría aparecido en todas esas fichas a la vez.
- **Planos y bacha no la ve** porque la nota del contrato es texto libre de la venta y ese rol no entra a Relación-Contrato. El servidor no se la manda; no es que la pantalla la esconda.
- **El buscador del tablero también busca en la nota del contrato.** Si se lee en la ficha, tiene que poder encontrarse.

## Cómo está hecho

- `bs_contrato_nota_propia()` en `calculadora/api/contratos.php`: la nota del contrato sin los renglones que empiezan con "Tablero:" ni con "Creado desde la ficha del tablero". No se guarda en ningún lado.
- `GET contratos.php?enlaces=1` ya le decía al tablero qué fichas tienen contrato; ahora trae además `notas` (ficha → texto), solo de los contratos que tienen algo escrito. Pide el permiso de siempre (`contratos`). A quien no ve la plata se le tapan los montos, igual que en la lista.
- `presupuestos/tablero.php`: `NOTAS_C` se llena en `cargarEnlaces()`; `notaCto()` arma lo que se muestra; `verNotas()` es la ventana y `notaPoner()` guarda la nota de la ficha, la misma función que usa el campo de "Más". El punto de los tres puntitos es `.c-menu.nt`. Las citas `.c-nota` y `.c-notac` quedan solo en la ventana que abre el calendario (`?solo=`).
- Borrar la nota de la ficha: `bs_contrato_traer()` saca el renglón que diga exactamente "Tablero: <lo que decía>". El tablero avisa al guardar (`de_ficha`), y lo que no llegue a avisarse lo levanta Relación-Contrato al abrirse (`de_fichas`), como antes.
- Si Relación-Contrato no existe o no contesta, el tablero sigue igual y muestra solo la nota de cada ficha (probado).
- El calendario no lee nada de contratos, como siempre. La ventana de la ficha que abre el calendario es la del tablero, así que ahí sí se ve.

## Knowns

- Probado en local: 40 comprobaciones por la API (`rcnotas.py`) y 56 en pantalla (`notacto.js`, rehecha para "Ver notas"): computadora, tablet y celular, claro y oscuro, los tres roles.
- Contraste, medido: los rótulos de la ventana, 8,5:1 en claro y 9,1:1 en oscuro; las notas, "Ver notas" y el punto, 17,7:1 y 16,2:1. No se sumó ningún color.
- Pedir las notas no escribe nada: ni en los contratos ni en el tablero.
- La prueba vieja de la seña y la nota (32 comprobaciones) se actualizó en los dos renglones que decían "borrar la nota de la ficha no borra el renglón".

## Known unknowns

- Cuántos contratos reales tienen hoy un renglón "Tablero: ..." de una nota que ya se borró en la ficha. Esos quedan como están: no se puede saber cuáles son sin ver la base, y no se borra texto de un contrato a ciegas. Se sacan a mano desde el contrato.
- Si al dueño le sirve que Planos y bacha también la vea (con los montos tapados). Hoy no la ve.
- Si el punto de los tres puntitos alcanza para que nadie se pierda una nota, ahora que no se leen en la ficha. Con los datos reales casi todas las fichas tienen nota (las de la carga inicial), así que casi todas lo van a llevar.
- Si el campo "Nota" de "Más" se saca, ahora que hay un lugar mejor.

## Unknown unknowns

- Una nota escrita en la ventana y no guardada: "Cancelar" y Escape la descartan, como en las demás ventanas del tablero; tocar afuera no cierra si hay algo escrito. El campo de "Más" se guarda solo; la ventana no.
- El pedido de enlaces viaja una vez por minuto por cada tablero abierto y ahora lleva las notas de todos los contratos enlazados, también los de obras ya archivadas. Con cientos de contratos puede pesar; si pasa, se filtra a las fichas vivas.
