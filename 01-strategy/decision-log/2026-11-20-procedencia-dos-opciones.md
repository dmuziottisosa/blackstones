# Procedencia: dos opciones, y un filtro en Relación-Contrato

**Fecha:** 20/11/2026 · **Decide:** el dueño · **Estado:** vigente

## Qué decidió el dueño

1. "Procedencia: ponemos 2 opciones: Alberdi, Publicidad/Página." Antes eran cinco (Alberdi, Alberdi viejo, Publicación, Publicación/WhatsApp, Publicación/Alberdi; lista del 16/11/2026).
2. "En Relación-Contrato puedo filtrar por lo que es Publicidad/Página y lo que es Alberdi."
3. Se le preguntó qué hacer con los contratos ya cargados con las opciones viejas. Eligió pasarlos a las dos nuevas: "Alberdi viejo" a Alberdi; "Publicación" y "Publicación/WhatsApp" a Publicidad/Página.
4. Se le preguntó aparte por "Publicación/Alberdi", que era las dos cosas a la vez. Eligió Publicidad/Página: la procedencia es de dónde llegó el cliente, no dónde se lo atendió.

## Cómo se hizo

- La lista quedó en dos opciones (`$BS_CONTRATO_PROCEDENCIAS`, `_contratos.php`).
- **No se reescribió ningún contrato.** Un valor de antes se traduce al leerlo (`bs_contrato_procedencia()`), y queda guardado con el nombre nuevo cuando ese contrato se vuelve a guardar. Desde acá no se toca la base de producción; y así, si la decisión cambia, alcanza con cambiar la tabla.
- Relación-Contrato tiene un selector "Procedencia" al lado del de "Etapa". Se combina con los demás filtros y la suma de abajo es la de lo filtrado.

Detalle de uso: `04-operations/relacion-contrato.md`, "Procedencia: dos opciones y un filtro".

## Knowns

- Probado en la copia de prueba: 18 comprobaciones de servidor y 14 de pantalla, más la regresión completa. Las cinco opciones viejas entran y se leen con su nombre nuevo; una procedencia inventada no entra; un contrato guardado antes del cambio se ve bien sin que se toque el archivo.
- Una pantalla que quedó abierta sin recargar y manda un nombre viejo: se guarda con el nuevo.
- La importación de la planilla también traduce los nombres viejos: "Alberdi viejo" entra como Alberdi y "Publicación/Alberdi" como Publicidad/Página (5 comprobaciones; con la versión anterior quedaban sin procedencia y con el texto en la nota).

## Known unknowns

- **Cuántos contratos de producción tienen cada opción vieja.** Desde acá no se ve. Importa sobre todo "Publicación/Alberdi": todos pasan a Publicidad/Página, sin revisar uno por uno.
- **El archivo guardado conserva el nombre viejo** hasta que cada contrato se vuelva a guardar. Lo que lee el archivo crudo (el ZIP del cierre del mes, un respaldo) lo va a mostrar con el nombre de antes.
- **El resumen de arriba no sigue al filtro:** es el del mes entero. La suma de una procedencia está abajo, en el pie. Si el dueño la quiere arriba, es otro cambio.
- No hay filtro por vendedor: no se pidió.

## Unknown unknowns

- Planillas o costumbres del equipo que sigan usando los nombres viejos.
