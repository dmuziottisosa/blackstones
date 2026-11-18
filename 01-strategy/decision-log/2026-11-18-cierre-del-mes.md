# Respaldo: el cierre del mes

**Fecha:** 18/11/2026 · **Decide:** el dueño · **Estado:** vigente

## Decisión

El respaldo del sistema es un **cierre de mes**: el último día de cada mes un administrador baja todo en un archivo y lo guarda dos veces, una copia impresa y una digital. Se hace desde la pestaña Reportes, que es solo para administradores y se puede borrar entera sin que nada se rompa. → `04-operations/reportes.md`

## Por qué así

El dueño lo quiere como un acto explícito y propio ("descargar todo y guardar en físico; descargar todo y tenerlo en digital"), con fecha fija, y no como algo automático que nadie mira.

## Knowns

- El ZIP trae las fichas, los contratos, los presupuestos adjuntos, las fotos que se tilden, una hoja para imprimir por obra y los datos del sistema tal cual. Probado: 22 comprobaciones sobre el contenido del archivo.
- Solo funciona el último día del mes; el resto de los días el servidor lo rechaza.
- No trae el archivo de usuarios.

## Known unknowns

- **Si alcanza con una vez por mes** (`status: hypothesis`). Un error que pise datos a mitad de mes no se recupera con el cierre del mes anterior. Se propuso sumar una copia automática diaria, de 30 días, solo de datos; quedó conversado y sin decidir.
- **Qué pasa si el último día nadie lo baja** (fin de semana, feriado). Hoy no se puede bajar después; habría que esperar al mes siguiente.
- Cuánto tarda y cuánto pesa el archivo cuando haya muchas fotos (unos 3 MB por obra). Con 40 obras vivas son unos 120 MB.

## Unknown unknowns

- Los límites del servidor de Hostinger para armar un archivo grande en un solo pedido.
