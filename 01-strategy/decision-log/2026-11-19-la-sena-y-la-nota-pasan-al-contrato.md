---
fecha: 2026-11-19
tema: La seña y la nota de la ficha pasan solas al contrato
status: decidido (dueño)
---

# La seña y la nota de la ficha pasan al contrato

## Qué se decidió

El dueño, 19/11/2026, mirando un contrato que avisaba "La seña no coincide con el tablero": "Si puse la seña en el tablero tiene que actualizarse en Relación-Contrato. Igual las notas, que haga un append a las notas de Relación-Contrato."

Cambia la regla del 17/11 ("no vamos a poner ninguna sobrescritura", `2026-11-17-sena-sin-pisar-aprobado-y-plazo.md`). Esa regla nació de un error concreto y ese error sigue cubierto; ver "Por qué así".

1. **Cada seña que se carga o se cambia en el tablero viaja una vez a su contrato.**
2. **La nota de la ficha queda como un renglón al final de la nota del contrato** ("Tablero: ...").

## Qué pasa y qué no

| Situación | Qué hace |
|---|---|
| El contrato no tiene seña ni pagos | Toma la de la ficha |
| El contrato ya tomó una seña de la ficha y en el tablero la cambian | Toma la nueva; si es la misma moneda, conserva la cotización que tenía |
| Primera vez que se miran y los dos tienen una seña distinta | No se toca. Sigue el aviso |
| El contrato no tiene seña pero sí pagos | No se toca: la seña puede ser uno de esos pagos y se contaría dos veces |
| La seña entró en una moneda y el contrato solo tiene total en la otra | No se copia: hace falta la cotización y esa la pone una persona. El aviso lo dice |
| Se destilda la seña en el tablero | La del contrato queda |
| Se corrige la seña a mano en el contrato | Queda, hasta que alguien vuelva a cambiarla en el tablero |
| Se edita la nota de la ficha | Se reemplaza su renglón en el contrato (no se apila una copia por cada cambio) |
| Se borra la nota de la ficha | Su renglón se va del contrato (dueño, 25/11/2026: "si borro, se debe borrar su append correspondiente"). Hasta ese día quedaba |
| Se borra a mano el renglón en el contrato | No vuelve solo |

Del contrato al tablero no cambió nada: la seña pasa solo si el tablero no tiene ninguna. *(Actualizado el 25/11/2026: desde ese día la nota del contrato se ve en la ficha del tablero. → `2026-11-25-las-notas-se-ven-en-los-dos-lados.md`)*

## Por qué así

- El 16/11 la seña del tablero reemplazaba a la del contrato **cada vez que se abría la página**, sin importar la moneda. Dos contratos quedaron "debe pesos y tiene dólares a favor". Por eso ahora: pasa una vez por cada carga (no en cada apertura), nunca cruza de moneda sin cotización y nunca se suma arriba de pagos ya cargados.
- La seña solo se puede cargar o cambiar en Por medir (el tilde es de esa etapa). Después, la plata se lleva en el contrato.
- La nota de la ficha se guarda sola mientras se escribe. Apilar una copia por cada guardado llenaría el contrato de frases a medias; por eso se reemplaza el renglón.

## Cómo está hecho

- Las reglas viven en un solo lugar: `bs_contrato_traer()` en `calculadora/api/contratos.php`. El servidor lee la seña y la nota de la ficha él mismo; del pedido solo llega de qué ficha se trata.
- El tablero avisa al guardar la seña o la nota (`de_ficha`). Relación-Contrato, al abrirse, levanta lo que haya quedado sin pasar (`de_fichas`): lo cargado antes de este cambio, o una nota guardada desde el calendario, que no escribe en contratos.
- Si Relación-Contrato no existe, el aviso del tablero falla en silencio y el tablero sigue igual.

## Knowns

- Probado en local: 32 comprobaciones por la API y 13 por pantalla (cargar la seña y la nota desde la ficha, abrir Relación-Contrato con cosas pendientes, los tres textos del aviso).
- Abrir la lista (GET) no cambia ningún contrato. Pedir dos veces lo mismo no cambia nada.
- En el registro queda "El contrato tomó la seña o la nota de su ficha", sin montos ni texto.

## Known unknowns

- Cuántos contratos reales van a tomar la seña la primera vez que se abra Relación-Contrato. Con el tablero del 16/11, 7 de 38 tenían aviso de seña: los 4 con montos distintos y los 3 con seña solo en el contrato no cambian; cambian los que tengan seña en el tablero y nada cobrado en el contrato. La página dice cuántas pasaron.
- Si el renglón "Tablero: ..." molesta en el export de contratos (va dentro de Notas).

## Unknown unknowns

- Un contrato abierto en otra pantalla mientras llega la nota: al guardar esa pantalla, su texto pisa el renglón recién sumado. No vuelve hasta que la nota de la ficha cambie otra vez.
- Fichas donde la "seña" del tablero era en realidad todo lo cobrado.
