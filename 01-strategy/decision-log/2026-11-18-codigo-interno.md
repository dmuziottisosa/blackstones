# El código interno de la ficha: el N° de presupuesto pasa a ser un dato

**Fecha:** 18/11/2026 · **Decide:** el dueño · **Estado:** paso 1 implementado; paso 2 pendiente

## Qué pidió el dueño

"Prefiero que la entidad que enlaza no sea algo tan frágil como un presupuesto, sino más bien un código interno invisible para nosotros. Si yo cambio algo en esa ficha, digamos el N° de presupuesto, no es que se debe traspapelar o quedar huérfano nada, sino que a ambos se les cambia el N°. Al final esa ficha es una representación de un cliente."

## Cómo era

Había dos clases de fichas:

- **Cargadas sin N°:** tenían un código interno (serie 90001+) y mostraban el N° de su contrato. Cambiar el N° no movía nada.
- **Cargadas con N° de presupuesto:** su identidad era ese número. La ficha vivía dentro del archivo del presupuesto, y cambiarle el N° era una mudanza al archivo de otro presupuesto, del que tomaba el nombre. De ahí salió el cruce de datos entre dos clientes, y contratos que quedaban apuntando a una ficha que "no existe en el tablero".

## Paso 1 (hecho)

- Toda obra nueva nace con código interno, tenga o no N° su contrato.
- El N° es un dato que comparten ficha y contrato: se cambia en uno y cambia en el otro. No hay mudanza.
- El presupuesto de la calculadora no se toca. En Presupuestos sigue figurando "En el tablero · etapa", buscado por N°.
- No se carga dos veces una obra con el mismo N°.

## Paso 2 (pendiente de decisión)

Convertir las fichas vivas que nacieron de un N° de presupuesto al mismo formato: sacarlas del archivo del presupuesto, darles código interno y dejarles su N° como dato, conservando tildes, fechas, fotos, historial, seña y contrato. Es una conversión de una sola vez sobre datos de producción: se hace con respaldo previo y probada antes con una copia de "Exportar tablero".

## Knowns

- Con el paso 1, una ficha nueva no puede quedar huérfana ni tomar el nombre de otro cliente por cambiarle el N° (probado: el contrato sigue enlazado con la misma ficha y los dos muestran el N° nuevo).
- El presupuesto no cambia de estado ni recibe datos del contrato (probado).

## Known unknowns

- Cuántas fichas vivas de producción son de la clase vieja. Sin el paso 2 conviven las dos clases hasta que las viejas se archiven.
- Si alguien necesita, desde la ficha, abrir el detalle del presupuesto (ítems, medidas). Hoy la ficha con código interno no lo trae: lo que queda del presupuesto es el adjunto del contrato.
- El presupuesto de una obra viva ahora vence a los 180 días como cualquier otro. Antes quedaba guardado, fuera de la pestaña.

## Unknown unknowns

- Usos del N° como identidad que no estén a la vista: una planilla externa, un hábito del equipo de buscar la ficha por "2086-1".
