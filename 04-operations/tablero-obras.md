# Tablero de obras

Dónde está cada obra y qué le falta para avanzar. Vive en `blackstones.com.ar/presupuestos/tablero.php`, pestaña **Tablero** del hub. Lo usa el gerente de operaciones; entra con la misma contraseña del hub.

**Status:** v1 en producción desde 2026-11-14. Decisión: `01-strategy/decision-log/2026-11-14-tablero-de-obras.md`.

## Cómo funciona

- Una obra aparece sola cuando su presupuesto se marca **Aprobado** en Presupuestos.
- La columna Por medir tiene el botón **+ Cargar obra**, con dos usos:
  - **Con N° de presupuesto** (`0042`, o `0042-2` si tiene versiones): busca ese presupuesto de la calculadora y lo aprueba. La ficha queda atada al presupuesto real.
  - **Sin número** (cliente, teléfono, dirección, material, trabajo): para obras que no pasaron por la calculadora. Crea un presupuesto aprobado en el hub, con importe en 0: administración lo completa desde Presupuestos.
- En Por medir hay dos filtros, **Sin plano** y **Sin foto de bacha**, con la cantidad de fichas a las que les falta cada uno. Las obras que no llevan bacha no cuentan como faltantes.
- **Buscador** arriba del tablero: por cliente, N°, material, zona, teléfono, dirección o instalador. En celular salta solo a la pestaña donde está la obra.
- La tarjeta se identifica con el código del presupuesto: N° de cliente + versión (`0042-1`).
- La tarjeta se **arrastra** de una columna a otra, o se avanza con el botón **Siguiente**. Las dos cosas hacen lo mismo y respetan la misma regla.
- **Regla única:** para avanzar hay que tener todos los tildes de la columna en la que está la tarjeta. Hacia atrás se mueve libre.
- En celular se ve una columna a la vez. Para arrastrar: mantener apretada la tarjeta y soltarla sobre la pestaña de destino.

## Colores

Cada etapa tiene un color fijo, que se repite en la columna, el borde de la tarjeta, la pestaña del celular y el botón que lleva a esa etapa: **Por medir** azul, **Por cortar** gris grafito, **Por fabricar** naranja, **Por instalar** violeta, **Por archivar** verde, **Retiro en local** turquesa. El rojo queda reservado para lo que está mal (atrasada, no puede pasar).

Al levantar una tarjeta, las columnas que la reciben se encienden en su color y las que no, se apagan. Sobre una columna, la tarjeta dice "Soltar en …" con el color de destino, o "Falta: …" en rojo. Al soltar, late una vez en el color nuevo; si no podía pasar, sacude y vuelve.

Los colores se cambian en un solo lugar: las clases `.et-*` al principio del CSS de `presupuestos/tablero.php`.

## Las columnas y sus tildes

| Columna | Fecha de la tarjeta | Para salir hace falta |
|---|---|---|
| **Por medir** | Medición | Medidor · Obra lista para medir · Medido (botón) · Medidas y planos listos · Trajo la seña · Tenemos la bacha · Tenemos la placa · Piso y ascensor · Instaladores |
| **Por cortar** | (sin fecha: se corta en orden de llegada) | Cortador · Cortado (botón) · Se cortó bien |
| **Por fabricar** | (sin fecha: se fabrica en orden de llegada) | Fabricadores · Medida y color correctos · Inglete y zócalos · Es la bacha correcta · Bacha hacia el lado correcto |
| **Por instalar** | Instalación, con día y hora (**obligatorios**) | Fecha y hora de instalación · Se avisó al cliente · Instaladores |
| **Por archivar** | — | Saldo cobrado |
| **Retiro en local** (cuadro aparte, abajo) | Retiro | Seña cobrada · Pieza terminada · Se avisó al cliente · Retirada · Saldo cobrado |

Cinco tildes de Por medir piden un dato y lo dejan escrito en la tarjeta:

