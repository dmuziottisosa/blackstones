---
fecha: 2026-11-16
tema: Orden de la cola en Por cortar y Por fabricar, y material tomado del catálogo
status: decidido (dueño)
---

# Cola por semana de entrega y color; material del catálogo de la calculadora

## Qué se decidió

1. En **Por cortar** y **Por fabricar** la cola deja de ir por orden de llegada. Va por **semana de entrega** y, dentro de la semana, **junta las obras del mismo color**.
2. El campo de material de las fichas se **elige del catálogo de la calculadora** (formato "Marca · Color").
3. En Por instalar se suma el tilde **"Dirección confirmada"**, después de "Se avisó al cliente".

## Por qué

- El dueño vio una obra que vencía el 22/11 en el puesto 2 de Por cortar, adelante de una ya vencida. El orden de llegada no mira el vencimiento.
- Cortar seguidas dos placas del mismo color es más eficiente en el taller, y para juntarlas hace falta que el color esté escrito igual.

## Knowns

- El catálogo ya existe y es único: `calc.html` → `/calculadora/api/colors-db.php` (683 colores, 12 marcas). Presupuestos ya lo usa para sugerir material.
- Los materiales de las fichas actuales están escritos a mano con variantes ("Gris mara granito", "SUGGAR", "Negro Brasil - 303"). Probado contra los 28 textos reales del tablero del 16/11: reconoce 22.
- Nombres repetidos entre marcas (Taj Mahal, Calacatta Gold y otros 9): no son la misma placa.

## Known unknowns

- Si el taller quiere juntar por color aunque sean semanas distintas (hoy manda la semana).
- Los 6 textos que no se reconocen ("Artic White", "Titan Black", "Crema Galala", "Perla", "Suprastone Blanco Mate", "Color a definir") quedan agrupados por su texto hasta que alguien elija el color de la lista.
- Acabado (pulido / mate) y espesor no entran en el agrupado: dos "Negro Boreal" con distinto acabado quedan juntas.

## Unknown unknowns

- Reordenar la cola cambia cuál es la "PRÓXIMA". Puede chocar con acuerdos de palabra que el taller ya tenía sobre qué cortar primero.
