# Tablero de obras

Dónde está cada obra y qué le falta para avanzar. Vive en `blackstones.com.ar/presupuestos/tablero.php`, pestaña **Tablero** del hub. Lo usa el gerente de operaciones; entra con la misma contraseña del hub.

**Status:** v1 en producción desde 2026-11-14. Decisión: `01-strategy/decision-log/2026-11-14-tablero-de-obras.md`.

## Cómo funciona

- Una obra aparece sola cuando su presupuesto se marca **Aprobado** en Presupuestos.
- La columna Por medir tiene el botón **+ Cargar obra**, con dos usos:
  - **Con N° de presupuesto** (`0042`, o `0042-2` si tiene versiones): busca ese presupuesto de la calculadora y lo aprueba. La ficha queda atada al presupuesto real.
  - **Sin número** (cliente, teléfono, dirección, material, trabajo): para obras que no pasaron por la calculadora. Crea un presupuesto aprobado en el hub, con importe en 0: administración lo completa desde Presupuestos.
- En Por medir hay dos filtros, **Sin plano** y **Sin foto de bacha**, con la cantidad de fichas a las que les falta cada uno. Las obras que no llevan bacha no cuentan como faltantes. En Por instalar hay un filtro igual, **Sin turno**, para ver solo las que no tienen día u hora de instalación.
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
| **Por medir** | Medición | Medidor · Medido (botón) · Medidas y planos listos · Trajo la seña · Tenemos la bacha · Tenemos la placa · Piso y ascensor |
| **Por cortar** | (sin fecha: se corta en orden de llegada) | Cortador · Entregado al cortador · Cortado (botón) · Medida de mesada correcta · Medida de zócalos correcta · Color de mesada correcto · Traforos / agujeros correctos · Corte aprobado |
| **Por fabricar** | (sin fecha: se fabrica en orden de llegada) | Fabricadores · Entregado al fabricador · Fabricado (botón) · Medida y color correctos · Inglete y zócalos · Es la bacha correcta · Bacha hacia el lado correcto · Fabricación aprobada |
| **Por instalar** | Instalación, con día y hora (**obligatorios**) | Fecha y hora de instalación · Se avisó al cliente · Instaladores · Entregado y conforme |
| **Por archivar** | — | Saldo cobrado |
| **Retiro en local** (cuadro aparte, abajo) | Retiro | Seña cobrada · Pieza terminada · Se avisó al cliente · Retirada · Saldo cobrado |

Cuatro tildes de Por medir piden un dato y lo dejan escrito en la tarjeta:

- **Medidor:** quién va a medir, uno o varios de la lista (Cesar, David, Negro, Jose; se edita en `$BS_MEDIDORES`). Después queda escrito quién midió.
- **Trajo la seña:** el monto, en $ o USD. Al escribirlo se va separando en miles (1.000 · 10.000 · 100.000 · 1.000.000). Es el bloqueante de salida de Por medir: la trae el medidor, o el cliente señó antes.
- **Tenemos la bacha:** de quién es. Nuestra (la proveemos), del cliente, o no lleva.
- **Piso y ascensor:** cómo sube la pieza. Planta baja, por ascensor o por escalera con cuántos pisos ($30.000 por piso si no entra en el ascensor).
- **Instaladores** (en Por instalar, no en Por medir): quiénes van, uno o varios de la lista (Negro, Espinola, Cesar, David, Jostin). La lista se edita en `$BS_INSTALADORES`, en `calculadora/api/_tablero.php`.

En Por fabricar hay un quinto tilde con dato: **Fabricadores**, uno o varios de su lista (Negro, Espinola, David, Jostin, Pedro, Jose; se edita en `$BS_FABRICADORES`). En Por instalar queda escrito quién la fabricó.

Esos datos quedan a la vista en las etapas siguientes ("Bacha del cliente · Sube por escalera, 3 pisos · Instalan: Negro, David").

Los demás:

