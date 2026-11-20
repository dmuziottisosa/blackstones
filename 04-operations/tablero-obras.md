# Tablero de obras

Dónde está cada obra y qué le falta para avanzar. Vive en `blackstones.com.ar/presupuestos/tablero.php`, pestaña **Tablero** del hub. Lo usa el gerente de operaciones; entra con la misma contraseña del hub.

**Status:** v1 en producción desde 2026-11-14. Decisión: `01-strategy/decision-log/2026-11-14-tablero-de-obras.md`.

## Cómo funciona

- Una obra aparece sola cuando su presupuesto se marca **Aprobado** en Presupuestos.
- **Desde el 18/11/2026 el tablero ya no tiene "+ Cargar obra"** (decisión final del dueño: todo nace de Relación-Contrato). No quedó ni un link en su lugar (el dueño lo pidió sacar el mismo día): las obras se cargan desde Relación-Contrato. Lo que sigue describe los dos casos, que ahora los resuelve "Cargar en Por medir" desde el contrato (con N° de presupuesto, o sin N°):
  - **Con N° de presupuesto** (`0042`, o `0042-2` si tiene versiones): busca ese presupuesto de la calculadora y lo aprueba. La ficha queda atada al presupuesto real.
  - **Sin número** (cliente, teléfono, dirección, material; el renglón "Trabajo" se sacó de esta ventana el 15/11/2026 para aliviarla, y se sigue pudiendo cargar desde "Editar obra"): para obras que no pasaron por la calculadora. **No crea un presupuesto** (dueño, 17/11/2026): la ficha no aparece en la pestaña Presupuestos, no se lleva un N° de la calculadora y en el tablero y el calendario dice **"Sin N°"**. Cuando el presupuesto exista, se le pone su número desde "Editar obra".
    - Antes creaba un presupuesto aprobado en $ 0 con el siguiente número de la calculadora. Eso hacía que el N° de la ficha no coincidiera con el del contrato y que en Presupuestos aparecieran presupuestos que nunca se hicieron.
    - Por dentro la ficha necesita un número para existir: sale de una serie aparte, de 90001 en adelante (`BS_NRO_FICHA_MANUAL_DESDE`, `bs_tablero_nro_ficha_manual()` en `_tablero.php`). No se muestra. La numeración de presupuestos (`next-nro.php`, `save.php`, `add-manual.php`) ignora esa serie.
    - Las fichas a mano que ya existían conservan su número interno viejo; como están marcadas `manual`, también dicen "Sin N°" y salen de Presupuestos.
    - Archivada, queda en "Archivadas" del tablero como cualquier otra obra.
- En Por medir hay dos filtros, **Sin plano** y **Sin foto de bacha**, con la cantidad de fichas a las que les falta cada uno. Las obras que no llevan bacha no cuentan como faltantes. En Por instalar hay un filtro igual, **Sin turno**, para ver solo las que no tienen día u hora de instalación.
- **Buscador** arriba del tablero: por cliente, N°, material, zona, teléfono, dirección o instalador. En celular salta solo a la pestaña donde está la obra.
- La tarjeta se identifica con el código del presupuesto: N° de cliente + versión (`0042-1`).
- La tarjeta se **arrastra** de una columna a otra, o se avanza con el botón **Siguiente**. Las dos cosas hacen lo mismo y respetan la misma regla.
- **Regla única:** para avanzar hay que tener todos los tildes de la columna en la que está la tarjeta. Hacia atrás se mueve libre.
- En celular se ve una columna a la vez. Para arrastrar: mantener apretada la tarjeta y soltarla sobre la pestaña de destino.

## Fichas plegadas y columnas de alto fijo

Para que el tablero no sea una página infinita (decisión del dueño, 2026-11-15):

- **Fichas plegadas:** cada ficha se ve en cuatro renglones como mucho (barrido de diseño del 19/11/2026): N° y cliente con su lugar en la cola; material y trabajo; una fila corta con cómo está (la fecha y hora en texto, y pastilla solo para lo que pide atención: atrasada, "Sin fecha", "Sin turno", "Falta la hora", "Falta confirmar", "Se pisa", "Plazo vencido"; "Hoy", en una pastilla de tinta; "Vence el…" va en ámbar sin fondo; lo hecho, en tinta con un tilde verde adelante; "Lista para pasar", en verde, cuando tiene todos los tildes); y la nota, como una cita con una raya gris. El contador "3 de 7" ya no va en la ficha cerrada. Se abre al tocarla y se vuelve a plegar con la flechita. Vienen todas plegadas; "Abrir todas las fichas" (arriba) las muestra completas y lo recuerda. Si el buscador deja una sola ficha, se muestra abierta.
- **Columnas de alto fijo (computadora):** el tablero entra en una pantalla y cada columna se recorre por dentro, con su título siempre a la vista. En celular la página se desplaza como siempre, pero con fichas plegadas.
- **Cómo se ve un filtro (16/11/2026):** apagado, todos iguales (fondo blanco, borde gris). Si adentro hay algo urgente ("Necesitan continuar", "Sin fecha", "Sin turno", "Se pisan"), lo marca solo **el número en rojo**. Prendido, relleno en el color de la etapa, igual para todos. Antes los urgentes iban pintados de rojo aunque estuvieran apagados y parecían ya seleccionados (lo marcó el dueño). Desde el 19/11/2026 la misma regla vale para los avisos de arriba del tablero y de arriba de cada columna: ya no llevan fondo rojo ni "!"; son texto con el número en color, y se rellenan al prenderlos.
- **Filtros detrás de "Filtrar":** los filtros de cada columna se muestran al tocar ese botón (queda recordado). Un filtro prendido siempre se ve, y cerrar "Filtrar" los apaga. Los avisos rojos de arriba de la columna siguen prendiendo su filtro con un toque.
- Plegada o abierta, la ficha se arrastra igual.
- **Entregado, sin abrir la ficha (16/11/2026; cambió el 19/11/2026):** en Por cortar y Por fabricar la ficha plegada dice "Entregado" en verde cuando ya se le dio la obra al cortador o al fabricador. La que no se entregó ya no dice "Sin entregar" en cada ficha: lo dice una vez el aviso de la columna ("9 sin entregar"), que además las filtra. Cuando ya está cortada o fabricada, pasa a decir eso. El texto sale de `urge_ok_txt`, en `$BS_ETAPAS`.
- **Etapas plegables (16/11/2026, computadora):** cada etapa tiene una flechita al lado del contador. Se pliega **hacia arriba**: queda solo el título con su contador y un "!" rojo si tiene algo por avanzar o sin fecha; no cambia de ancho ni mueve a las demás. Se despliega tocando el título en cualquier lado. Se puede soltar una ficha sobre una etapa plegada. Queda recordado en ese navegador (`bs-tablero-cerradas`). En tablet y celular no aplica: ya se ve de a una etapa por vez. (La primera versión plegaba hacia el costado, como una tira; el dueño pidió que sea hacia arriba.)

## Pantallas: celular, tablet y computadora

Pasada de diseño del 16/11/2026 (pedido del dueño: que se trabaje bien en celular y sobre todo en tablet). Vale para el tablero y el calendario.

| Ancho de pantalla | Tablero | Calendario |
|---|---|---|
| Menos de 700 px (celular) | Una etapa a la vez, con las pestañas de colores arriba. Fichas en una columna. | El mes en lista (agenda). |
| 700 a 1099 px (tablet parada, tablet chica acostada) | Una etapa a la vez con pestañas, y las fichas en **2 columnas** (3 desde 960 px). Se leen de izquierda a derecha, en el orden de la cola. | El mes en grilla, con los pendientes arriba. |
| 1100 px o más (tablet acostada, computadora) | Las 5 columnas juntas, sin desplazar de costado. Entre 1100 y 1279 px los márgenes se achican para que entren. | Mes en grilla y panel de pendientes al costado. |

**Interruptor de vista en computadora (dueño, 19/11/2026).** Arriba del tablero, en pantallas de 1100 px o más, hay un interruptor **Columnas | Por etapa**:

- **Columnas:** la vista de siempre, las cinco etapas una al lado de la otra. Es la que viene de entrada.
- **Por etapa:** la vista de la tablet en la computadora: una etapa a la vez, con las cinco pestañas de colores arriba y las fichas de esa etapa en varias columnas (las que entren, de 300 px como mínimo; 4 en una pantalla de 1440 px).
- Se recuerda en ese navegador (cada persona elige la suya). No cambia ningún dato: es solo cómo se mira.
- Todo funciona igual en las dos: tildes, "Más", los tres puntitos, y arrastrar una ficha (en "Por etapa" se suelta sobre la pestaña, como en la tablet).
- Plegar etapas es de la vista en columnas: en "Por etapa" una etapa plegada se ve entera, y al volver sigue plegada.
- En pantallas de menos de 1100 px el interruptor no aparece, porque ahí la vista ya es por etapa.

