# Tablero de obras

Dónde está cada obra y qué le falta para avanzar. Vive en `blackstones.com.ar/presupuestos/tablero.php`, pestaña **Tablero** del hub. Lo usa el gerente de operaciones; entra con la misma contraseña del hub.

**Status:** v1 en producción desde 2026-11-14. Decisión: `01-strategy/decision-log/2026-11-14-tablero-de-obras.md`.

## Cómo funciona

- Una obra aparece sola cuando su presupuesto se marca **Aprobado** en Presupuestos. No se cargan obras a mano.
- La tarjeta se identifica con el código del presupuesto: N° de cliente + versión (`0042-1`).
- La tarjeta se **arrastra** de una columna a otra, o se avanza con el botón **Siguiente**. Las dos cosas hacen lo mismo y respetan la misma regla.
- **Regla única:** para avanzar hay que tener todos los tildes de la columna. Hacia atrás se mueve libre.
- En celular se ve una columna a la vez. Para arrastrar: mantener apretada la tarjeta y soltarla sobre la pestaña de destino.

## Las columnas y sus tildes

| Columna | Fecha de la tarjeta | Tildes para salir |
|---|---|---|
| **Por medir** | Medición | Obra lista para medir · Medida tomada · Seña cobrada · Tenemos placa · Bacha en taller |
| **Por fabricar** | (sin fecha: se fabrica en orden de llegada) | Pieza terminada · Controlada |
| **Por instalar** | Instalación | Cliente avisado · Piso y ascensor vistos · Instalada y conforme |
| **Por archivar** | — | Saldo cobrado |
| **Retiro en local** (cuadro aparte, abajo) | Retiro | Seña cobrada · Pieza terminada · Cliente avisado · Retirada · Saldo cobrado |

Qué significa cada tilde que no es obvio:

- **Obra lista para medir:** el cliente confirmó muebles colocados y mesada vieja retirada. Se tilda antes de mandar al medidor.
- **Seña cobrada:** es el bloqueante de salida de Por medir. Pide el monto ($ o USD) que trajo el medidor, o que el cliente señó antes. Sin monto no se tilda.
- **Tenemos placa:** la placa del color está en el taller o reservada con el proveedor.
- **Bacha en taller:** si la obra no lleva bacha, se tilda igual.
- **Piso y ascensor vistos:** se sabe si la pieza sube por ascensor o por escalera ($30.000 por piso si no entra).

## Fechas y avisos

- **Medición / Instalación / Retiro:** un turno por tarjeta. Si la fecha pasó y el tilde que la cumple no está, la tarjeta se marca atrasada en rojo.
- **Entrega estimada:** semana en que cae medición + plazo (granito 15 días, el resto 20). Aparece al tildar "Medida tomada". Es guía: el orden de fabricación lo da la cola.
- **Cola:** dentro de cada columna van primero las que tienen turno (el más próximo arriba) y después por orden de llegada a esa columna.
- **Nota:** un renglón libre por tarjeta (botón "Más"), para lo que frena la obra.

## Retiro en local

Desde "Más" → **Retira en el local**, o arrastrando la tarjeta al cuadro de abajo. Sale de las cuatro columnas y sigue su propio checklist. "Volver a las columnas" la devuelve a donde estaba.

## Archivar

"Archivar obra" pasa el presupuesto a **Entregado** en el hub y saca la tarjeta del tablero. Desde el tablero **no se borra nada** (ni las otras versiones del presupuesto). El hub conserva los entregados 180 días (`BS_RETENTION_DAYS`); la copia permanente es la física que arma administración.

Si un presupuesto se devuelve a Activos desde el hub, la tarjeta vuelve al tablero con sus tildes.

## Dónde vive

| Qué | Dónde |
|---|---|
| Página | `site/public_html/presupuestos/tablero.php` |
| Endpoint y definición de etapas/tildes | `site/public_html/calculadora/api/tablero.php` (array `$BS_ETAPAS`: para cambiar un tilde o un título se toca solo ahí) |
| Datos | `bs-data/tablero/obras.json`, aparte de `clientes/`. Si falta, el hub y la calculadora siguen igual |
| Archivar | `state.php` con `conservar_hermanas: true` |

## Lo que la v1 deja afuera a propósito

Accesos por rol (hoy hay una sola contraseña), hojas imprimibles para medidor/cortador/instalador, agrupar cortes por material, etiquetas, métricas de tiempos, fotos por etapa, tarjetas de reclamo, saldos y conversión de moneda (eso lo lleva administración). Están en la propuesta original: `06-knowledge/layer-0-raw/2026-11-14-propuesta-sistema-gestion-kanban.md`.
