# Las obras archivadas se guardan 1 año

**Fecha:** 17/11/2026 · **Decide:** el dueño · **Estado:** vigente ("por los momentos")

## Decisión

Una obra archivada del tablero se guarda **1 año** desde que se archiva, con sus fotos, planos, tildes e historial. Antes eran 180 días. Los presupuestos de la pestaña Presupuestos siguen en 180 días.

Pasado el año se borra de verdad, sin papelera. El contrato no se borra: queda en Relación-Contrato y avisa que la ficha ya no existe.

## Por qué

Las fotos de bacha, plano y entrega no están en la copia física que arma administración. A los 180 días se perdían, y pueden hacer falta para un reclamo o para mostrar trabajos.

## Knowns

- **Cuánto pesa una foto (medido el 17/11/2026 con fotos reales del sitio, pasadas por el mismo achicado del tablero):** foto de bacha o entrega (1600 px) entre 0,2 y 0,35 MB; plano (2400 px) entre 0,3 y 0,7 MB; miniatura 0,01 MB.
- **Una obra típica** (2 de bacha, 2 planos, 3 de entrega) pesa cerca de **3 MB**. Con el máximo de 18 fotos, unos 8 MB.
- **Un plano en PDF no se achica:** puede pesar hasta 10 MB cada uno.
- El resto de los datos (fichas, contratos, usuarios) es texto y no pesa.
- Está probado con datos envejecidos: a los 200 y 364 días la archivada sigue con sus fotos; a los 366 se borra; un presupuesto suelto se sigue borrando a los 180.

## Known unknowns

- **Cuántas obras se archivan por año** (`status: hypothesis`). Con 300 serían ~0,9 GB guardados; con 500, ~1,5 GB; con 1.000, ~3 GB. Con 180 días era la mitad.
- **Cuánto espacio tiene el plan de Hostinger.** Se mira en el panel. `health.php` dice cuánto pesan las fotos (`fotos_tablero_mb`), para seguirlo con el dato real.
- Si los planos se suben como foto o como PDF. Si la mayoría fuera PDF pesado, la cuenta sube.

## Unknown unknowns

- No hay respaldo diario de `bs-data`. Guardar más tiempo es guardar más cosas en un solo lugar.

## Cómo se revisa

Mirar `fotos_tablero_mb` en `health.php` cada tanto. Si crece más de lo previsto, se baja `BS_ARCHIVADAS_DAYS` en `calculadora/api/_config.php` o se dejan de guardar los PDF pesados.