**La barra de arriba, ordenada (dueño, 19/11/2026: "organizar esto mejor, orden y desaturación").** Eran cinco botones con marco y la fecha. Ahora, de izquierda a derecha: el buscador, la fecha (en gris, es un dato y no un botón), el interruptor de vista, "Abrir todas / Plegar las fichas" sin marco, y **"⋯"**.

- **El único control con marco es el interruptor de vista.** Lo demás va sin marco para que la barra no compita con el tablero.
- **"⋯" guarda lo que se usa de vez en cuando:** Exportar tablero, Archivadas · N y Papelera · N. Archivadas y Papelera aparecen solo si hay algo adentro, como antes. A quien no le queda nada en ese menú (Planos y bacha), el "⋯" no le aparece.
- **Cada preferencia se recuerda por separado** en el navegador: la vista (`bs-tablero-disposicion`) y abrir/plegar (`bs-tablero-vista`). En la primera versión del interruptor compartían la misma clave y al recargar una pisaba a la otra; se corrigió el mismo día.

Reglas que se cuidan en todas las pantallas:

- **Lo que se toca mide lo que mide un dedo.** En pantallas táctiles (también una tablet acostada con las 5 columnas) los tildes, botones, pestañas y enlaces son más grandes.
- **Los campos de texto usan letra de 16 px en táctil.** Con menos, iPhone y iPad hacen zoom solos al tocar un campo.
- **Nada ensancha la pantalla.** Un renglón largo (por ejemplo la nota de una ficha plegada) se corta con "…"; antes podía ensanchar la columna más que el celular y cortar las fichas.
- **Una sola marca por ficha (19/11/2026).** Una barra roja a la izquierda si algo la frena (atrasada, sin turno, se pisa); la próxima de la cola lleva el único marco en el color de su etapa, con el rótulo "Próxima" (en gris) y su número lleno. El motivo siempre está en las etiquetas. Las fichas ya no repiten el color de la etapa en el borde: el color es de la columna (su fondo, teñido suave, su línea de arriba y el fondo de su contador).
- **En celular y tablet, los avisos de arriba van en un renglón que se desliza de costado**, para no comerle alto al tablero, y la barra de páginas deja a la vista la página actual.
- **Pendientes del calendario resumidos:** cuando el panel va arriba del mes (celular y tablet parada) muestra los primeros y un botón "Ver los N pendientes".
- Los efectos al pasar el mouse solo existen donde hay mouse (en táctil quedaban pegados después de tocar).
- Arrastrar con el dedo funciona igual en tablet: mantener apretada la ficha y soltarla sobre la pestaña (tablet parada) o sobre la columna (tablet acostada).

### Auditoría de tablet del 18/11/2026

El dueño preguntó si el tablero y cada pestaña están optimizados para tablet. Se midió cada pestaña con pantalla táctil en tres tamaños (820 × 1180 parada, 1180 × 820 acostada, 768 × 1024 parada chica), con las ventanas más usadas abiertas: desborde de costado, lo que se toca y mide menos de 36 px, campos con letra de menos de 16 px.

| Pestaña | Cómo dio | Qué se hizo |
|---|---|---|
| **Tablero** | Sin desbordes. Quedaban chicos los avisos de arriba de cada columna (29 px) y los links "Editar obra" / "Borrar ficha" (34 px). | Corregido: 0 cosas chicas en los tres tamaños, también con la ficha abierta y el menú de tres puntitos. |
| **Calendario** | Sin desbordes. Las obras del mes medían 30-32 px de alto, las franjas 22 px, y el buscador y los campos del Cronograma tenían letra de menos de 16 px. | Corregido: obras del mes 36 px, franjas 30 px, buscador y "Cronograma" 42 px, campos a 16 px. Las franjas quedan en 30 px a propósito: son anchas y más altas le comen lugar al mes. |
| **Relación-Contrato** | Parada, bien. Acostada en tablet ancha (1180 px) le tocaban los tamaños de computadora: filtros de 33 px y "Más acciones" de 30 px. | Corregido: en tablet acostada usa los mismos tamaños táctiles que parada, sin cambiar la planilla. |
| **Control** | Sin desbordes. El tilde "Hecho" medía 24 px. | Corregido: 32 px en táctil. |
| **Usuarios** | Bien. | Nada. |
| **Stock** | Es un cartel de "en construcción". | Nada. |
| **Presupuestos** | **No está pensada para tablet.** La planilla no entra parada (la fecha y las acciones quedan a la derecha, hay que deslizar), y los botones de cada renglón miden 25 px y los casilleros 16 px. | Sin tocar: es un rediseño (qué columnas se ven, cómo se muestran las acciones) y lo decide el dueño. |
| **Calculadora** | **Es una herramienta de computadora.** No se desborda, pero los campos de cada fila miden 29 px con letra de 12,5 px y los botones de fila 24 px. | Sin tocar: es la herramienta de cotizar y cualquier cambio de tamaños mueve toda la grilla. Lo decide el dueño. |

El guion de la auditoría queda con las pruebas (`tablet-audit.js`): se puede volver a correr después de cualquier cambio de diseño.

## Colores

Cada etapa tiene un color fijo, que se repite en la columna, el borde de la tarjeta, la pestaña del celular y el botón que lleva a esa etapa: **Por medir** azul, **Por cortar** gris grafito, **Por fabricar** naranja, **Por instalar** violeta, **Por archivar** verde, **Retiro en local** turquesa. El rojo queda reservado para lo que está mal (atrasada, no puede pasar).

Al levantar una tarjeta, las columnas que la reciben se encienden en su color y las que no, se apagan. Sobre una columna, la tarjeta dice "Soltar en …" con el color de destino, o "Falta: …" en rojo. Al soltar, late una vez en el color nuevo; si no podía pasar, sacude y vuelve.

Los colores se cambian en un solo lugar: las clases `.et-*` al principio del CSS de `presupuestos/tablero.php`.

## Las columnas y sus tildes

| Columna | Fecha de la tarjeta | Para salir hace falta |
|---|---|---|
| **Por medir** | Medición | Medidor · Medido (botón) · Medidas y planos listos · Trajo la seña · Tenemos la bacha · Tenemos la placa · Piso y ascensor · Medición aprobada |
| **Por cortar** | (sin fecha: por semana de entrega, con el mismo color junto) | Cortador · Entregado al cortador · Cortado (botón) · Medida de mesada correcta · Medida de zócalos correcta · Color de mesada correcto · Traforos / agujeros correctos · Corte aprobado |
| **Por fabricar** | (sin fecha: por semana de entrega, con el mismo color junto) | Fabricadores · Entregado al fabricador · Fabricado (botón) · Medida y color correctos · Inglete y zócalos · Es la bacha correcta · Bacha hacia el lado correcto · Fabricación aprobada |
| **Por instalar** | Instalación, con día y hora (se avisa "Sin turno" mientras falten; **no frenan el pase** desde el 18/11/2026) | Dirección confirmada · Instaladores · Instalado (botón) · Entregado y conforme · Instalación aprobada. "Turno confirmado" no frena |
| **Por archivar** | — | Saldo cobrado |
| **Retiro en local** (cuadro aparte, abajo) | Retiro | Seña cobrada · Pieza terminada · Se avisó al cliente · Retirada · Saldo cobrado |

### Las aprobaciones (17/11/2026)

Cada etapa de trabajo cierra con una aprobación: **Medición aprobada**, **Corte aprobado**, **Fabricación aprobada** e **Instalación aprobada**. Las dos de las puntas se sumaron el 17/11/2026, cuando el sistema pasó a tener usuarios.

- **Queda quién la dio.** En la ficha el tilde dice "Medición aprobada · Nombre", y en el historial aparece con su fecha. Vale para las cuatro; las que se tildaron antes de que hubiera usuarios no tienen nombre.
- **No se aprueba lo que no se hizo.** "Medición aprobada" pide "Medido" e "Instalación aprobada" pide "Instalado". Si se saca ese tilde, la aprobación se cae sola.
- **Sin la aprobación la ficha no pasa de etapa**, como con cualquier otro tilde.
- **Las da quien tiene el permiso `tablero_aprobar`** (hoy, Administrador y Operador). → `usuarios.md`
- La lista está en `$BS_TILDES_APROBACION` (`_tablero.php`).

### "Marcar instalado" (17/11/2026)

