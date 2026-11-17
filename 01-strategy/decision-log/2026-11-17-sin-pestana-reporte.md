# 2026-11-17 — Se elimina la pestaña "Presupuestos · Reporte"

**Decide:** el dueño. **Status:** decidido e implementado.

## Decisión

La pestaña "Presupuestos · Reporte" sale del menú de todo el sistema. "Presupuestos · Activos" pasa a llamarse "Presupuestos". Un enlace viejo a `?tab=reporte` cae en la lista de presupuestos.

## Por qué

- El reporte sumaba ventas a partir del total de los presupuestos. Desde Relación-Contrato la plata (monto firmado, seña, pagos, cotización) vive en los contratos. Dos lugares con números de plata distintos es peor que uno.
- Las fichas cargadas a mano ya no son presupuestos (17/11/2026), así que el reporte tampoco las veía bien.
- Una pestaña menos en el menú.

## Qué cambió

- Menú sin "Reporte" en Presupuestos, Tablero, Calendario, Relación-Contrato, Stock y la calculadora.
- "Devolver una obra archivada" vivía solo en esa pestaña. Pasó al tablero: botón "Archivadas · N" → "Devolver al tablero" (`tablero.php?archivadas=1`). → `04-operations/tablero-obras.md`

## Qué se perdió con la pestaña

- La vista de ventas del mes y el corte publicidad / local.
- "+ Agregar manual" (cargar una entrega histórica que no pasó por la calculadora).
- El aviso del reporte mensual cerrado.
- Los entregados históricos que nunca pasaron por el tablero ya no se ven en ninguna pantalla. Los archivos siguen en `bs-data` hasta que venzan a los 180 días.

## Knowns

- El código de la vista quedó en `presupuestos/index.php` sin forma de llegar a él. `add-manual.php` y `monthly-report.php` siguen en el servidor.
- La tarea automática de las 2 (`monthly-report.php`) sigue corriendo y escribe archivos que nadie lee.
- Probado: ninguna página muestra "Reporte" en el menú; archivar → "Archivadas · 1"; devolver restaura etapa, tildes y seña; regresión del tablero, calendario, contratos y hub sin errores.

## Known unknowns

- Si el dueño va a querer números de ventas del mes. Si los quiere, salen de Relación-Contrato (firmado, cobrado, saldo por mes y por vendedor), no de presupuestos. No está construido.
- Si se borra la tarea de las 2 del panel de Hostinger y el código muerto del reporte. Se dejó para no tocar el servidor sin pedido.

## Unknown unknowns

- Alguien del equipo que usara el reporte para algo que no sabemos (comisiones, cierre de mes).