- **Medido:** es un botón al lado de la fecha de medición, no una pastilla. Al tocarlo, donde decía la fecha pasa a decir "Ya medido · día" y la tarjeta baja al final de la columna, bajo "Ya medidas · esperando pasar a fabricar". Así arriba queda la cola real de lo que falta medir, ordenada por fecha de medición y después por orden de llegada. Desde ese día se cuenta el plazo de entrega. Tocar "Ya medido" lo deshace.
- **Medidas y planos listos:** es un tilde aparte de Medido. Tildarlo no marca la obra como medida.
- **Tenemos la placa:** la placa del color está en el taller o reservada con el proveedor.
- **Entregado al cortador:** el cortador no ve el tablero; trabaja lo que se le entrega (plano u orden). Este tilde deja dicho que ya lo recibió.
- **Por cortar:** el cortador (Jose o Johander; se edita en `$BS_CORTADORES`) avisa que terminó con el botón **Cortado**. La ficha no avanza sola: baja a "Ya cortadas · esperando el control del gerente". Pasa a Por fabricar recién cuando el gerente tilda **Corte aprobado** y la mueve.
- **Por fabricar** es el control de la pieza antes de que salga del taller. Si la obra no lleva bacha, los dos tildes de bacha se tildan igual.
- **Por instalar** no tiene tilde de "instalada": pasar la tarjeta a Por archivar es decir que se instaló.

## Historial de la obra

Al avanzar, la ficha no pierde lo que ya pasó:

- **En la ficha:** una fila de hitos con su fecha ("Medido 24/10 · Cortado 16/11 · Corte aprobado 17/11 · Fabricado 20/11").
- **En "Más" → Historial:** por cada etapa anterior, todos los tildes cumplidos con el día en que se tildaron y su dato (quién midió, cuánto fue la seña, de quién es la bacha, quién cortó), y el día en que la obra salió de esa etapa.

Las fechas de los tildes se guardan desde que existe el historial. Lo tildado antes, o lo que vino del traspaso de la planilla, figura sin fecha (salvo la medición, que sí la tiene).

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
- **Sin fecha de medición:** una obra en Por medir sin fecha acordada es un estado indeseable. Sin fecha no hay plazo, y una obra sin plazo se queda olvidada. Por eso se marca en rojo con un "!", en tres lugares a la vez: la ficha ("Falta fecha de medición"), el encabezado de la columna ("3 sin fecha") y el filtro **Sin fecha**, que las deja a la vista. No frena el avance, y deja de avisar cuando la obra ya está medida.
- **Aviso rojo "!" en Por instalar:** la obra que no tiene día u hora de instalación se marca con un signo de exclamación rojo, y el encabezado de la columna cuenta cuántas están "sin turno". Rojo con "!" es que falta definir la fecha; rojo "atrasada" es que la fecha ya pasó.
- **Entrega estimada:** semana en que cae medición + plazo (granito 15 días, el resto 20). Aparece al tildar "Medida tomada". Es guía: el orden de fabricación lo da la cola.
- **Cola:** dentro de cada columna van primero las que tienen turno (el más próximo arriba) y después por orden de llegada a esa columna. Cada ficha muestra su lugar (1, 2, 3…) y la 1 va enmarcada como **Próxima**. El número es el lugar real en la cola: no cambia al buscar o filtrar. Las ya medidas y ya cortadas no llevan número.
- **Dos filtros en Por medir, Por cortar y Por fabricar:** "Necesitan medirse / cortarse / fabricarse" (falta ejecutar) y "Necesitan continuar". Este segundo es el pendiente urgente: junta lo que ya está marcado como hecho pero sigue en la columna, y lo que tenía fecha y se le pasó sin hacerse (había que medir el 14, es 15 y sigue sin medir). En Por cortar suma un tercer caso: las que todavía no se le entregaron al cortador. Cuando tiene fichas, el botón se pone rojo. Prender uno apaga el otro.
- **"Con el cortador" (Por cortar) y "Con el fabricador" (Por fabricar):** las que ya se le entregaron y todavía no terminó. Es su lista de trabajo. Asignar quién y entregarle la pieza son dos tildes distintos a propósito: se puede asignar hoy y entregar mañana. Lo que está sin entregar cuenta como "por avanzar". Con "Necesitan cortarse" y "Necesitan continuar" forma un grupo de tres: prender uno apaga los otros.
- **Aviso "N por avanzar" arriba de la columna:** en rojo con "!", cuenta esas mismas fichas sobre toda la columna (no cambia al filtrar). Tocarlo prende el filtro "Necesitan continuar". Convive con el aviso "N sin fecha". Al lado dice el motivo ("1 sin entregar · 2 atrasadas · 1 ya cortada"): cada ficha cuenta una sola vez (hecha > atrasada > sin entregar), así la suma da N y se ve cuando una ficha cambia de caso aunque el número quede igual. Los textos salen de `hecha_txt` y `urge_txt` de cada etapa.
- **Plazo prometido, arriba de todo el tablero:** "N con plazo vencido" (rojo) y "N vencen en 7 días" (ámbar). El plazo es medición + 15 días (granito) o 20 (el resto), en **días corridos**: cuentan sábados, domingos y feriados. Vence el día exacto (`limite` en cada ficha), no al terminar la semana. Cuentan las fichas de cualquier columna; las de Por archivar no. Tocar el aviso deja a la vista solo esas fichas en todo el tablero; tocarlo de nuevo lo apaga. Si no hay ninguna, no aparece nada. En la ficha: lejos del plazo dice "Entrega estimada: semana del…"; a 7 días o menos, "Vence el vie 20/11"; pasado, "Plazo vencido el…".
- **Fecha de hoy:** arriba a la derecha (en celular, arriba del buscador): "Hoy · Sábado 14 de noviembre". Es la fecha del servidor, la misma contra la que se calculan atrasos y plazos.
- **"Se pisan" (Por instalar):** dos instalaciones con el mismo día, un instalador en común y menos de 2 horas entre una y otra (`CHOQUE_MIN` en la página). Avisa, no frena: la ficha dice con quién y a qué hora ("David también instala 10:00 hs · 0041 …"), la columna muestra "N se pisan" y el filtro "Se pisan" aparece solo cuando hay alguna. Se calcula en pantalla con los datos que ya trae el tablero.
- **Fabricado:** Por fabricar tiene su botón, igual que Medido y Cortado. La ficha baja a "Ya fabricadas" y espera el control.
- **Qué mueve una ficha de lugar:** solo la fecha, la hora y los botones Medido / Cortado / Fabricado. Un tilde común nunca la reordena. Cuando una ficha cambia de lugar, la pantalla la acompaña y la marca un instante.
- **Pasar de etapa es siempre a mano:** aunque estén todos los tildes, la ficha no avanza sola. El gerente la pasa con "Siguiente" o arrastrándola. Decisión del dueño (2026-11-14): tiene que haber intención en el paso. Lo que quedó completo y no se pasó se ve en rojo como "por avanzar".
- **Botón de etapa:** "Marcar medido" y "Marcar cortado" son lo más visible de la ficha; hechos, quedan en verde ("Ya medido", "Ya cortado").
- **Nota:** un renglón libre por tarjeta (botón "Más"), para lo que frena la obra.

