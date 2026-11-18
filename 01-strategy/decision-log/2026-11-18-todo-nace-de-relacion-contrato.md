# Todo nace de Relación-Contrato

**Fecha:** 18/11/2026 · **Decide:** el dueño ("esa es mi decisión final") · **Estado:** implementado

## Qué se decidió

1. **En el tablero ya no se carga ninguna obra.** Se sacó "+ Cargar obra". Una obra entra solo con "Cargar en Por medir" desde su contrato, en Relación-Contrato.
2. **El tablero avisa si hay fichas sin contrato** ("N sin contrato", arriba). Tocarlo las deja solas a la vista. Debería ser raro.
3. **El Operador trabaja en Relación-Contrato:** crea y edita contratos, seña, cobros posteriores, adjunta el presupuesto, borra contratos y ve la papelera. Sigue sin ver el resumen de plata del mes.
4. **Un contrato sin enlazar dice "No existe en el tablero".**

En palabras del dueño: "Así mantenemos el ciclo de vida enlazado, y no hay fichas dando vueltas o manuales y linkeadas coexistiendo".

## Por qué

- Al empezar con el sistema se cargó todo directo en el tablero, porque Relación-Contrato todavía no existía. Quedaron fichas a mano, fichas de presupuesto y contratos cargados después, conviviendo.
- De esa convivencia salieron los dos problemas de esta semana: una ficha con el nombre de una persona y los datos de otra (ver `2026-11-18-un-numero-una-persona.md`), y una misma clienta cargada dos veces, una vez a mano desde el cronograma y otra con su N°.
- Con una sola puerta, cada ficha tiene su contrato desde el primer día.

## Knowns

- El servidor lo exige: `tablero-cargar.php` no carga nada sin el id de un contrato que exista, sea quien sea el usuario (probado: ni el administrador).
- Si el N° del contrato es el presupuesto de otra persona, no se carga y se avisa (probado).
- Las fichas que ya están sin contrato **no se tocan ni se borran**: quedan avisadas en el tablero. Se regularizan desde Relación-Contrato ("Obras sin contrato · Verlas" → crear o enlazar su contrato).

## Known unknowns

- Cuántas fichas sin contrato hay hoy en producción (desde acá no se ve). El aviso del tablero lo dice.
- Si el Operador necesita ver el resumen del mes para trabajar. Hoy no lo ve.
- `tablero-importar.php` (herramienta de administrador, de una sola vez) sigue creando fichas a mano desde una planilla. Quedó como está; si no se va a usar más, conviene sacarlo.

## Unknown unknowns

- Obras que por algún motivo operativo necesiten entrar al tablero antes de que exista el contrato (una medición de urgencia, por ejemplo). Hoy el camino es crear el contrato con lo mínimo (nombre y teléfono) y cargarla desde ahí.

## Qué cambia respecto de reglas anteriores

- "Una obra entra al tablero solo por dos puertas" pasa a una sola.
- "Relación-Contrato se tiene que poder borrar entera sin que se caiga nada": sigue siendo cierto para todo menos para cargar obras nuevas.
- "Operador: Relación-Contrato solo la mira" deja de valer.