Por instalar tiene su botón de etapa, igual que Medido, Cortado y Fabricado. Al tocarlo la ficha baja a "Ya instaladas · falta la aprobación" y queda el día. Con "Instalado" puesto, una fecha de instalación que ya pasó deja de marcarse como atrasada. Si la obra se marca instalada sin el día o la hora del turno, la fecha sigue a la vista para completarla: sin el turno completo no pasa a Por archivar.

Cuatro tildes de Por medir piden un dato y lo dejan escrito en la tarjeta:

- **Medidor:** quién va a medir, uno o varios de la lista (Cesar, David, Negro, Jose; se edita en `$BS_MEDIDORES`). Después queda escrito quién midió.
- **Trajo la seña:** el monto, en $ o USD. Al escribirlo se va separando en miles (1.000 · 10.000 · 100.000 · 1.000.000). Es el bloqueante de salida de Por medir: la trae el medidor, o el cliente señó antes.
- **Tenemos la bacha:** de quién es (nuestra, del cliente, o no lleva) y **qué modelo es**. El modelo es obligatorio **solo si la bacha es nuestra** (la proveemos nosotros, así que sabemos cuál es). Si es del cliente se anota si se sabe, y no frena el tilde; si no lleva bacha, no aplica. Queda a la vista en la ficha y en las etapas siguientes. *(Cambiado 15/11/2026 por pedido del dueño: antes obligaba también con bacha del cliente.)* Tocar el tilde ya puesto abre la ventana para corregirlo o quitarlo. El buscador encuentra la obra por el modelo.
  - **Se puede anotar al cargar la obra** (15/11/2026): en "Cargar obra" hay un renglón opcional **Bacha: Nuestra · Del cliente · No lleva**, con el modelo si ya se sabe. Eso anota de quién es, **no tilda** "Tenemos la bacha": la pastilla queda sin tildar y dice "Bacha del cliente" o "Bacha nuestra · modelo", y el tilde se pone a mano cuando la bacha está en el taller (ahí se exige el modelo si es nuestra). La excepción es "No lleva": queda tildado, porque no hay nada que esperar. Si se anotó mal, la ventana de la pastilla tiene "Borrar el dato".
  - **Después de Por medir** (16/11/2026): la pastilla ya no está a la vista, así que el modelo se carga o corrige desde "Más" → **Editar obra**, que muestra el campo "Modelo de la bacha" en cualquier etapa si la obra tiene bacha anotada.
- **Piso y ascensor:** cómo sube la pieza. Planta baja, por ascensor o por escalera con cuántos pisos ($30.000 por piso si no entra en el ascensor).
- **Instaladores** (en Por instalar, no en Por medir): quiénes van, uno o varios de la lista (Negro, Espinola, Cesar, David, Jostin). La lista se edita en `$BS_INSTALADORES`, en `calculadora/api/_tablero.php`.

En Por fabricar hay un quinto tilde con dato: **Fabricadores**, uno o varios de su lista (Negro, Espinola, David, Jostin, Pedro, Jose; se edita en `$BS_FABRICADORES`). En Por instalar queda escrito quién la fabricó.

Esos datos quedan a la vista en las etapas siguientes ("Bacha del cliente · Sube por escalera, 3 pisos · Instalan: Negro, David").

Los demás:

- **Medido:** es un botón al lado de la fecha de medición, no una pastilla. Al tocarlo, donde decía la fecha pasa a decir "Ya medido · día" y la tarjeta pasa al grupo "Ya medidas", que desde el 16/11/2026 va **arriba** de la columna (antes iba al fondo). Debajo queda la cola de lo que falta medir, ordenada por fecha de medición y después por orden de llegada. Desde ese día se cuenta el plazo de entrega. Tocar "Ya medido" lo deshace.
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

## Foto de bacha, plano y foto de entrega

En Por medir cada tarjeta tiene dos botones: **+ Foto bacha** y **+ Plano**. En Por instalar y Por archivar aparece **+ Foto entrega** (no es obligatoria). La foto de la bacha también se puede subir desde el cuadro donde se marca de quién es (nuestra, del cliente, no lleva).

Al tocar cualquiera de los tres botones se abre una ventana con dos caminos (16/11/2026, pedido del dueño):

- **Subir desde este dispositivo:** elige el archivo de la computadora o del celular, como antes.
- **Pegar el link de Drive:** el archivo queda en Drive y el tablero guarda solo el link. En la ficha se ve un cuadrito "Drive"; al tocarlo, "Abrir en Drive" lo abre en otra pestaña. No ocupa lugar en el servidor, que era la preocupación con las fotos de entrega. Quien lo abra tiene que tener permiso en Drive: el tablero no cambia los permisos ni mira el archivo. Solo se aceptan links `https` de Google (Drive, Docs, Fotos); el mismo link no se puede cargar dos veces en la obra.

Un link cuenta igual que un archivo: saca a la obra de los filtros "Sin plano" / "Sin foto de bacha" y entra en el máximo de 6.

- La bacha y la entrega aceptan fotos. El plano acepta fotos o PDF. Hasta 6 de cada tipo por obra (entre archivos y links), 10 MB por archivo.
- Las fotos se achican solas antes de subir (el plano queda más grande, para que se lea la letra chica).
- Las miniaturas quedan en la tarjeta en todas las etapas: el taller ve el plano y la bacha al controlar la pieza.
- Tocar una miniatura la abre; desde ahí se puede ver en grande o borrar (pide confirmar).
- **Girar una foto (16/11/2026):** en esa misma ventana, el botón "Girar" da un cuarto de vuelta por toque (en el sentido del reloj) y se ve al instante. Con "Guardar así" la foto girada reemplaza a la original para todos, incluida la miniatura de la ficha; "Cancelar" la deja como estaba. Sirve para las fotos de planos que quedan de costado. Solo fotos: los PDF y los links de Drive no se giran.
- Fuera de Por medir, los botones para sumar aparecen al abrir "Más".
- Adjuntar no es obligatorio para avanzar.

Los archivos se guardan en `bs-data/tablero/adjuntos/`, fuera de la web pública: solo se ven con la sesión del hub. Se borran junto con la obra cuando la purga de 180 días elimina el presupuesto.

Quién hace qué es un acuerdo interno, no un permiso del sistema: una persona carga fotos, planos y tildes; el gerente mueve las tarjetas de etapa. Hoy cualquiera con la contraseña puede hacer las dos cosas.

## Fechas y avisos

- **Medición / Instalación / Retiro:** un turno por tarjeta. Si la fecha pasó y la obra sigue sin cumplirla, la tarjeta se marca atrasada en rojo. En Por instalar el turno lleva día y hora (al poner el día, la hora es obligatoria), y mientras falte la tarjeta avisa "Sin turno"; **desde el 18/11/2026 no frena el pase a Por archivar** (ver "Qué frena el pase de etapa"). En Por medir el turno también puede llevar hora, pero es opcional: se puede dejar vacía y no frena nada.
- **Tres puntitos arriba a la derecha de cada ficha (dueño, 18/11/2026):** abren un menú chico, sin ocupar lugar en la ficha. Están en la ficha plegada y en la abierta (ahí, al lado de plegar).
  - **Copiar:** N° de presupuesto, Teléfono y Dirección. Tocar uno lo copia tal cual figura y avisa "Se copió el teléfono: …". Lo que la ficha no tiene no aparece (una ficha "Sin N°" no ofrece el N°).
  - **Ir al calendario →** (para quien tiene acceso al calendario): abre `calendario.php?ficha=<clave>`, que se para en el mes de esa obra y le abre la ficha.
  - Se cierra tocando afuera, con Escape o al desplazar la pantalla. No van en la ficha que muestra el calendario: ahí el teléfono y la dirección se copian a la izquierda.
- **La nota de la ficha se guarda sola (19/11/2026).** Se escribe en "Más" (o en "Editar obra"), en cualquier etapa. Antes se guardaba recién al salir del campo o con Enter, y lo escrito se perdía si enseguida se tocaba otra cosa: un tilde, "Menos", o el "Cerrar" de la ventana del calendario. Se reprodujo con el código de producción; en iPad y Safari pasa siempre, porque tocar un botón no saca el foco del campo. Ahora:
  - se guarda **al hacer una pausa mientras se escribe** (1,2 segundos), sin volver a dibujar la ficha ni sacar el cursor;
  - se guarda **antes de cualquier otra cosa que se toque** en la pantalla, y al salir de la página;
  - desde el calendario, al cerrar la ventana **la guarda el calendario** (la ficha de al lado desaparece con la ventana y su propio guardado no llegaría a salir);
  - los guardados del tablero van con `keepalive`: uno que ya salió termina aunque la página se cierre.
  - Cada guardado queda en el registro como un cambio de nota; escribiendo con pausas puede haber más de un renglón.