## Retiro en local

Desde "Más" → **Retira en el local**, o arrastrando la tarjeta al cuadro de abajo. Sale de las cuatro columnas y sigue su propio checklist. "Volver a las columnas" la devuelve a donde estaba.

## Archivar

"Archivar obra" pasa el presupuesto a **Entregado** en el hub y saca la tarjeta del tablero. Desde el tablero **no se borra nada** (ni las otras versiones del presupuesto). El hub conserva los entregados 180 días (`BS_RETENTION_DAYS`); la copia permanente es la física que arma administración.

Si un presupuesto se devuelve a Activos desde el hub, la tarjeta vuelve al tablero con sus tildes.

## Editar una obra y cambiarle el N° de presupuesto

Botón "Editar obra" en "Más" (también desde el calendario, que muestra la misma ficha). Se puede en cualquier etapa.

- **Corregir datos:** cliente, teléfono, dirección, material y trabajo. Solo se guarda lo que cambió.
- **Cambiar el N° de presupuesto:** la ficha se muda a ese presupuesto con todo lo hecho: tildes, fechas, seña, historial, fotos y planos. Sirve para la obra que se cargó a mano y después tuvo presupuesto en la calculadora, o para la que se ató al número equivocado. Acepta `0042` o `0042-2`.
  - El presupuesto nuevo pasa a Aprobado.
  - El anterior: si era una obra cargada a mano, se borra (su número no se recicla); si era un presupuesto de la calculadora, vuelve a Enviado.
  - Los datos de la ficha pasan a ser los del presupuesto nuevo. Teléfono y dirección se heredan de la ficha solo si el presupuesto nuevo no los tiene.
  - No se puede mudar a un presupuesto que ya está en el tablero como otra ficha, ni a uno entregado.
- **Medición al cargar:** "Cargar obra" tiene día y hora de medición (la hora es opcional), con o sin N° de presupuesto. Así la obra no nace "sin fecha". Sin día, queda marcada "sin fecha" como siempre.
- **Nunca se pierde lo escrito:** si el número no existe o algo falla, la ventana queda abierta con los datos y el motivo. Lo mismo vale para "Cargar obra".

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