- **Medidor:** quién va a medir, uno o varios de la lista (Cesar, David, Negro, Jose; se edita en `$BS_MEDIDORES`). Después queda escrito quién midió.
- **Trajo la seña:** el monto, en $ o USD. Es el bloqueante de salida de Por medir: la trae el medidor, o el cliente señó antes.
- **Tenemos la bacha:** de quién es. Nuestra (la proveemos), del cliente, o no lleva.
- **Piso y ascensor:** cómo sube la pieza. Planta baja, por ascensor o por escalera con cuántos pisos ($30.000 por piso si no entra en el ascensor).
- **Instaladores:** quiénes van, uno o varios de la lista (Negro, Espinola, Cesar, David, Jostin). Se elige al medir y se puede cambiar en Por instalar tocando el mismo tilde. La lista se edita en `$BS_INSTALADORES`, en `calculadora/api/_tablero.php`.

En Por fabricar hay un quinto tilde con dato: **Fabricadores**, uno o varios de su lista (Negro, Espinola, David, Jostin, Pedro, Jose; se edita en `$BS_FABRICADORES`). En Por instalar queda escrito quién la fabricó.

Esos datos quedan a la vista en las etapas siguientes ("Bacha del cliente · Sube por escalera, 3 pisos · Instalan: Negro, David").

Los demás:

- **Obra lista para medir:** el cliente confirmó muebles colocados y mesada vieja retirada. Se tilda antes de mandar al medidor.
- **Medido:** es un botón al lado de la fecha de medición, no una pastilla. Al tocarlo, donde decía la fecha pasa a decir "Ya medido · día" y la tarjeta baja al final de la columna, bajo "Ya medidas · esperando pasar a fabricar". Así arriba queda la cola real de lo que falta medir, ordenada por fecha de medición y después por orden de llegada. Desde ese día se cuenta el plazo de entrega. Tocar "Ya medido" lo deshace.
- **Medidas y planos listos:** da por hecha la medición (marca Medido si no estaba).
- **Tenemos la placa:** la placa del color está en el taller o reservada con el proveedor.
- **Por cortar:** el cortador (Jose o Johander; se edita en `$BS_CORTADORES`) avisa que terminó con el botón **Cortado**. La ficha no avanza sola: baja a "Ya cortadas · esperando el control del gerente". Pasa a Por fabricar recién cuando el gerente tilda **Se cortó bien** y la mueve.
- **Por fabricar** es el control de la pieza antes de que salga del taller. Si la obra no lleva bacha, los dos tildes de bacha se tildan igual.
- **Por instalar** no tiene tilde de "instalada": pasar la tarjeta a Por archivar es decir que se instaló.

## Foto de bacha y plano

En Por medir cada tarjeta tiene dos botones: **+ Foto bacha** y **+ Plano**. La foto de la bacha también se puede adjuntar desde el cuadro donde se marca de quién es (nuestra, del cliente, no lleva).

- La bacha acepta fotos. El plano acepta fotos o PDF. Hasta 6 archivos de cada uno por obra, 10 MB por archivo.
- Las fotos se achican solas antes de subir (el plano queda más grande, para que se lea la letra chica).
- Las miniaturas quedan en la tarjeta en todas las etapas: el taller ve el plano y la bacha al controlar la pieza.
- Tocar una miniatura la abre; desde ahí se puede ver en grande o borrar (pide confirmar).
- Fuera de Por medir, los botones para sumar aparecen al abrir "Más".
- Adjuntar no es obligatorio para avanzar.

Los archivos se guardan en `bs-data/tablero/adjuntos/`, fuera de la web pública: solo se ven con la sesión del hub. Se borran junto con la obra cuando la purga de 180 días elimina el presupuesto.

Quién hace qué es un acuerdo interno, no un permiso del sistema: una persona carga fotos, planos y tildes; el gerente mueve las tarjetas de etapa. Hoy cualquiera con la contraseña puede hacer las dos cosas.

## Fechas y avisos

- **Medición / Instalación / Retiro:** un turno por tarjeta. Si la fecha pasó y la obra sigue sin cumplirla, la tarjeta se marca atrasada en rojo. En Por instalar el turno lleva día y hora, y los dos son obligatorios para salir de la columna. En Por medir el turno también puede llevar hora, pero es opcional: se puede dejar vacía y no frena nada.
- **Aviso ámbar "!":** en Por instalar, la obra que no tiene día u hora de instalación se marca con un signo de exclamación ámbar, y el encabezado de la columna cuenta cuántas están "sin turno". Rojo es atrasada; ámbar es que falta definir.
- **Entrega estimada:** semana en que cae medición + plazo (granito 15 días, el resto 20). Aparece al tildar "Medida tomada". Es guía: el orden de fabricación lo da la cola.
- **Cola:** dentro de cada columna van primero las que tienen turno (el más próximo arriba) y después por orden de llegada a esa columna. Cada ficha muestra su lugar (1, 2, 3…) y la 1 va enmarcada como **Próxima**. El número es el lugar real en la cola: no cambia al buscar o filtrar. Las ya medidas y ya cortadas no llevan número.
- **Botón de etapa:** "Marcar medido" y "Marcar cortado" son lo más visible de la ficha; hechos, quedan en verde ("Ya medido", "Ya cortado").
- **Nota:** un renglón libre por tarjeta (botón "Más"), para lo que frena la obra.