- **Cuánto tiempo estuvo en cada etapa (dueño, 18/11/2026):** el historial ("Más" en el tablero; a la izquierda de la ficha en el calendario; "Historial del tablero" en el contrato) dice, por cada etapa que ya pasó, "salió el … · estuvo N días", y al final la etapa actual con "está hace N días" (o "entró hoy"). No se guarda un dato nuevo: sale de los pases que ya se anotaban en la ficha (`hist`). Días corridos; si pasó dos veces por la misma etapa (la devolvieron), se suman. Una ficha de la carga inicial, que entró directo en una etapa avanzada, no dice cuánto estuvo en las anteriores (no se inventa), y su primera estadía se cuenta desde el día en que entró al sistema.
- **La entrega de la ficha se ve más (dueño, 18/11/2026):** el renglón "Entrega estimada: semana del …" / "Vence el …" / "Plazo vencido el …" va en negrita y un punto más grande que el resto de la ficha. Los colores no cambiaron (ámbar cerca del plazo, rojo vencido).
- **Qué frena el pase de etapa (dueño, 18/11/2026):** solo los tildes del trabajo de la etapa. En Por instalar: Dirección confirmada, Instaladores, **Instalado**, Entregado y conforme e **Instalación aprobada** (que a su vez pide "Instalado"). **No frenan:** el día y la hora del turno, ni "Turno confirmado" (en ninguna etapa). Una obra que se instaló sin que nadie cargara el turno pasa igual a Por archivar. Lo valida el servidor (`tablero.php`, acción `mover`), valga para el botón "Siguiente" o para el arrastre; la tarjeta muestra el botón en gris mientras falte algo y, al tocarlo, dice qué falta.
  - Ya instalada, la tarjeta deja de avisar "Sin turno" y de mostrar la fecha: no hay nada que cargar.
- **Sin fecha de medición:** una obra en Por medir sin fecha acordada es un estado indeseable. Sin fecha no hay plazo, y una obra sin plazo se queda olvidada. Por eso se marca en rojo con un "!", en tres lugares a la vez: la ficha ("Falta fecha de medición"), el encabezado de la columna ("3 sin fecha") y el filtro **Sin fecha**, que las deja a la vista. No frena el avance, y deja de avisar cuando la obra ya está medida.
- **Aviso rojo "!" en Por instalar:** la obra que no tiene día u hora de instalación se marca con un signo de exclamación rojo, y el encabezado de la columna cuenta cuántas están "sin turno". Rojo con "!" es que falta definir la fecha; rojo "atrasada" es que la fecha ya pasó.
- **Entrega estimada:** semana en que cae medición + plazo (20 días corridos para todo; hasta el 16/11/2026 el granito llevaba 15). Aparece al tildar "Medida tomada". Es guía: el orden de fabricación lo da la cola.
- **Lo ya cortado no salta de semana (16/11/2026, corregido el mismo día):** en Por cortar y Por fabricar manda el calendario: primero la **semana de entrega**, esté la obra hecha o no. Una obra **no sube por estar cortada**; queda en su semana. Dentro de la misma semana, lo ya hecho que espera el control va primero (por vencimiento) y después lo que falta, agrupado por color. Como se corta en orden de cola, lo ya cortado queda naturalmente arriba, y ahí lleva su título: una banda verde con tilde y contador ("Ya cortadas · Falta el control"). Si alguien corta fuera de orden una obra de una semana posterior, esa ficha se queda en su semana con su etiqueta verde "Ya cortado", sin pasar por encima de una vencida. Lo que falta hacer ("Necesitan cortarse (13)", cuenta solo lo no cortado) y el rótulo de cada semana ("Entrega: semana del 16/11 · esta semana") son texto simple: el dueño pidió mantenerlos así. En Por medir no hay semana de entrega todavía, así que "Ya medidas" sigue yendo primero. La primera versión del día subía todo lo ya cortado arriba de la columna; el dueño la corrigió: "Inés no sube por estar cortada, va arriba por el orden natural del calendario".
- **Hoy y la semana (16/11/2026):** arriba a la derecha, junto a la fecha de hoy, va la semana en curso de lunes a viernes ("Semana 16-20 de noviembre"; en celular, "Lun 16 nov · Sem 16-20"). En tablet el buscador pasa a su propio renglón para que entre.
- **Cola en Por cortar y Por fabricar (16/11/2026):** como no tienen turno, se ordenan por **semana de entrega** (la que vence antes, arriba) y dentro de la semana van **juntas las del mismo color**, para que dos Negro Boreal de la misma semana se corten una atrás de la otra. Entre colores de la misma semana va primero el que vence antes; dentro de un color, la que vence antes. Un renglón marca dónde empieza cada semana ("Entrega: semana del 16/11 · esta semana"). Antes era por orden de llegada a la columna, que no miraba el vencimiento: por eso una obra que vencía el 22 podía quedar adelante de una ya vencida.
- **"Por definir" (18/11/2026):** es siempre la primera opción del campo de material, en "Cargar obra" y "Editar obra", para cuando el cliente todavía no eligió el color. Lo mismo en el contrato.
- **Material del catálogo (16/11/2026):** en "Cargar obra" y "Editar obra" el campo de material sugiere los colores de la calculadora (la misma lista que usa Presupuestos) y guarda con el formato "Marca · Color". Si el color no está en el catálogo, se guarda como se escribió. Las fichas viejas no se reescriben: el tablero **lee** el texto para reconocer el color ("Negro Boreal Pulido", "gris mara granito", "SUGGAR") y con eso las junta. Si el mismo nombre existe en varias marcas (Taj Mahal, Calacatta Gold) y el texto no dice cuál, no se asume marca. Lo que no reconoce se agrupa por el texto tal cual; para corregirlo, elegir el color de la lista en "Editar obra". El export trae la columna "Color reconocido" para revisar.
- **Cola:** dentro de cada columna van primero las que tienen turno (el más próximo arriba) y después por orden de llegada a esa columna (en Por cortar y Por fabricar, ver arriba). Cada ficha muestra su lugar (1, 2, 3…) y la 1 va enmarcada como **Próxima**. El número es el lugar real en la cola: no cambia al buscar o filtrar. Las ya medidas y ya cortadas no llevan número.
- **Dos filtros en Por medir, Por cortar y Por fabricar:** "Necesitan medirse / cortarse / fabricarse" (falta ejecutar) y "Necesitan continuar". Este segundo es el pendiente urgente: junta lo que ya está marcado como hecho pero sigue en la columna, y lo que tenía fecha y se le pasó sin hacerse (había que medir el 14, es 15 y sigue sin medir). En Por cortar suma un tercer caso: las que todavía no se le entregaron al cortador. Cuando tiene fichas, el botón se pone rojo. Prender uno apaga el otro.
- **"Con el cortador" (Por cortar) y "Con el fabricador" (Por fabricar):** las que ya se le entregaron y todavía no terminó. Es su lista de trabajo. Asignar quién y entregarle la pieza son dos tildes distintos a propósito: se puede asignar hoy y entregar mañana. Lo que está sin entregar cuenta como "por avanzar". Con "Necesitan cortarse" y "Necesitan continuar" forma un grupo de tres: prender uno apaga los otros.
- **Aviso de lo que hay que mover, arriba de la columna (19/11/2026; antes "N por avanzar"):** dice el motivo concreto con cada número en rojo ("8 atrasadas · 3 ya medidas", "9 sin entregar"), cuenta sobre toda la columna (no cambia al filtrar) y tocarlo prende el filtro "Necesitan continuar". Al lado van "N sin fecha" o "N sin turno" y "N se pisan", cada uno con su filtro, y a la derecha "Filtrar".
- **Atrasadas, arriba de todo el tablero:** "N atrasadas" (rojo), primero de la fila porque es lo más urgente. Cuenta las fichas de cualquier columna que tenían fecha (medición, instalación o retiro), la fecha pasó y siguen sin hacerse. Tocarlo deja a la vista solo esas fichas; tocarlo de nuevo lo apaga.
- **Plazo prometido, arriba de todo el tablero:** "N con plazo vencido" (rojo) y "N vencen en 7 días" (ámbar). El plazo es medición + **20 días corridos para todos los materiales** (estándar único, decisión del dueño del 16/11/2026; antes el granito de la calculadora llevaba 15). En **días corridos**: cuentan sábados, domingos y feriados. Vence el día exacto (`limite` en cada ficha), no al terminar la semana. Cuentan las fichas de cualquier columna; las de Por archivar no. Tocar el aviso deja a la vista solo esas fichas en todo el tablero; tocarlo de nuevo lo apaga. Si no hay ninguna, no aparece nada. En la ficha: lejos del plazo dice "Entrega estimada: semana del…"; a 7 días o menos, "Vence el vie 20/11"; pasado, "Plazo vencido el…".
- **Fecha de hoy:** arriba a la derecha (en celular, arriba del buscador): "Hoy · Sábado 14 de noviembre". Es la fecha del servidor, la misma contra la que se calculan atrasos y plazos.
- **"Se pisan" (Por instalar):** dos instalaciones con el mismo día, un instalador en común y menos de 2 horas entre una y otra (`CHOQUE_MIN` en la página). Avisa, no frena: la ficha dice con quién y a qué hora ("David también instala 10:00 hs · 0041 …"), la columna muestra "N se pisan" y el filtro "Se pisan" aparece solo cuando hay alguna. Se calcula en pantalla con los datos que ya trae el tablero.
- **Fabricado:** Por fabricar tiene su botón, igual que Medido y Cortado. La ficha baja a "Ya fabricadas" y espera el control.
- **Qué mueve una ficha de lugar:** solo la fecha, la hora y los botones Medido / Cortado / Fabricado. Un tilde común nunca la reordena. Cuando una ficha cambia de lugar, la pantalla la acompaña y la marca un instante.
- **Pasar de etapa es siempre a mano:** aunque estén todos los tildes, la ficha no avanza sola. El gerente la pasa con "Siguiente" o arrastrándola. Decisión del dueño (2026-11-14): tiene que haber intención en el paso. Lo que quedó completo y no se pasó se ve en rojo como "por avanzar".
- **Botón de etapa:** "Marcar medido" y "Marcar cortado" son lo más visible de la ficha; hechos, quedan en verde ("Ya medido", "Ya cortado").
- **Nota:** un renglón libre por tarjeta (botón "Más"), para lo que frena la obra.

