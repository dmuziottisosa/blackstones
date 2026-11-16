# 2026-11-16 — Relación-Contrato: lo administrativo, aparte y enlazable con el tablero

**Estado:** primera versión construida, sin datos reales (se prueba con 3 renglones de prueba).
**Quién decide:** el dueño. Doc operativo: `04-operations/relacion-contrato.md`.

## Qué se decidió

- Relación-Contrato es una página propia, más administrativa: un renglón por contrato, centrado en el total y los pagos.
- El cliente entra cuando dice que contrata y se le coordina la medición.
- **Por defecto el renglón no está enlazado.** Un botón por renglón ("Cargar en Por medir") crea la ficha del tablero copiando solo los campos que el tablero ya tiene.
- Una vez enlazado: el tablero manda en lo operativo, Relación-Contrato en la plata. El renglón muestra la etapa de la obra.
- La seña es un solo dato: se carga en cualquiera de los dos lados y se refleja en el otro.
- N° de presupuesto: debería estar, pero no es bloqueante.
- Todo el equipo ve la plata, por ahora.

- **Crítico:** Relación-Contrato se puede eliminar entera sin que se caiga nada del tablero ni del calendario. La dependencia es de un solo sentido (contratos lee del tablero, nunca al revés).

## Por qué

- No romper lo que ya funciona: datos aparte, y la ficha se crea con los endpoints existentes.
- Un solo dueño por dato evita que la dirección o la seña digan cosas distintas en dos pantallas.

## Knowns

- Los campos del renglón los dictó el dueño el 16/11/2026 (lista en el doc operativo).
- El tablero ya guardaba la seña (monto y moneda) como bloqueante de salida de Por medir.
- Una obra entra al tablero por tres puertas: presupuesto Aprobado, "Cargar obra" y ahora Relación-Contrato. El N° de presupuesto es lo que evita duplicados; sin N° no hay forma automática de detectarlos.

## Known unknowns

- Qué va exactamente en "Contrato" y "Contrato paralelo" (hoy son texto libre, `status: hypothesis`).
- Cómo se registra un pago en pesos contra un total en dólares (¿a qué cotización?).
- Si "Pileta sí/no" de la planilla alcanza o hace falta saber de quién es (se dejó Nuestra / Del cliente / No lleva para que coincida con el tablero).
- ~~Si Vendedor y Procedencia deben ser listas cerradas.~~ Resuelto el 16/11/2026: sí, las dos (lista en el doc operativo). Queda confirmar si "Dioge" es otra persona o "Diego" repetido.
- Si conviene que el presupuesto Aprobado cree solo el renglón del contrato.

## Unknown unknowns

- Dos lugares donde se carga plata (Presupuestos y Relación-Contrato) pueden terminar con totales distintos para la misma obra.
- La contraseña compartida deja la plata a la vista de todo el taller; puede dejar de ser aceptable sin aviso.