## Retiro en local

Desde "Más" → **Retira en el local**, o arrastrando la tarjeta al cuadro de abajo. Sale de las cuatro columnas y sigue su propio checklist. "Volver a las columnas" la devuelve a donde estaba.

## Archivar

"Archivar obra" pasa el presupuesto a **Entregado** en el hub y saca la tarjeta del tablero. Desde el tablero **no se borra nada** (ni las otras versiones del presupuesto). El hub conserva los entregados 180 días (`BS_RETENTION_DAYS`); la copia permanente es la física que arma administración.

Si un presupuesto se devuelve a Activos desde el hub, la tarjeta vuelve al tablero con sus tildes.

## Borrar una ficha

En "Más" → **Borrar ficha**. Siempre pide confirmación y dice qué se pierde; Enter no confirma, hay que tocar el botón rojo.

- Se borran los tildes, fechas, datos y adjuntos de esa obra. No se puede deshacer.
- Si la obra vino de un presupuesto de la calculadora, el presupuesto **no** se borra: vuelve a Enviado en Presupuestos. Si se aprueba de nuevo, la ficha reaparece en blanco.
- Si la obra se cargó a mano con "Cargar obra", se borra completa.

## Carga masiva (traspaso de una planilla)

Para pasar muchas obras de una vez, `calculadora/api/tablero-importar.php`:

1. Se sube por FTP `bs-data/tablero/importar.json` (fuera de `public_html`; tiene datos de clientes y **no va al repo**, que es público).
2. Con sesión iniciada se abre `blackstones.com.ar/calculadora/api/tablero-importar.php`: muestra qué hay hoy y qué se va a cargar, sin tocar nada.
3. Recién al tildar la confirmación y apretar el botón se ejecuta. Si el archivo dice `borrar_todo`, antes vacía el tablero con el mismo criterio que "Borrar ficha".
4. El archivo se renombra a `importar-hecho-<fecha>.json`, así no corre dos veces.

Es un traspaso: la planilla se adapta a los campos del tablero, no al revés. Lo que no tiene campo (saldo pendiente, día de entrega, horario del turno, modelo de bacha) va a la nota de la ficha. El formato del archivo está en el encabezado del endpoint.

## Dónde vive

| Qué | Dónde |
|---|---|
| Página | `site/public_html/presupuestos/tablero.php` |
| Endpoint de las tarjetas | `site/public_html/calculadora/api/tablero.php` |
| Definición de etapas, tildes y listas de personas | `site/public_html/calculadora/api/_tablero.php` (array `$BS_ETAPAS`: para cambiar un tilde o un título se toca solo ahí) |
| Adjuntos (subir, ver, borrar) | `site/public_html/calculadora/api/tablero-adjunto.php` |
| Cargar obra (a mano o por N° de presupuesto) | `site/public_html/calculadora/api/tablero-cargar.php` |
| Carga masiva | `site/public_html/calculadora/api/tablero-importar.php` |
| Datos | `bs-data/tablero/obras.json`, aparte de `clientes/`. Si falta, el hub y la calculadora siguen igual |
| Archivar | `state.php` con `conservar_hermanas: true` |

## Lo que la v1 deja afuera a propósito

Accesos por rol (hoy hay una sola contraseña), hojas imprimibles para medidor/cortador/instalador, agrupar cortes por material, etiquetas, métricas de tiempos, fotos por etapa, tarjetas de reclamo, saldos y conversión de moneda (eso lo lleva administración). Están en la propuesta original: `06-knowledge/layer-0-raw/2026-11-14-propuesta-sistema-gestion-kanban.md`.