## Calendario (la vista de halcón)

`/presupuestos/calendario.php`, pestaña "Calendario". Son las mismas obras del tablero puestas en el mes: no guarda nada propio, lee y escribe por `tablero.php`. Lo que se cambia acá se ve al instante en el tablero y al revés.

- **Qué muestra:** mediciones (azul), instalaciones (violeta), retiros (verde agua) y el día en que vence el plazo de cada obra (recuadro punteado; ámbar a 7 días o menos, rojo si ya venció). Las obras en Por cortar y Por fabricar no tienen fecha propia: aparecen por su vencimiento. La leyenda de arriba cuenta lo del mes y sirve para ocultar o mostrar cada tipo.
- **Entrega estimada (fantasma):** una obra con medición agendada y todavía sin medir muestra, más tenue y en cursiva, el día en que vencería su plazo si se mide ese día (medición + 15/20 corridos). Si se mueve la medición, se mueve con ella. Al marcar Medido pasa a ser vencimiento real. Si se le fija un plazo a mano (arrastrándola o desde la ficha), deja de ser estimada. Se calcula en pantalla; no se guarda.
- **Pendientes (panel de la derecha; en celular, arriba):** plazo vencido, turnos atrasados y lo que todavía no tiene día (sin fecha de medición, sin turno de instalación, sin fecha de retiro). Lo urgente va primero y se ve siempre, aunque caiga en otro mes.
- **Editar la ficha desde el calendario:** al tocar una obra, la ventana muestra a la izquierda lo propio del calendario (día, hora, plazo) y a la derecha la ficha del tablero entera: tildes, seña, bacha, fotos, nota, "Siguiente". No es una copia: es `tablero.php?solo=0042-1`, la misma página en modo "una ficha" (sin encabezado ni columnas), embebida. Cualquier cambio a la ficha del tablero aparece acá solo. Al cerrar, el calendario se vuelve a leer.
- **Agendar y reagendar:** tocar una obra abre esa ventana (día, hora, plazo). En computadora también se arrastra a otro día. Una instalación sin hora, al soltarla, pide la hora ahí mismo.
- **Cambiar el plazo de entrega:** desde la ficha, o arrastrando el recuadro "Vence". El plazo normal sigue siendo medición + 15/20 días corridos; el modificado queda guardado en la obra (`plazo`) y manda sobre el calculado. "Volver al plazo normal" lo borra. En el tablero la ficha dice "plazo modificado".
- **Avisos en el turno ("!"):** atrasado, falta la hora, se pisa con otra instalación, o la instalación quedó después del plazo.
- **Celular:** en vez de la grilla, la lista del mes día por día (solo los días que tienen algo, más hoy).
- **"Ver la ficha en el tablero":** abre `tablero.php?q=0042-1` con esa obra ya buscada.

## Dónde vive

| Qué | Dónde |
|---|---|
| Página | `site/public_html/presupuestos/tablero.php` |
| Calendario | `site/public_html/presupuestos/calendario.php` |
| Endpoint de las tarjetas | `site/public_html/calculadora/api/tablero.php` |
| Definición de etapas, tildes y listas de personas | `site/public_html/calculadora/api/_tablero.php` (array `$BS_ETAPAS`: para cambiar un tilde o un título se toca solo ahí) |
| Adjuntos (subir, ver, borrar) | `site/public_html/calculadora/api/tablero-adjunto.php` |
| Cargar obra (a mano o por N° de presupuesto) | `site/public_html/calculadora/api/tablero-cargar.php` |
| Editar obra / cambiar N° de presupuesto | `site/public_html/calculadora/api/tablero-editar.php` |
| Carga masiva | `site/public_html/calculadora/api/tablero-importar.php` |
| Datos | `bs-data/tablero/obras.json`, aparte de `clientes/`. Si falta, el hub y la calculadora siguen igual |
| Archivar | `state.php` con `conservar_hermanas: true` |

## Lo que la v1 deja afuera a propósito

Accesos por rol (hoy hay una sola contraseña), hojas imprimibles para medidor/cortador/instalador, agrupar cortes por material, etiquetas, métricas de tiempos, fotos por etapa, tarjetas de reclamo, saldos y conversión de moneda (eso lo lleva administración). Están en la propuesta original: `06-knowledge/layer-0-raw/2026-11-14-propuesta-sistema-gestion-kanban.md`.