## Limpiar filtros

Cuando algo está filtrando el tablero (el buscador, un aviso de arriba prendido o un filtro de columna), aparece **Limpiar filtros** primero en la fila de avisos, con la cantidad de filtros si hay más de uno. Un toque saca todo y vuelven a verse todas las obras. Sin nada filtrando, el botón no está. Los paneles "Filtrar" abiertos quedan como estaban: abrirlos no es filtrar. *(Agregado 16/11/2026: el dueño marcó que faltaba una forma de volver todo a lo normal.)*

## Exportar tablero

Arriba a la derecha, **Exportar tablero** baja un archivo `tablero-blackstones-AAAA-MM-DD.csv` con **todas** las obras del tablero (no solo las que deja ver el buscador o un filtro), una por fila, ordenadas por etapa. Se abre con Excel: separador `;`, con acentos.

Columnas: N°, cliente, teléfono, dirección, zona, material, trabajo, etapa, turno (qué, día, hora), atrasada, medido el, plazo de entrega y su estado, seña, bacha (de quién, modelo, si la tenemos), cómo sube, quién mide / corta / fabrica / instala, qué falta en la etapa, cantidad de fotos de bacha, de planos y de fotos de entrega, los links de Drive cargados, y la nota.

Sirve para corroborar contra la planilla o para pasarle el estado del tablero a alguien que no entra al sistema. **El archivo tiene nombres, teléfonos y direcciones de clientes: no se sube al repo** (es público) ni se manda por canales abiertos. No incluye las obras archivadas: esas están en Presupuestos · Entregados.

## Retiro en local

> **Desactivado por ahora (15/11/2026, decisión del dueño).** No aparece el cuadro de abajo, ni el botón "Retira en el local", ni los retiros en el calendario, y el servidor rechaza mover una obra ahí. Si alguna obra ya estaba en Retiro, el cuadro se sigue mostrando solo para esa obra hasta que se la devuelva a las columnas. Para volver a activarlo: borrar la línea `'oculta' => true` de la etapa `retiro` en `site/public_html/calculadora/api/_tablero.php` y subir ese archivo. Lo que sigue describe cómo funciona cuando está activo.

Desde "Más" → **Retira en el local**, o arrastrando la tarjeta al cuadro de abajo. Sale de las cuatro columnas y sigue su propio checklist. "Volver a las columnas" la devuelve a donde estaba.

## Archivar

"Archivar obra" saca la tarjeta del tablero (por dentro la cotización pasa a "entregado"). **No se borra nada** (ni las otras versiones del presupuesto). Las archivadas se guardan **1 año** desde que se archivan (`BS_ARCHIVADAS_DAYS`; dueño, 17/11/2026, antes 180 días), con sus fotos, planos, tildes e historial. Pasado el año se borran de verdad, sin papelera. Devolverla al tablero frena el reloj; archivarla de nuevo lo arranca otra vez. Los presupuestos siguen durando 180 días (`BS_RETENTION_DAYS`). La copia permanente es la física que arma administración. Cuánto ocupa → `01-strategy/decision-log/2026-11-17-archivadas-un-ano.md`.

- **Archivadas (17/11/2026):** arriba del tablero aparece el botón **"Archivadas · N"** cuando hay alguna. Abre la lista con cliente, material, cuándo se archivó y cuántos días le quedan. **"Devolver al tablero"** la vuelve a la etapa en la que estaba, con sus tildes, seña, fotos y turno.
  - Antes eso se hacía desde la pestaña "Presupuestos · Reporte", que se eliminó ese día. → `01-strategy/decision-log/2026-11-17-sin-pestana-reporte.md`
  - La lista sale de `tablero.php?archivadas=1`: solo cotizaciones "entregado" que tienen registro en el tablero.

- **Una obra viva no vence (16/11/2026):** la limpieza nocturna de 180 días ya no toca las obras que están en el tablero. Antes las borraba, con sus fotos, a los 180 días de aprobadas. El reloj de 180 días empieza al archivar.

## Editar una obra y cambiarle el N° de presupuesto

Botón "Editar obra" en "Más" (también desde el calendario, que muestra la misma ficha). Se puede en cualquier etapa.

- **Corregir datos:** nombre y apellido, teléfono, dirección, material (del catálogo de la calculadora) y **nota**. Solo se guarda lo que cambió. *(16/11/2026: el campo "Trabajo" se cambió por "Nota" a pedido del dueño; es la misma nota que se ve en la ficha y en "Más". El dato "trabajo" de las fichas que ya lo tenían se conserva y se sigue viendo, pero ya no se edita desde acá.)*
- **"Nombre y apellido" es un solo campo, y se llama igual en todos lados** (dueño, 17/11/2026): Cargar obra, Editar obra, el contrato y la calculadora. Un presupuesto hecho sin saber el nombre trae el teléfono en su lugar; en ese caso la ficha figura con el teléfono y, en "Editar obra", el campo arranca vacío y avisa que falta. Guardar otra cosa sin ponerlo no da error. Si la ficha tiene contrato enlazado, toma de ahí el nombre, el teléfono y la dirección que le falten → `relacion-contrato.md`.
- **Cambiar el N° de presupuesto:** la ficha se muda a ese presupuesto con todo lo hecho: tildes, fechas, seña, historial, fotos y planos. Sirve para la obra que se cargó a mano y después tuvo presupuesto en la calculadora, o para la que se ató al número equivocado. Acepta `0042` o `0042-2`.
  - El presupuesto nuevo pasa a Aprobado.
  - El anterior: si era una obra cargada a mano, se borra (su número no se recicla); si era un presupuesto de la calculadora, vuelve a Enviado.
  - Los datos de la ficha pasan a ser los del presupuesto nuevo. Teléfono y dirección se heredan de la ficha solo si el presupuesto nuevo no los tiene.
  - No se puede mudar a un presupuesto que ya está en el tablero como otra ficha, ni a uno entregado.
  - **Si la ficha tenía contrato, el contrato la sigue (17/11/2026):** queda enlazado con la ficha nueva y toma su N°. Enlazados, el contrato y la ficha comparten el número. El tablero le avisa a Relación-Contrato (`mudar_enlazado`); si Relación-Contrato no existe, el aviso falla en silencio y la mudanza se hace igual.
- **Medición al cargar:** "Cargar obra" tiene día y hora de medición (la hora es opcional), con o sin N° de presupuesto. Así la obra no nace "sin fecha". Sin día, queda marcada "sin fecha" como siempre.
- **Nunca se pierde lo escrito:** si el número no existe o algo falla, la ventana queda abierta con los datos y el motivo. Lo mismo vale para "Cargar obra".

## Cómo entra una obra al tablero (regla del 18/11/2026)

