# Tablero de obras

Dónde está cada obra y qué le falta para avanzar. Vive en `blackstones.com.ar/presupuestos/tablero.php`, pestaña **Tablero** del hub. Lo usa el gerente de operaciones; entra con la misma contraseña del hub.

**Status:** v1 en producción desde 2026-11-14. Decisión: `01-strategy/decision-log/2026-11-14-tablero-de-obras.md`.

## Cómo funciona

- Una obra aparece sola cuando su presupuesto se marca **Aprobado** en Presupuestos. No se cargan obras a mano.
- La tarjeta se identifica con el código del presupuesto: N° de cliente + versión (`0042-1`).
- La tarjeta se **arrastra** de una columna a otra, o se avanza con el botón **Siguiente**. Las dos cosas hacen lo mismo y respetan la misma regla.
- **Regla única:** para avanzar hay que tener todos los tildes de la columna. Hacia atrás se mueve libre.
- En celular se ve una columna a la vez. Para arrastrar: mantener apretada la tarjeta y soltarla sobre la pestaña de destino.

## Colores

Cada etapa tiene un color fijo, que se repite en la columna, el borde de la tarjeta, la pestaña del celular y el botón que lleva a esa etapa: **Por medir** azul, **Por fabricar** naranja, **Por instalar** violeta, **Por archivar** verde, **Retiro en local** turquesa. El rojo queda reservado para lo que está mal (atrasada, no puede pasar).

Al levantar una tarjeta, las columnas que la reciben se encienden en su color y las que no, se apagan. Sobre una columna, la tarjeta dice "Soltar en …" con el color de destino, o "Falta: …" en rojo. Al soltar, late una vez en el color nuevo; si no podía pasar, sacude y vuelve.

Los colores se cambian en un solo lugar: las clases `.et-*` al principio del CSS de `presupuestos/tablero.php`.

## Las columnas y sus tildes

| Columna | Fecha de la tarjeta | Para salir hace falta |
|---|---|---|
| **Por medir** | Medición | Obra lista para medir · Medidas y planos listos · Trajo la seña · Tenemos la bacha · Tenemos la placa · Piso y ascensor · Instaladores |
| **Por fabricar** | (sin fecha: se fabrica en orden de llegada) | Se cortó bien · Medida y color correctos · Inglete y zócalos · Es la bacha correcta · Bacha hacia el lado correcto |
| **Por instalar** | Instalación (**obligatoria**) | Fecha de instalación · Se avisó al cliente · Instaladores |
| **Por archivar** | — | Saldo cobrado |
| **Retiro en local** (cuadro aparte, abajo) | Retiro | Seña cobrada · Pieza terminada · Se avisó al cliente · Retirada · Saldo cobrado |

Cuatro tildes de Por medir piden un dato y lo dejan escrito en la tarjeta:

- **Trajo la seña:** el monto, en $ o USD. Es el bloqueante de salida de Por medir: la trae el medidor, o el cliente señó antes.
- **Tenemos la bacha:** de quién es. Nuestra (la proveemos), del cliente, o no lleva.
- **Piso y ascensor:** cómo sube la pieza. Planta baja, por ascensor o por escalera con cuántos pisos ($30.000 por piso si no entra en el ascensor).
- **Instaladores:** quiénes van, uno o varios de la lista (Negro, Espinola, Cesar, David, Jostin). Se elige al medir y se puede cambiar en Por instalar tocando el mismo tilde. La lista se edita en `$BS_INSTALADORES`, en `calculadora/api/tablero.php`.

Esos datos quedan a la vista en las etapas siguientes ("Bacha del cliente · Sube por escalera, 3 pisos · Instalan: Negro, David").

Los demás:

- **Obra lista para medir:** el cliente confirmó muebles colocados y mesada vieja retirada. Se tilda antes de mandar al medidor.
- **Medidas y planos listos:** desde ese día se cuenta el plazo de entrega.
- **Tenemos la placa:** la placa del color está en el taller o reservada con el proveedor.
- **Por fabricar** es el control de la pieza antes de que salga del taller. Si la obra no lleva bacha, los dos tildes de bacha se tildan igual.
- **Por instalar** no tiene tilde de "instalada": pasar la tarjeta a Por archivar es decir que se instaló.

## Fechas y avisos

- **Medición / Instalación / Retiro:** un turno por tarjeta. Si la fecha pasó y la obra sigue sin cumplirla, la tarjeta se marca atrasada en rojo. La fecha de instalación además es obligatoria para salir de Por instalar.
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
