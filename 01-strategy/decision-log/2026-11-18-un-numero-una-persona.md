# Un número, una persona: el cruce de dos clientes y cómo se cerró

**Fecha:** 18/11/2026 · **Decide:** el dueño ("hubo un traspapeleo que no puede pasar nunca más") · **Estado:** vigente

## Qué pasó

Una ficha del tablero que estaba en Por instalar apareció con el nombre, el teléfono y la dirección de otro cliente, que recién estaba cotizando. Todo lo demás (material, bacha, tildes, turno, seña) seguía siendo de la obra original. El dueño lo vio como "cargué uno en Por medir y apareció en Por instalar".

## Knowns

- **La ficha original era una obra cargada a mano antes del 17/11/2026.** Esas fichas tienen por dentro un número de la misma serie que los presupuestos (las nuevas van de 90001 en adelante), y ese número no se ve en ninguna pantalla: la ficha dice "Sin N°" o el N° de su contrato.
- **La calculadora podía guardar encima de un número ajeno.** Cuando el N° del presupuesto no era "el propuesto recién" (por ejemplo, al recuperar un borrador, que guarda el número hasta 24 horas), `save.php` lo trataba como "agregar una versión a ese cliente" y **pisaba nombre, teléfono y dirección** con los del presupuesto nuevo, sin comparar si era la misma persona.
- Reproducido en la copia de prueba el 18/11/2026: con la versión anterior, guardar un presupuesto con el número interno de una ficha a mano le cambia el nombre, el teléfono y la dirección a esa ficha, que sigue en su etapa. Es exactamente lo que se vio.
- El tablero exportado ese día muestra la ficha con el número interno viejo, los datos de contacto del cliente nuevo y todo lo demás de la obra original.

## Qué se cambió

1. **`save.php`: un N° que ya es de otra persona no se pisa nunca.** Solo se agrega una versión si es la misma persona (mismo teléfono; si a alguno le falta el teléfono, mismo nombre). Si no, el presupuesto se guarda con un N° nuevo y la calculadora avisa de quién era el otro. Una ficha cargada a mano nunca recibe un presupuesto por esta vía. Cada cruce evitado queda anotado en `bs-data/registro/_cruces-evitados.log`.
2. **La calculadora recuerda si el N° de un borrador era el propuesto**, así un borrador recuperado sigue siendo "un presupuesto nuevo".
3. **Una obra entra al tablero solo por dos puertas:** "Cargar obra" en el tablero y "Cargar en Por medir" en Relación-Contrato (las dos pasan por `tablero-cargar.php`). `state.php` ya no aprueba un presupuesto por ningún camino; solo devuelve una archivada.
4. **Toda obra cargada nace en Por medir, vacía.** Si en el tablero quedó un registro viejo con esa clave, no se hereda. Lo que está en la papelera se recupera desde la Papelera, no cargándolo de nuevo.
5. **`tablero-cargar.php` no acepta el número interno de una ficha a mano** como si fuera un presupuesto.
6. **"Cargar en Por medir" carga o avisa; ya no enlaza sola.** Antes, si el presupuesto "ya estaba en el tablero", buscaba una ficha con ese número y la enlazaba sin preguntar. Enlazar con una ficha que ya existe es el botón "Enlazar con el tablero", que muestra de quién es antes de confirmar.

## Known unknowns

- **El paso exacto que disparó el cruce** en producción (`status: hypothesis`): el camino de la calculadora es el único encontrado que produce ese resultado, y se reprodujo, pero no se puede ver la base de producción. Se confirma si en Presupuestos aparece una versión del cliente nuevo colgada del número interno de la ficha original.
- Si hay otras fichas a mano viejas a las que ya les pasó lo mismo. Se detecta comparando el tablero exportado con la planilla del dueño.

## Unknown unknowns

- Otros lugares donde dos datos de personas distintas comparten una clave. La regla que queda: antes de escribir datos de un cliente sobre un registro que ya existe, comparar que sea la misma persona.
