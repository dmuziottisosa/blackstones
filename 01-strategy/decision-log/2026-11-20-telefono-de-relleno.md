# Un teléfono de relleno no es un teléfono (y un cartel que no explicaba)

**Fecha:** 20/11/2026 · **Decide:** el dueño mostró el caso; el cambio lo eligió quien lo hizo y se revierte en un renglón si no va · **Estado:** vigente

Ajusta la regla del 18/11/2026. → `2026-11-18-un-numero-una-persona.md`

## Qué pasó

Al tocar "Cargar en Por medir" en un contrato, salió: "El presupuesto N° … es de [nombre], no de [el mismo nombre]. Revisá el N° del contrato. No se cargó nada." El dueño preguntó por qué. Después contó qué tenía el presupuesto: "era [la misma persona] pero tenía el número 111111111".

El presupuesto se había guardado con un teléfono de relleno y el contrato tenía el teléfono de verdad.

## Knowns

- **La regla comparaba un relleno como si fuera un teléfono.** Con teléfono en los dos, manda el teléfono (los últimos 8 dígitos). "111111111" tiene 9 dígitos: contaba, era distinto del de verdad, y la regla decía "otra persona".
- **Reproducido en la copia de prueba:** mismo nombre y otro teléfono da ese cartel, palabra por palabra.
- **El cartel mostraba solo los nombres.** Con el mismo nombre de los dos lados no decía nada: no decía que lo distinto era el teléfono ni cuál era.
- **El caso inverso también pasaba, y era peor** (comprobado con la regla publicada): dos personas distintas guardadas las dos con "1111111111" daban "la misma persona", porque tenían "el mismo teléfono". La calculadora podía sumarle a una la versión de la otra: el mismo cruce del 18/11.
- En la batería de pruebas no había ningún teléfono de relleno: por eso no saltó antes.

## Qué se cambió

1. **La regla (`bs_misma_persona`, `_config.php`):** un teléfono cuyos últimos 8 dígitos son todos iguales ("111111111", "0000000000", "11 1111-1111") cuenta como que falta. Entonces decide el nombre, igual que cuando no hay teléfono cargado.
2. **El cartel de "Cargar en Por medir"** cuando el nombre coincide y los dos teléfonos son de verdad y distintos: dice que no coincide el teléfono, muestra los dos uno debajo del otro (el del presupuesto y el del contrato) y dice qué hacer. Primero, revisar el N°; después, cómo dejar el mismo teléfono en los dos (el del presupuesto se cambia en Presupuestos, con el lápiz).
3. **El aviso de la calculadora**, en el mismo caso: "El N° … ya era de [nombre], con otro teléfono (…)".
4. **Relación-Contrato no propone "Enlazar con el tablero"** por un teléfono de relleno: dos obras distintas pueden tener el mismo.

## Qué no se cambió, a propósito

- **Con dos teléfonos de verdad y distintos, sigue frenando aunque el nombre sea el mismo.** Dos personas pueden llamarse igual. Se pensó en un botón "es la misma persona, cargar igual" y se descartó: abre una puerta en la regla, y hay un camino sin puerta (corregir el teléfono que esté mal).
- **El orden del cartel: primero el N°.** Emparejar los teléfonos para que cargue, sin mirar de quién es cada uno, es justo el cruce que la regla evita.
- **La importación de contratos** (`contratos-importar.php`) compara teléfonos por su cuenta y no se tocó: no tiene pruebas en la batería actual.

## Known unknowns

- **Qué otros rellenos usa el equipo.** Solo se contempla el de dígitos todos iguales. "123456789" o "1100000000" siguen contando como teléfonos. Lo que tiene letras o menos de 8 dígitos ("no tiene", "-") ya contaba como sin teléfono.
- **Cuántos presupuestos de producción están guardados con un relleno.** Desde acá no se ve.
- **Con un relleno decide el nombre.** Dos personas distintas con el mismo nombre suelto, una de ellas con relleno, pasan por la misma. Es el riesgo que ya existía cuando falta el teléfono; no es nuevo, pero ahora alcanza a más registros.

## Unknown unknowns

- Otros datos de relleno que el sistema toma por verdaderos (una dirección "x", un nombre "cliente").

## Validación

Probado en la copia de prueba: la regla sola (con y sin relleno, nombres iguales y distintos), "Cargar en Por medir", la calculadora y la pantalla. Falta verlo en producción: después del deploy, un contrato cuyo presupuesto tenga 111111111 tiene que cargar sin cartel.