- **Una sola puerta (18/11/2026, decisión final):** "Cargar en Por medir", desde su contrato en Relación-Contrato. El tablero ya no carga obras. `tablero-cargar.php` exige el id de un contrato que exista; sin eso responde que las obras se cargan desde su contrato. Lo usan administradores y operadores.
- **Aviso "N sin contrato":** arriba del tablero, junto a "atrasadas" y "plazo vencido". Aparece solo si hay fichas sin contrato (las de la carga inicial); tocarlo las deja solas a la vista. No aparece si Relación-Contrato no contesta.
- **Si el N° del contrato es el presupuesto de otra persona, no se carga** y se avisa de quién es cada uno ("un número, una persona").
- **Siempre nace en Por medir, vacía.** No hereda etapa, tildes ni fotos de ningún registro anterior. Lo que está en la papelera se recupera desde la Papelera.
- **El número interno de una ficha a mano no es un N° de presupuesto.** Si alguien lo escribe en "Cargar obra", la respuesta es que ese presupuesto no existe.
- **La calculadora no puede guardar un presupuesto encima del número de otra persona** ni del de una ficha a mano: lo guarda con un N° nuevo y avisa.
- El porqué → `01-strategy/decision-log/2026-11-18-un-numero-una-persona.md`.

## Borrar una ficha

- **Borrar el presupuesto no borra la ficha (16/11/2026).** Si alguien elimina en Presupuestos el presupuesto de una obra que está en el tablero, o le llegan los 180 días, el presupuesto sale de esa pestaña y la ficha sigue igual, con sus fotos. La ficha solo se borra con "Borrar ficha", acá.
- **"→ Aprobado" ya no existe en Presupuestos (17/11/2026).** Si una obra está en el tablero es que fue aprobada. Un presupuesto entra al tablero solo desde su contrato, con "Cargar en Por medir". El servidor rechaza aprobar desde Presupuestos; devolver un entregado al tablero desde el Reporte sigue permitido.
- **Presupuestos ya no cambia la etapa de una obra viva:** en esa pestaña, un presupuesto aprobado muestra "En el tablero →" en lugar de los botones de estado.
- **Pero la etapa se ve y se filtra en Presupuestos (16/11/2026):** la columna Estado de una obra viva dice su etapa del tablero ("Por cortar"), y el filtro de estados tiene un grupo "En el tablero" con cada etapa. El CSV también la lleva.
- **La ficha abierta dice si tiene contrato (17/11/2026).** Debajo del material aparece "Con contrato", en verde, y lleva a ese contrato en Relación-Contrato. Si Relación-Contrato no existe o no contesta, la marca no aparece y nada más cambia.
- **Si la ficha está enlazada con un contrato, se borran los dos.** El aviso lo dice antes, con cuántos pagos tiene y si tiene el presupuesto adjunto.

En "Más" → **Borrar ficha**. Siempre pide confirmación y dice qué se pierde; Enter no confirma, hay que tocar el botón rojo.

- **Va a la papelera (16/11/2026).** La ficha sale del tablero y del calendario con sus tildes, fechas, datos y adjuntos, y se puede recuperar durante 30 días desde el botón "Papelera" del tablero; vuelve a la etapa en la que estaba. Pasados los 30 días se borra de verdad (lo hace la limpieza diaria, y también cada vez que se abre la papelera).
- Al vencer la papelera: si la obra vino de un presupuesto de la calculadora, el presupuesto **no** se borra, vuelve a Enviado en Presupuestos; si se cargó a mano con "Cargar obra", se borra completa.
- Mientras está en la papelera no aparece en Presupuestos ni en Archivadas.

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
- **Entrega estimada (fantasma):** una obra con medición agendada y todavía sin medir muestra, más tenue y en cursiva, el día en que vencería su plazo si se mide ese día (medición + 20 corridos). Si se mueve la medición, se mueve con ella. Al marcar Medido pasa a ser vencimiento real. Si se le fija un plazo a mano (arrastrándola o desde la ficha), deja de ser estimada. Se calcula en pantalla; no se guarda.
- **Pendientes (panel de la derecha; en celular, arriba):** plazo vencido, turnos atrasados y lo que todavía no tiene día (sin fecha de medición, sin turno de instalación, sin fecha de retiro). Lo urgente va primero y se ve siempre, aunque caiga en otro mes.
- **Editar la ficha desde el calendario:** al tocar una obra, la ventana muestra a la izquierda lo propio del calendario (día, hora, plazo) y a la derecha la ficha del tablero entera: tildes, seña, bacha, fotos, nota, "Siguiente". No es una copia: es `tablero.php?solo=0042-1`, la misma página en modo "una ficha" (sin encabezado ni columnas), embebida. Cualquier cambio a la ficha del tablero aparece acá solo. Al cerrar, el calendario se vuelve a leer.
  - **Teléfono y dirección a la vista, con botón de copiar (dueño, 18/11/2026):** a la izquierda, abajo del historial, sin tener que abrir "Más"; cada uno con un ícono que lo copia tal cual figura en la ficha y avisa "Se copió…". No son links: no abren WhatsApp ni el mapa (el dueño no quiere que el sistema abra otro WhatsApp). Si falta el dato, dice "Sin dirección" o "Sin teléfono", sin botón. Si se cambian con "Editar obra" en la ficha de al lado, se actualizan solos.
  - **El historial va a la izquierda, ya desplegado (dueño, 18/11/2026):** debajo de día, hora y plazo se ve qué se hizo en cada etapa que ya pasó y cuándo. En la ficha de al lado ya no está adentro de "Más" (en el tablero sí sigue ahí). Si la obra cambia de etapa desde esa ficha, el historial se pone al día solo. Una obra que sigue en Por medir dice "Todavía está en su primera etapa".
- **Agendar y reagendar:** tocar una obra abre esa ventana (día, hora, plazo). En computadora también se arrastra a otro día. Una instalación sin hora, al soltarla, pide la hora ahí mismo.
- **Cronograma (18/11/2026):** botón "Cronograma" al lado del mes. Abre ahí mismo la ruta de un día, en dos pestañas: **Medición** (las obras de Por medir) e **Instalación** (las de Por instalar). Cada pestaña dice cuántas tiene. Las obras van **en orden de hora** y numeradas, cada una con nombre y apellido, dirección, teléfono y color.
  - Arriba, un botón por cada día que tiene turnos pendientes (en rojo los que ya pasaron) y uno para las que todavía no tienen día ("Sin fecha" en Medición, "Sin turno" en Instalación). Arranca en hoy o en el próximo día con turnos.
  - **La hora y el día se cambian ahí mismo y se guardan solos.** Cambiar la hora reordena la ruta; cambiar el día pasa la obra a la ruta de ese día. La que no tiene hora queda última.
  - **No inventa ningún dato:** escribe el turno de la ficha en su etapa (la misma acción `fecha` de siempre). Por eso repercute en el tablero, en el calendario y en Relación-Contrato, que lo leen del tablero.
  - **En Instalación** cada renglón dice además quiénes instalan y cuándo vence el plazo, y avisa en rojo lo mismo que el calendario: falta la hora, queda después del plazo, o se pisa con otra instalación del mismo equipo. La hora de instalación sigue siendo obligatoria: se puede poner primero el día, pero el renglón avisa hasta que tenga hora.
  - "Ver la ruta en el mapa" abre Google Maps con las direcciones en ese orden; la dirección sola abre el mapa y el teléfono abre WhatsApp. Si a una obra le falta la dirección o el teléfono, lo dice en rojo.
  - En Medición, abajo nombra las que ya se midieron ese día. Al cerrar, el calendario queda al día.
