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

**Corrección del mismo día (dueño):** lo ya cortado **no sube por estar cortado**. Primero va la semana de entrega para todas las fichas; recién dentro de la misma semana lo ya cortado que espera control queda antes que lo que falta. La versión anterior ponía todo lo ya cortado arriba de la columna, y una obra de la semana siguiente podía quedar por encima de una vencida sin cortar.

**Corrección del 27/11/2026 (dueño): el mismo color va junto también con lo ya cortado.** Mandó una captura de Por cortar, semana del 30/11: dos Guidoni Blanco Suggar ya cortadas arriba y una tercera, sin cortar, en el puesto 7, detrás de otros tres colores. "Si son de la misma semana y el mismo material", preguntó, ¿por qué la tercera no se agrupó con las otras dos? Se le explicó la regla de ese momento (lo cortado arriba; el color se junta entre lo que falta, por vencimiento) y contestó: "Todas se entregan esa semana", y preguntó por qué la siguiente no era la tercera. Desde ese día, dentro de una semana el color va entero: primero el color que ya se empezó a cortar, con sus cortadas y la que le falta pegada; después los colores sin empezar, el que vence antes arriba. La que falta de un color empezado es la primera de la cola de esa semana. Lo de arriba no cambia: una ficha no sube de semana por estar cortada.

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

- (27/11) Con dos colores empezados en la misma semana va primero el que tiene la cortada que vence antes. No se le preguntó al taller si prefiere otro criterio.

## Unknown unknowns

- Reordenar la cola cambia cuál es la "PRÓXIMA". Puede chocar con acuerdos de palabra que el taller ya tenía sobre qué cortar primero.
