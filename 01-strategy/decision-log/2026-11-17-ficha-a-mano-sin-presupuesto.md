---
fecha: 2026-11-17
tema: Cargar una obra a mano no crea un presupuesto; desenlazar y enlace roto
status: decidido (dueño)
---

# Una ficha a mano no es un presupuesto

## Qué se decidió

1. **Cargar una obra a mano en el tablero no crea un presupuesto.** No aparece en la pestaña Presupuestos, no se lleva un N° de la calculadora y dice "Sin N°" hasta que se le ponga el de su presupuesto.
2. **"Desenlazar"** en los puntitos de cada contrato. No borra nada.
3. **Un contrato enlazado con una ficha que no está lo dice claro:** "Esta ficha no existe en el tablero", distinto de Archivada y de En la papelera.
4. De paso: "Enlazar con el tablero" reconoce la obra también por teléfono, y la ficha abierta del tablero muestra "Con contrato".

## Por qué

El dueño preguntó cuáles eran las vías normales para enlazar. Al explicarle que la vía "poner el N° del presupuesto" no encontraba sus fichas, salió la causa: cada obra cargada a mano creaba por dentro un presupuesto aprobado en $ 0, con el siguiente número de la calculadora. En palabras del dueño: "si se carga a mano no debe crearse un presupuesto. Ahí está el quid de la cuestión".

Consecuencias de lo anterior, verificadas el 17/11:

- En el tablero exportado el 16/11, de 38 fichas solo 3 tenían el mismo número que su contrato en la planilla del equipo.
- Cargar una obra a mano movía el próximo N° de la calculadora (probado en local: de 0084 a 0085).
- La obra aparecía en Presupuestos como un presupuesto "aprobado" que nadie había hecho.

Sobre desenlazar, el dueño propuso que alcanzara con sacar el N° del contrato. No se hizo así: el enlace es un dato aparte, y casi todos los contratos enlazados no coinciden en número con su ficha o no tienen N°; atarlo al número los dejaría sueltos, y corregir un número mal tipeado rompería el enlace sin avisar.

## Knowns

- La ficha a mano sigue necesitando un número para existir (todo el sistema guarda las obras por número). Sale de una serie aparte, de 90001 en adelante, que no se muestra.
- La numeración de presupuestos ignora esa serie. Probado: con tres fichas a mano cargadas, la calculadora guardó dos presupuestos nuevos como 0086 y 0087, y el siguiente quedó en 0088.
- Las fichas a mano que ya existían no cambian de número: como están marcadas `manual`, también dicen "Sin N°" y salen de Presupuestos.
- Siguen andando "Cargar obra" con N°, y pasar una ficha a mano a su presupuesto real desde "Editar obra".
- Probado con la regresión del tablero, el calendario, contratos, papelera, y el tablero con Relación-Contrato borrada.

## Known unknowns

- **Cuáles de las fichas de hoy están marcadas a mano.** Desde acá no se ve. Las que alguien ya pasó a su presupuesto real con "Editar obra" siguen mostrando su N°.
- Si el equipo usaba el número de las fichas a mano para nombrar las obras ("la 2269"). Ese número deja de verse en el tablero; queda en la última columna de "Exportar tablero".
- Una obra a mano archivada sigue entrando al reporte mensual como una entrega en $ 0. Qué fuente manda la plata del reporte sigue sin decidir.
- Cambiarle el N° a una ficha enlazada deja el contrato apuntando al número viejo. Hoy se ve ("No existe en el tablero") y se resuelve desenlazando y volviendo a enlazar; no se sigue solo.

## Unknown unknowns

- Algún archivo o costumbre que dé por hecho que todo lo que está en el tablero figura en Presupuestos.
- Dos fichas con el mismo teléfono (una pareja, un arquitecto con dos obras): por teléfono no se ofrece enlazar, y hay que usar "Crear contrato" desde la ficha.

## Dónde quedó

`04-operations/tablero-obras.md` (Cargar obra) y `04-operations/relacion-contrato.md` (formas de enlazar, desenlazar, enlace roto).