- **Buscador (dueño, 18/11/2026):** arriba, al lado de "Cronograma". Deja en el mes, en la lista del celular y en Pendientes solo las obras cuyo nombre o N° coincide (sin importar acentos ni mayúsculas; el teléfono no cuenta, y el código interno de una ficha a mano tampoco). Arriba del mes dice cuántas encontró y nombra hasta cinco, con su etapa y su fecha; tocar una lleva el calendario a su mes y abre la ficha. "Limpiar" o Escape vuelven a mostrar todo. No se guarda entre visitas y se combina con el filtro por bloque.
- **Filtro por bloque de medición, a la izquierda del mes (16/11/2026):** es, junto con el buscador, lo único que filtra el calendario, y sirve para hacer foco cuando se está siguiendo un bloque.
  - Hay un casillero por cada día de medición que existe en el tablero (solo datos reales), con la cantidad de obras; el número va en rojo si su entrega venció y en ámbar si está en curso.
  - **Marcar un bloque muestra su par completo:** la medición y su entrega con el vencimiento. Como suelen caer en meses distintos, el calendario deja de mostrar un mes y muestra **el tramo entero**, desde la semana de la medición hasta la del vencimiento (el título pasa a "24 oct – 13 nov" y no hay flechas de mes). El panel de Pendientes también queda solo con ese bloque.
  - Se pueden marcar varios. Con todos marcados se ve todo, incluidas las obras que todavía no tienen fecha de medición.
  - Arriba de la grilla, un renglón resume lo que está en foco (medición, entrega, estado) y tiene **Volver al mes**. **Ver todo**, en el panel, hace lo mismo. Los dos aparecen solo cuando hay algo marcado.
  - Lo marcado no queda guardado entre visitas, para que nada quede escondido sin querer.
  - Debajo del filtro están las **Referencias**: qué color es cada cosa (mediciones, instalaciones, entregas, vencimientos) y cuántas hay a la vista. No se tocan. *(Durante unas horas fueron casilleros para prender y apagar cada tipo; se sacaron porque venían todos marcados y al tocarlos para "ver" se apagaban, y con eso la historia del bloque quedaba incompleta. Lo marcó el dueño.)*
  - En pantallas de menos de 1360 px el panel va arriba del mes, plegado detrás del botón "Filtros", que muestra cuántos bloques hay marcados.
- **Entrega estimada que ya pasó = vencida (16/11/2026):** si la medición estaba agendada y la fecha estimada de entrega (medición agendada + 20) ya pasó, el cartelito deja de decir "Entrega estimada" y pasa a "Venció", en rojo, y la obra entra en "Plazo vencido" del panel de Pendientes con la aclaración "sin marcar medida". Los días corren desde la medición agendada, esté o no tildado "Medido" (criterio del dueño). En el tablero esa obra sigue figurando como medición atrasada hasta que alguien marque Medido.
- **Cambio de mes a la vista (16/11/2026):** el día 1 (y el primer casillero de la grilla) llevan el nombre del mes, y una línea dorada dibuja el borde entre un mes y el siguiente. Es solo visual: no cambia qué se muestra, ni el filtro por bloque, ni las franjas. Sirve sobre todo cuando un bloque en foco cruza de un mes a otro.
- **Bloque de medición y entrega del bloque (16/11/2026):** son dos cosas distintas y se ven distinto.
  - **Bloque de medición (azul, un solo día):** todas las obras que se miden o se midieron un mismo día, sean ocho o una sola y sea el día de la semana que sea. En ese día va una pastilla azul "Medición · 8 obras", con un tilde cuando están todas medidas. 
  - **Entrega del bloque (franja dorada, seis días):** la ventana en la que se espera entregar ese bloque, del día 15 al día 20 desde la medición. Dice "Entrega · medidas el 14-nov · 8 obras" y "Vence" en el último día. Los cartelitos "Vence" de cada obra siguen en el día 20.
  - Colores de la entrega: dorada mientras falta, ámbar cuando hoy cae dentro de la ventana, roja si pasó el día 20 y quedan obras sin entregar. Punteada y en cursiva ("Entrega estimada") si las obras tienen medición agendada pero todavía no se midieron.
  - Dos entregas que se pisan (bloque del sábado y del lunes) van una debajo de la otra, siempre en el mismo renglón.
  - Tocar cualquiera de las dos abre la misma ventana, que dice por separado la medición y la entrega y lista las obras para abrir cada ficha.
  - Quedan fuera de la franja de entrega las obras con plazo puesto a mano (van con su propio cartelito en su día) y las que ya están en Por archivar.
  - En celular (mes en lista): "Bloque de medición · 8 obras" en su día, y la entrega el día que empieza y el día que vence.
  - Los días 15 y 20 salen de `BS_PLAZO_VENTANA` y `BS_PLAZO_DIAS` en `_tablero.php`.
- **Mediciones ya hechas:** el día en que se midió cada obra queda marcado con un tilde verde, esté la obra en la etapa que esté (antes, al marcar Medido, la medición desaparecía del calendario). Es el día que le da nombre al bloque. No se puede arrastrar; tocarla abre la ficha.
  - **Segundo tilde si ya está instalada (dueño, 18/11/2026):** en ese mismo renglón, adelante va el tilde verde de la medición y, después del nombre, el de la instalación, en el violeta que tiene Por instalar en el tablero (así los N° y los nombres quedan alineados). Aparece cuando la obra está en Por instalar con "Instalado" marcado, o cuando ya está en Por archivar, tenga o no ese tilde (las fichas de la carga inicial entraron directo en Por archivar sin él). Al pasar el mouse dice el día, si se sabe. Destildar "Instalado" lo saca.
- **Cambiar el plazo de entrega:** desde la ficha, o arrastrando el recuadro "Vence". El plazo normal es medición + 20 días corridos; el modificado queda guardado en la obra (`plazo`) y manda sobre el calculado. "Volver al plazo normal" lo borra. En el tablero la ficha dice "plazo modificado".
- **Avisos en el turno ("!"):** atrasado, falta la hora, se pisa con otra instalación, o la instalación quedó después del plazo.
- **Celular:** en vez de la grilla, la lista del mes día por día (solo los días que tienen algo, más hoy).
- **"Ver la ficha en el tablero":** abre `tablero.php?q=0042-1` con esa obra ya buscada.

- **Con un bloque marcado el calendario no se recorta (16/11/2026):** arranca en la semana de la medición y muestra semanas enteras hasta el vencimiento (nunca menos de 5), sin días apagados. Las flechas siguen andando: corren la vista de a 4 semanas con el filtro puesto, y "Hoy" trae la semana actual. Si te alejaste, "Ir al bloque" vuelve al tramo de ese bloque; "Volver al mes" saca el filtro. Antes, con filtro, las flechas desaparecían y la grilla terminaba en el vencimiento.

## Dónde vive

| Qué | Dónde |
|---|---|
| Página | `site/public_html/presupuestos/tablero.php` |
| Calendario | `site/public_html/presupuestos/calendario.php` |
| Relación-Contrato | `site/public_html/presupuestos/relacion-contrato.php`. Tiene su propio doc: `relacion-contrato.md` |
| Endpoint de las tarjetas | `site/public_html/calculadora/api/tablero.php` |
| Definición de etapas, tildes y listas de personas | `site/public_html/calculadora/api/_tablero.php` (array `$BS_ETAPAS`: para cambiar un tilde o un título se toca solo ahí) |
| Adjuntos (subir, ver, borrar) | `site/public_html/calculadora/api/tablero-adjunto.php` |
| Cargar en Por medir (desde su contrato; con o sin N° de presupuesto) | `site/public_html/calculadora/api/tablero-cargar.php` |
| Editar obra / cambiar N° de presupuesto | `site/public_html/calculadora/api/tablero-editar.php` |
| Carga masiva | `site/public_html/calculadora/api/tablero-importar.php` |
| Papelera (tirar, restaurar, listar) | `site/public_html/calculadora/api/tablero-papelera.php`. Los 30 días se definen en `_tablero.php` (`BS_PAPELERA_DIAS`) |
| Datos | `bs-data/tablero/obras.json`, aparte de `clientes/`. Si falta, el hub y la calculadora siguen igual |
| Archivar | `state.php` con `conservar_hermanas: true` |

## Lo que la v1 deja afuera a propósito

Accesos por rol (hoy hay una sola contraseña), hojas imprimibles para medidor/cortador/instalador, agrupar cortes por material, etiquetas, métricas de tiempos, fotos por etapa, tarjetas de reclamo, saldos y conversión de moneda (eso lo lleva administración). Están en la propuesta original: `06-knowledge/layer-0-raw/2026-11-14-propuesta-sistema-gestion-kanban.md`.

## Barrido de errores del 18/11/2026: lo que cambió en el tablero

- **Archivar lo valida el servidor:** la obra tiene que estar en Por archivar con "Saldo cobrado". Antes solo lo miraba la pantalla.
- **Eliminar de Presupuestos una obra archivada no la borra:** sale de la pestaña y sigue en "Archivadas". Antes se borraba de verdad, con fotos y planos.
- **"Editar obra", cambiar el N°:** no deja mudarse al N° de una ficha que está en la papelera ni al número interno de otra ficha a mano, y solo hereda teléfono y dirección si el presupuesto es de la misma persona.
- **Bacha:** si una obra cargada como "No lleva" pasa a llevar, el tilde automático "Tenemos la bacha" se va.
- **Cronograma del calendario:** guarda cuando se termina de escribir (no en cada tecla), relee el tablero antes de guardar y no le escribe el turno a una obra que ya cambió de etapa.
- **Enter no confirma una ventana si el foco está en otro botón** (elegir "USD" en la seña, "Cancelar" al archivar).
- **El número interno de una ficha a mano no aparece** en títulos de ventanas ni avisos.
- **El presupuesto eliminado mientras era obra vuelve a verse en Presupuestos** cuando la obra sale del tablero.
- **La limpieza nocturna** usa el mismo candado que el resto, guarda el ZIP de una archivada el año entero y no recicla números.

## El código interno de la ficha (18/11/2026)

- **Toda obra nueva nace con un código interno** (serie 90001+, no se muestra), tenga o no N° su contrato. `tablero-cargar.php` ya no aprueba presupuestos: crea la ficha con los datos del contrato, le anota el N° del contrato como dato (`nro_contrato`) y la deja enlazada en el mismo paso.
- **El N° es un dato compartido.** En "Editar obra", cambiar el N° de una ficha con código interno lo cambia en su contrato (`contratos.php`, acción `nro_de_ficha`) y en la ficha (`tablero.php`, acción `nro_contrato`). La ficha no cambia de lugar. Si la ficha no tiene contrato, avisa que primero hay que crearle el contrato.
- **Una ficha con código interno no se muda nunca.** `tablero-editar.php` rechaza el pedido.
- **Una obra, una ficha:** no se carga otra obra con un N° que ya está en el tablero o en su papelera; avisa a nombre de quién está y ofrece "Enlazar con el tablero".
- **En Presupuestos** el presupuesto de esa obra sigue siendo un presupuesto ("Enviado" por dentro), pero se muestra con su etapa y el link "En el tablero →": `list.php` lo encuentra por el N° y devuelve `en_tablero`. Sin versión en el N°, vale para la última versión del presupuesto.
- **Las fichas viejas** que nacieron de un N° de presupuesto (su clave es ese número) siguen funcionando igual: para ellas cambiar el N° sigue siendo una mudanza, con las protecciones del barrido. **No se convierten** (decisión del dueño, 18/11/2026): es más riesgoso tocarlas que dejar que salgan solas al archivarse. Alcanza con que cada una tenga su contrato; el aviso "N sin contrato" del tablero dice cuáles faltan.

## Barrido de diseño del 19/11/2026

Criterio del dueño: "un sistema serio, simple y claro; efectivo y elegante". El detalle, las reglas y lo que cambió respecto de decisiones anteriores están en `01-strategy/decision-log/2026-11-19-barrido-de-diseno.md`. Lo que se ve en el tablero por columnas:

- **Orden de lectura:** el título de la etapa (lo más grande, con su contador en tinta); debajo, un renglón con lo que hay para hacer y "Filtrar"; después los tramos de la cola, cada uno con su rótulo y una raya ("Ya medidas", "Necesitan medirse", "Entrega: semana del 16/11 · esta semana"); y las fichas.
- **Color:** cada etapa es un carril: su color tiñe la columna entera, muy suave, y vuelve en la línea de arriba, en el fondo del contador y en el marco y el número de la ficha próxima. Las fichas son blancas y no llevan el color de la etapa (antes, una barra en cada una). En el título de una semana vencida solo la palabra "vencida" va en rojo, y sus fichas no repiten "Plazo vencido".
- **El color de la etapa tiñe, no escribe (20/11/2026).** Ningún texto va en el color de la etapa sobre el carril teñido: ahí no se lee bien (medido). El contador, "Filtrar", "Próxima" y el rótulo de cada tramo van en tinta o en gris. Donde hay texto blanco sobre el color de la etapa (el número de la próxima, "Pasar a…", las pestañas de etapa en tablet y celular), el color se oscureció apenas en Por fabricar y Por archivar para llegar a 4,5:1.
- **Todo el texto se lee (20/11/2026).** Se midió el contraste de cada texto contra su fondo, en claro y oscuro, en computadora, tablet y celular: todo llega a 4,5:1. El gris chico y el verde se oscurecieron un punto para eso. Los números están en `06-knowledge/layer-0-raw/2026-11-20-contraste-medido.md`; el porqué, en `06-knowledge/layer-2-landscape/como-se-lee-una-pantalla-de-trabajo.md`.
- **Ficha abierta:** los tildes ya puestos se apagan (fondo neutro, solo el cuadrito en verde): lo que resalta es lo que falta. Los hitos son texto con un tilde verde. "Con contrato" es un link gris.
- **Sin descripciones al pasar el mouse.** Lo que decían pasó a `aria-label`.
- **Sin material:** una ficha sin material lo dice ("Sin material", en gris).
- Se sacó el renglón fijo "Por semana de entrega · mismo color junto" (la regla sigue; los títulos de cada semana ordenan la cola).

## Cambios del 19/11/2026

- **"Falta confirmar" en la ficha cerrada.** Si la ficha tiene el turno puesto y el cliente todavía no lo confirmó, la etiqueta se ve sin abrirla, en ámbar (no frena nada). Vale en Por medir y en Por instalar. No aparece con el turno ya pasado ni cuando en Por instalar falta la hora (ahí primero dice "Falta la hora").
- **Una obra instalada no vence más.** Con "Instalado" tildado deja de decir "Vence" o "Plazo vencido" en la ficha, sale del aviso de arriba y, en el calendario, sale de los "Vence", de la franja de entrega y de "Plazo vencido". Sigue en Por instalar hasta la aprobación, con su segundo tilde. Si se desmarca "Instalado", vuelve a contar.
- **Calendario: filtro por día de instalación.** En Filtros, debajo de "Bloque de medición", hay una lista "Día de instalación" con cada día y cuántas obras tiene (las de Por instalar con turno y las ya instaladas). Marcar uno o varios muestra solo esas obras; se puede combinar con bloques de medición y se sale con "Ver todo". El número va en rojo si el día ya pasó y queda alguna sin instalar.
- **Calendario: la instalación hecha se marca en el día de su turno.** Igual que la medición en el suyo: la obra instalada aparece ese día con el tilde violeta adelante, esté en Por instalar o ya en Por archivar (el turno se guarda en el pase de etapa; `historia[].turno`). Si se instaló sin turno cargado, queda en el día en que se marcó "Instalado". Una ficha de la carga inicial, que entró directo en Por archivar, no tiene ese día y no se marca. El filtro "Día de instalación" usa el mismo día.
- **Calendario: al pasar por una obra se marcan todos sus carteles.** Con el mouse (o el foco del teclado) sobre la instalación de una obra, queda marcada también su medición y su vencimiento, y al revés. Solo donde hay mouse: en tablet y celular el toque abre la ficha.
- **Calendario: el turno sin confirmar lleva un "?" ámbar.** Es lo mismo que "Falta confirmar" en la ficha: turno puesto y cliente sin confirmar, en medición e instalación. El confirmado no lleva nada. No aparece con el turno ya pasado ni cuando falta la hora de una instalación. La ayuda del bloque de medición del día dice cuántas faltan.
- **La seña y la nota de la ficha pasan al contrato.** El tablero le avisa a Relación-Contrato al guardarlas. → `relacion-contrato.md`, "La seña y la nota llegan del tablero".

## "Turno confirmado" (18/11/2026)

- **Es el primer tilde de Por medir** (antes de "Medidor") **y de Por instalar** (antes de "Dirección confirmada" e "Instaladores"). Dice que el cliente confirmó su turno.
- **En Por instalar no es un tilde nuevo:** es el que se llamaba "Se avisó al cliente" (`avisado`), con otro nombre. Las obras que ya lo tenían tildado lo conservan. En Por medir sí es nuevo (`turno_ok`).
- **Solo se tilda con el turno puesto:** el día en Por medir; el día y la hora en Por instalar.
- **Se cae solo si cambia el día o la hora**, desde la ficha, el calendario o el Cronograma: lo que el cliente confirmó era el turno anterior. El Cronograma avisa "hay que volver a confirmar".
- **Deja de pedirse cuando la visita ya se hizo** ("Medido" / "Instalado"): una obra ya medida o ya instalada no queda trabada por no tenerlo. Antes de la visita figura entre lo que falta.
- **En el Cronograma** cada obra tiene su casillero "Turno confirmado" y cada día dice cuántas van ("3 de 5 confirmadas"). Es el mismo tilde de la ficha.
- **Qué etapa lo tiene** lo define `confirma` en `$BS_ETAPAS` (`_tablero.php`); la regla de "ya no se pide" está en `bs_tablero_tilde_liberado()`.
- No hay avisos automáticos (se evaluó Telegram y el dueño lo descartó) ni un botón que abra WhatsApp (abre otra ventana, no la que ya está en uso).
