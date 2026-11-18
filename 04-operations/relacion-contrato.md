# Relación-Contrato

> Lo administrativo de cada obra: el contrato, su total y lo cobrado. Primera versión: 16/11/2026.
> Página: `blackstones.com.ar/presupuestos/relacion-contrato.php` (misma contraseña del equipo).

## Qué es

Un renglón por contrato. Nace cuando el cliente dice que contrata y se le coordina la medición, **antes** de que exista una ficha en el tablero. El tablero y el calendario siguen siendo lo operativo; esto es la plata.

## Campos del renglón

Los definió el dueño (16/11/2026), sobre la planilla que ya usaba:

| Campo | Notas |
|---|---|
| Contrato (N° de la calculadora) | Es la columna "contrato" de la planilla del equipo ("0042 - calcu"): el N° del presupuesto. No es obligatorio, pero debería estar: sin N° el renglón muestra "Sin N°" en ámbar |
| Ctto paralelo | El número del contrato paralelo, como en la planilla. En la lista se ve debajo del N° |
| Fecha del contrato | Define en qué mes aparece |
| Vendedor | Lista cerrada (dueño, 16/11/2026): Laura, Sol, Diego, Jose, Lara, Dioge. Se edita en `$BS_CONTRATO_VENDEDORES`, en `_contratos.php` |
| Procedencia | Lista cerrada: Alberdi, Alberdi viejo, Publicación, Publicación/WhatsApp, Publicación/Alberdi ("Alberdi viejo" y "Publicación" se sumaron porque así figuran en la planilla). Se edita en `$BS_CONTRATO_PROCEDENCIAS` |
| Cliente, Teléfono, Dirección | El teléfono vale pegado de WhatsApp ("+54 9 11 5555-1234", incluso con los caracteres invisibles que agrega) o como diez dígitos ("1155551234"). Son el mismo número: se guarda y se muestra "11 5555-1234". Se ve debajo del nombre y abre WhatsApp con un toque. El buscador lo encuentra en cualquiera de los dos formatos |
| Material | Sugiere del catálogo de la calculadora ("Marca · Color") |
| Medidas | Texto libre |
| Total del contrato | En dólares y/o en pesos, por separado |
| Seña recibida | Monto, moneda y fecha. Si se cobró en una moneda y el contrato está en la otra, lleva la cotización del dólar (ver abajo). Debajo dice qué porcentaje del total es |
| Pagos | Fecha, monto y cómo: efectivo en pesos, efectivo en dólares o transferencia. Con cotización cuando hace falta |
| Saldo pendiente | Se calcula solo: total menos seña menos pagos, ya convertidos. "Cobrado" muestra lo que entró de verdad, en cada moneda |
| Factura | Sí / No / Cons. final (o sin marcar) |
| Muebles colocados | Sí / No (o sin marcar) |
| Pileta | Nuestra / Del cliente / No lleva, igual que en el tablero, así se copia tal cual |
| Medición coordinada | Opcional; es la fecha que se copia al tablero |
| Notas | Caja grande, arriba de la ficha: es donde van los detalles del contrato. Hasta 4000 caracteres, conserva los renglones. El primer renglón se ve en la lista |

## Va por mes, y se lee como una planilla de control

Criterio del dueño (16/11/2026): **no es un tablero de indicadores, es un centro de control de contratos y clientes. Lectura clara, control preciso.** Por eso la pantalla es una planilla, con columnas fijas y números alineados; nada decorativo.

- **Un mes por vez**, según la fecha del contrato, con flechas. El buscador busca en todos los meses.
- **Franja de resumen**, en un renglón: contratos (y cuántos están completos), contratado, cobrado, por cobrar del mes, y por cobrar de todos los meses.
- **Filtros** con su cantidad: Todos, Con saldo, Pagados, Sin cargar al tablero, Sin verificar, Sin N°, Completos.
- **Columnas:** Fecha, Contrato (el N° y debajo el ctto paralelo), Cliente (debajo: vendedor y procedencia), Material (debajo: medidas), Total, Cobrado, Saldo, Factura, Muebles, Pileta, Tablero y Notas. Fecha y Cliente ordenan la planilla al tocarlas.
- **Pie con la suma** de lo que se está viendo, debajo de Total, Cobrado y Saldo.
- **Un número no contradice a otro:** resumen, filtros y pie cuentan los mismos renglones. Los de prueba cuentan mientras existan, y el cartel lo dice.
- En pantallas medianas se ocultan primero Notas (queda un punto ámbar al lado del nombre) y después Factura / Muebles / Pileta; todo sigue en la ficha. Por debajo de 1100 px cada contrato es una tarjeta.

## El presupuesto del contrato: tres casos

Definido por el dueño (16/11/2026):

1. **Vacío:** no pasa nada. El renglón dice "Sin N°".
2. **Con N° de presupuesto:** el contrato queda enlazado con ese presupuesto de Presupuestos. La ficha lo busca mientras se escribe y muestra de quién es y su total, con el link para verlo; si el N° no existe, lo avisa (y en la planilla queda un "?" al lado del número).
3. **Con el PDF o Excel del presupuesto adjunto:** el contrato queda **VERIFICADO** (sello dorado al lado del nombre, en la planilla y en la ficha).

**Solo el archivo adjunto verifica.** El N° enlaza, pero no pone el sello. Un archivo por contrato (PDF, .xlsx o .xls, hasta 10 MB); subir otro reemplaza al anterior. El límite de Hostinger no es problema: `uploadMaxFilesize` y `postMaxSize` están en 1024M (verificado por el dueño en el panel, 17/11/2026; ver `deploy-notes.md`, sección 1 bis). El filtro "Sin verificar" muestra los que no lo tienen. El archivo acompaña al contrato a la papelera y se borra con él a los 30 días.

### Por qué solo el archivo verifica

El presupuesto dura **180 días** en la pestaña Presupuestos y después se borra (regla del dueño, 16/11/2026; ver `01-strategy/decision-log/2026-11-16-la-etapa-la-dicta-el-tablero.md`). Pasado ese tiempo el N° deja de existir: la planilla muestra un "?" al lado del número y la ficha lo explica. El archivo adjunto no se borra: es lo que queda del presupuesto. Los contratos y sus adjuntos no se borran solos.

## Cobros en otra moneda: la cotización

Decisión del dueño (16/11/2026). Los contratos se hacen en pesos y muchas veces se cobra en dólares. Sin cotización, el saldo quedaba partido ("debe pesos" y "a favor dólares").

- Un cobro (seña o pago) se descuenta del total de **su misma moneda**. Si ahí no queda saldo, o el contrato no tiene total en esa moneda, se descuenta del otro y **pide la cotización del dólar**.
- El campo viene con el **dólar del día** (`/calculadora/dolar.php`, el mismo que usa la calculadora) y se puede cambiar. Cada cobro guarda la suya: `cotizacion` y `aplica` (de qué total se descuenta).
- En la lista de pagos queda a la vista: "Efectivo · a $ 1.580 = $ 790.000 · USD 500". **Cobrado** muestra lo que entró de verdad (USD 500); **Saldo**, lo que falta ya convertido.
- **Porcentaje de la seña:** debajo de la seña dice qué parte del total es. Lo habitual es 50%, o 75% si no hay muebles; si queda por debajo se pone en ámbar. Informa, no bloquea (el dueño: "no podemos confiar ciegamente").
- La seña que llega del tablero no trae cotización: el contrato conserva la que tenga anotada para esa moneda.

## Completo: cuándo un contrato cierra limpio

Definición del dueño (16/11/2026): un contrato está **completo** con tres cosas: **N° puesto, presupuesto adjunto y enlazado con el tablero**.

- La ficha lo muestra arriba, con un punto verde por cada una.
- El resumen del mes dice "X de N completos" y hay un filtro "Completos". Lo que falta se persigue con los otros filtros (Sin N°, Sin verificar, Sin cargar al tablero).

## Obras en el tablero sin contrato

Cada obra viva del tablero tendría que tener su contrato. Si hay alguna que no, la página lo avisa arriba ("Hay 3 obras en el tablero sin contrato") y las lista.

- **Crear contrato** arma el renglón con lo que la ficha ya tiene (cliente, teléfono, dirección, material, pileta, seña) y lo deja **enlazado**. Queda con fecha de hoy y sin total: eso se completa a mano. Si la obra vino de la calculadora, lleva su N°.
- **Crear los N** hace lo mismo para todas.
- Una ficha no puede tener dos contratos (el servidor lo rechaza).

## Papelera (30 días)

Decisión del dueño (16/11/2026): nada se borra de una.

- **Borrar contrato** lo manda a la papelera con sus pagos y su presupuesto adjunto. Se recupera desde el botón "Papelera" durante 30 días; después se borra de verdad.
- Si estaba enlazado, su ficha va a la papelera del tablero y vuelven los dos juntos al restaurar cualquiera.
- Los renglones de prueba no pasan por la papelera.

## Carga de los contratos de las fichas del tablero

Regla del dueño (16 y 17/11/2026): **se parte del tablero.** Cada ficha viva recibe su contrato; los datos salen de la planilla que el equipo llevaba en Excel cuando esa ficha está ahí. Las filas de la planilla sin ficha en el tablero **no se cargan**. El sistema se adapta al formato de la planilla, no al revés.

La primera versión hacía el camino inverso (recorría la planilla y buscaba fichas) y el dueño la frenó antes de correrla: "iba a cargar de más".

1. Se sube por FTP `bs-data/contratos/importar.json` (fuera de `public_html`; los datos de clientes no pasan por el repo). Es solo la fuente donde se busca.
2. Se abre `/calculadora/api/contratos-importar.php` con sesión iniciada. Muestra, ficha por ficha, qué contrato le toca y por qué. No toca nada.
3. Se confirma y recién ahí se cargan, enlazados. El archivo subido **se borra** del servidor.

Cómo encuentra el contrato de cada ficha, en orden: por N° de presupuesto, por teléfono y por nombre.

**Un nombre solo no alcanza** (17/11/2026). En la planilla y en el tablero los clientes figuran casi siempre con el nombre de pila, y hay varios iguales. Para aceptar una coincidencia por nombre tiene que acompañarla algo más:

- el **teléfono casi igual** (diez dígitos con hasta dos cambiados: error de tipeo),
- la **misma dirección** (comparten la altura y una palabra de la calle), o
- la **misma seña** (lo cobrado según la planilla, en pesos, con hasta $ 1.000 de diferencia).

Si nada de eso coincide, la ficha se toma como "no está en la planilla" y la vista previa nombra al homónimo que descartó. Se llegó a esta regla simulando la carga contra el tablero exportado el 16/11: con nombre y fecha solos, una ficha quedaba enlazada con el contrato de otro cliente del mismo nombre.

**La fecha de medición desempata** (dueño, 17/11/2026). El contrato se firma cerca de la medición: en la planilla de octubre y noviembre, de 33 días antes a 5 días después. Entre dos filas posibles va la de fecha más cercana; si no alcanza para decidir, queda **sin tildar** con el motivo escrito.

**Ficha que no está en la planilla:** el contrato se arma con lo que tiene la ficha (cliente, teléfono, dirección, material, pileta, seña), con la fecha de la medición y sin total. Queda una nota en el contrato.

**La seña no se pisa.** El contrato se carga con lo que dice la planilla, tal cual (seña y pagos, con su cotización si fueron en dólares). Si la seña del tablero es otra, la vista previa lo avisa y el contrato va a mostrar el aviso; no se cambia ninguno de los dos.

**Antes de subir la planilla, se recorta.** Con el tablero exportado se simula la carga en local y al servidor viajan solo las filas que resultaron ser de una ficha. Las demás no salen de la máquina.

El tablero solo se lee.

Cómo se pasó la plata de la planilla (16/11/2026):

| En la planilla | En el sistema |
|---|---|
| "Total contrato" | Total en pesos |
| "Seña" (lo cobrado hasta hoy, en pesos) | Si el detalle de cobros suma eso: el primer cobro es la seña y los demás, pagos |
| Cobro en dólares | Pago en USD con la cotización que resulta de la propia planilla (1530 a 1570), descontado de los pesos |
| Detalle que no suma lo cobrado, o sin detalle | Un solo cobro por el total cobrado, y una nota en el contrato para revisarlo |
| "0042 - calcu" | N° de contrato 0042 |
| Vendedor "solci", "lau" | Sol, Laura |

`status: hypothesis`: las cuatro columnas de montos en pesos no tenían encabezado en lo que se pegó, así que el medio (efectivo o transferencia) queda sin marcar.

## Los puntitos de cada renglón

Menú con: copiar N° de presupuesto, copiar teléfono (como diez dígitos, listo para pegar en WhatsApp), copiar dirección, **desenlazar del tablero** y borrar contrato (va a la papelera). Lo que el renglón no tiene sale apagado.

**Desenlazar (17/11/2026)** no borra nada: el contrato queda suelto, con su total y sus pagos, y la ficha sigue en el tablero y en el calendario. Antes de soltarlo, el contrato se queda con los datos que venían de la ficha (cliente, teléfono, dirección, material). Se puede volver a enlazar.

## Enlace con el tablero

- **Por defecto el renglón está suelto**: no toca el tablero.
- Hay **tres formas de enlazar**, todas desde acá:
  1. Crear el contrato y usar **"Cargar en Por medir"**: crea la ficha, ya enlazada.
  2. **"Enlazar con el tablero"**: aparece sola en el renglón cuando la obra de ese contrato ya está en el tablero. La reconoce por el **N° de presupuesto** o, si no, por el **teléfono** (los diez dígitos iguales y una sola ficha sin contrato con ese teléfono). Muestra la obra antes de enlazar, y al guardar la ficha del contrato se ofrece ahí mismo. No crea ni cambia nada en el tablero.
  3. **"Obras en el tablero sin contrato" → "Crear contrato"**: el camino inverso, desde la ficha.
- **Una ficha cargada a mano no tiene N° de presupuesto** (17/11/2026): por número no se la busca, aunque el N° del contrato coincida con su número interno. Se enlaza por teléfono o por la vía 3.
- El enlace es un dato aparte del N°: sacarle o corregirle el N° a un contrato no lo desenlaza. Para eso está "Desenlazar".
- **"Cargar en Por medir"** crea la ficha en el tablero y copia solo lo que el tablero ya tiene: cliente, teléfono, dirección, material, pileta, fecha de medición y seña. Total, pagos y el resto quedan acá.
- Si el renglón tiene **N° de presupuesto**, en vez de crear una ficha aprueba ese presupuesto. Si ese presupuesto ya estaba en el tablero, avisa y ofrece "Enlazar con el tablero". **Si ese N° es el presupuesto de otra persona, no se carga** y dice de quién es cada uno (18/11/2026).
- **Es la única forma de cargar una obra (18/11/2026, decisión final del dueño).** El tablero ya no tiene "Cargar obra". Un contrato sin enlazar dice **"No existe en el tablero"** y ofrece el botón.
- **El Operador también (18/11/2026):** crea y edita contratos, carga la seña y los cobros, adjunta el presupuesto, borra contratos y ve la papelera. No ve el resumen de plata del mes ni carga renglones de prueba.
- Una ficha del tablero se enlaza con un solo contrato.
- **Enlazados, la ficha se borra en los dos lados (16/11/2026):** borrar el contrato manda también su ficha del tablero a la papelera (con sus tildes y adjuntos), y borrar la ficha en el tablero manda también el contrato (con sus pagos y su presupuesto adjunto). Los dos avisan antes y se recuperan durante 30 días. Si el contrato no está enlazado, o su ficha ya no está en el tablero, se borra solo. **Archivar no es borrar:** el contrato queda y dice "Fuera del tablero".
- **Borrar el presupuesto no toca nada de esto:** la ficha, el calendario y el contrato siguen (ver el decision log de la etapa).
- **Enlazado, manda el tablero en lo operativo**: cliente, teléfono, dirección, material y pileta se ven como están en la ficha y se cambian desde el tablero. La plata se cambia acá.
- **Distintivo de enlace:** el renglón enlazado lleva una raya verde a la izquierda y la marca "Enlazado" al lado de la etapa; la ficha dice "Enlazado con el tablero y el calendario" (el calendario lee del tablero, así que queda enlazado solo).
- **Del otro lado también se ve (17/11/2026):** en el tablero, la ficha abierta de una obra con contrato muestra la marca "Con contrato", que lleva directo a ese contrato (`relacion-contrato.php?contrato=<id>` lo abre en su mes). Solo en la ficha abierta, no en la plegada.
- El renglón muestra **la etapa** de la obra (Por medir, Por cortar…), la fecha de medición o instalación y el vencimiento, y lleva a la ficha con un toque.
- **La seña del tablero y la del contrato no se pisan nunca (dueño, 17/11/2026).** Si difieren, hay un aviso y nada más: el renglón dice "Seña distinta en el tablero" (con los dos números al pasar el mouse), la ficha del contrato lo explica debajo de la seña, y aparece un filtro "Seña distinta". Cada una se corrige a mano donde esté mal.
  - No cuenta como diferencia la misma plata dicha de otra forma: la seña en dólares con su cotización contra la del tablero en pesos, o el tablero con todo lo cobrado anotado como seña.
  - Sí avisa si un lado tiene seña y el otro no.
  - Antes (16/11) la del tablero reemplazaba a la del contrato al abrir la página, y con la seña en el tablero el campo quedaba de solo lectura. Eso se sacó: cambiaba la plata sin que nadie lo decidiera.
  - Lo único que sigue copiándose es al llenar un lado vacío en un momento explícito: "Cargar en Por medir" y "Crear contrato" desde la ficha llevan la seña, y guardar un contrato con seña la tilda en el tablero si la obra está en Por medir y el tablero todavía no la tiene (la ficha lo avisa antes de guardar).
- **Enlazado con una ficha que ya no está entre las obras vivas, se dice por qué** (17/11/2026). El contrato conserva la última seña.
  - **Archivada:** la obra se entregó. Es lo normal; el contrato queda.
  - **En la papelera:** su ficha está en la papelera del tablero.
  - **No existe en el tablero:** la ficha se borró o le cambiaron el N° de presupuesto desde "Editar obra". Es un enlace roto: el renglón lo marca en rojo con el botón **Desenlazar**, y la ficha del contrato lo dice arriba ("Esta ficha no existe en el tablero"). Desenlazado, si la obra sigue en el tablero con otro número, el renglón vuelve a ofrecer "Enlazar con el tablero".

## El N° del contrato en la ficha del tablero (17/11/2026)

**Regla (dueño, 17/11/2026): enlazados, el contrato y la ficha comparten el N°.** El enlace sigue siendo un dato aparte (se puede enlazar sin número y desenlazar sin borrar nada), pero mientras están enlazados se ve el mismo número en los dos lados.

Una ficha cargada a mano dice "Sin N°". Si está enlazada con un contrato que tiene N°, **muestra ese número** en el tablero y en el calendario, y se la encuentra buscándolo.

- **El dueño del dato es el contrato.** Se le pasa a la ficha al guardar el contrato, al enlazarlo y cada vez que alguien que puede editar contratos abre Relación-Contrato. Cambiar el N° en el contrato lo cambia en la ficha.
- **Si el contrato se desenlaza, se borra o se queda sin N°, la ficha vuelve a "Sin N°".**
- **No muda la ficha a ningún presupuesto.** Es solo el número que se muestra: sirve aunque ese presupuesto ya no exista en la calculadora. Atar la ficha al presupuesto real sigue siendo "Editar obra" en el tablero.
- **Y al revés: si en el tablero le cambian el N° a la ficha ("Editar obra"), el contrato la sigue.** Queda enlazado con la ficha nueva y toma ese N°; no se toca nada más del contrato. Antes el contrato quedaba apuntando a una ficha que ya no existía. Lo hace la acción `mudar_enlazado` de `contratos.php`, que llama la página del tablero después de mudar la ficha; solo actúa si la ficha vieja ya no está y la nueva sí.
- **Una ficha que viene de un presupuesto no lo lleva:** ya tiene su número.
- El contrato paralelo ("ctto") no se pasa; es un dato solo del contrato.
- Cómo está hecho: la página de Relación-Contrato llama a `tablero.php` con la acción `nro_contrato` (`pasarNros()`), y el número queda guardado en la ficha. El tablero y el calendario no leen nada de contratos: si Relación-Contrato se borra, las fichas conservan el último número que tenían.

## Enlazados: qué comparten el contrato y la ficha (17 y 18/11/2026)

La idea del dueño: una vez enlazados, que se aproveche al máximo lo que cada lado ya sabe, sin cargar dos veces.

- **El contrato completa lo que le falta a la ficha: nombre y apellido, teléfono y dirección.** Un presupuesto hecho sin saber el nombre trae el teléfono en su lugar y casi nunca trae la dirección; al cargarlo en Por medir la ficha entraba "sin nombre" y sin dónde ir a medir, aunque el contrato lo tuviera. Ahora Relación-Contrato se lo pasa.
  - **Nunca pisa un dato que la ficha ya tiene.** Solo llena el que falta (el nombre "falta" si no tiene letras). Si difieren, no se toca ninguno; enlazados, esos datos se siguen cambiando desde el tablero.
  - Se hace al cargar en Por medir, al enlazar y cada vez que alguien que puede editar contratos abre Relación-Contrato.
  - Como la ficha de un presupuesto comparte el cliente con ese presupuesto, el dato también queda en la pestaña Presupuestos.
  - Cómo está hecho: `pasarNombres()` llama a `tablero-editar.php`. El tablero no lee nada de contratos.
- **Dentro del contrato se ve lo del tablero.** La ventana del contrato (también la de solo mirar) muestra etapa, medición (coordinada o hecha, y quién midió), cuándo vence la entrega, el turno de instalación con el equipo, cuántas fotos y planos tiene, la nota del tablero y, plegado, el **historial del tablero** con sus tildes y fechas. Es solo lectura y sale de lo que el tablero ya manda (`tableroHtml()`): no se guarda nada en el contrato.
- **"Enlazado" es un link a la ficha.** En el renglón de la lista y dentro del contrato, tocarlo abre el tablero con esa ficha. Es el camino inverso de "Con contrato" en la ficha, que lleva al contrato.
- El N° también se comparte: ver "El N° del contrato en la ficha del tablero", arriba.

## Filtrar por etapa y ver las que no tienen turno (18/11/2026)

- **Selector "Etapa"**, al final de los filtros: deja solo los contratos cuya obra está en esa etapa del tablero, o los que todavía no están cargados. Ofrece solo las etapas que hay en el mes, con su cantidad.
- **Filtro "Sin turno":** obras en Por medir sin día de medición, y obras en Por instalar sin día o sin hora de instalación.
- **Se combinan:** "Por instalar" + "Sin turno" muestra las instalaciones que falta agendar. Los números de los demás filtros acompañan a la etapa elegida.

## Exportar a CSV (18/11/2026)

Botón "Exportar", arriba. Baja **lo que está en pantalla**: el mes, la búsqueda y los filtros que estén puestos. Trae los datos del contrato, la plata (total, cobrado, saldo y seña; solo si quien exporta la puede ver) y lo del tablero (etapa, medición, instalación, plazo, si le falta el turno). Mismo formato que "Exportar tablero": se abre directo en Excel. **El archivo tiene nombres, teléfonos y direcciones de clientes: no se sube al repo.**

## Material "Por definir" (18/11/2026)

En el contrato, en "Cargar obra" y en "Editar obra", la primera opción del campo Material es **"Por definir"**, para cargar la obra aunque el cliente todavía no haya elegido el color. Se guarda como texto, igual que cualquier material; cuando elige, se cambia desde "Editar obra".

## "Cargar en Por medir" carga o avisa (18/11/2026)

Crea la ficha en Por medir, vacía, y la enlaza. Si el presupuesto ya está en el tablero, **avisa y no hace nada**: no busca una ficha para enlazarla por su cuenta. Para enlazar con una ficha que ya existe está "Enlazar con el tablero", que muestra de quién es antes de confirmar. → `01-strategy/decision-log/2026-11-18-un-numero-una-persona.md`

## Renglones de prueba

Con la lista vacía aparece "Cargar 3 de prueba". Quedan marcados PRUEBA, no cuentan en "Por cobrar" y se borran juntos con "Borrar los de prueba" (si alguno se cargó en el tablero, esa ficha de prueba también se borra).

## Dónde vive

| Qué | Dónde |
|---|---|
| Página | `site/public_html/presupuestos/relacion-contrato.php` |
| Endpoint | `site/public_html/calculadora/api/contratos.php` |
| Subir y bajar el presupuesto adjunto | `site/public_html/calculadora/api/contratos-adjunto.php` |
| Listas, rutas y lo compartido | `site/public_html/calculadora/api/_contratos.php` (vendedores y procedencias se editan ahí) |
| Carga de la planilla | `site/public_html/calculadora/api/contratos-importar.php` |
| Datos | `bs-data/contratos/contratos.json` y `bs-data/contratos/adjuntos/`, aparte de `clientes/` y `tablero/` |

La página no modifica ningún endpoint existente: para crear la ficha usa `tablero-cargar.php` y `tablero.php`, los mismos que usa el tablero.

## Regla crítica: se puede borrar entero sin que se caiga nada

Exigencia del dueño (16/11/2026): **Relación-Contrato se tiene que poder eliminar completa sin afectar el tablero ni el calendario.**

- Relación-Contrato **lee** del tablero y de `clientes/`. El calendario, el hub y la calculadora **nunca** leen ni escriben nada de contratos.
- **Única excepción, a prueba de ausencia:** el tablero llama a `contratos.php` en dos momentos: al cargar, para saber qué fichas tienen contrato y mostrar la marca "Con contrato" (`?enlaces=1`, una sola consulta, aparte y sin esperar la respuesta); y al borrar o restaurar una ficha (`?enlace=` para avisar, `borrar_enlazado` y `restaurar_enlazado`). Si el archivo no existe, la llamada falla en silencio: el tablero no muestra la marca y borra la ficha igual. Probado el 16 y el 17/11/2026 con Relación-Contrato borrada.
- Ningún archivo existente llama a `contratos.php`, `contratos-adjunto.php` ni `_contratos.php`, ni abre `bs-data/contratos/`. Relación-Contrato sí lee `clientes/` (para saber si un N° existe) y el tablero, sin modificarlos. Lo único que la nombra desde afuera es el link de la pestaña en la barra de páginas.
- Las fichas creadas con "Cargar en Por medir" son fichas comunes del tablero: siguen ahí, con su fecha y su seña, aunque el contrato desaparezca.
- Verificado el 16/11/2026 (dos veces, la segunda con los adjuntos ya hechos): con la página, el endpoint y los datos borrados, pasaron la regresión completa del tablero y las pruebas del calendario.

**Para quien toque esto después:** no agregar en `tablero.php`, `calendario.php` ni en sus endpoints nada que dependa de contratos. Si algún día el tablero necesita mostrar algo del contrato, tiene que tolerar que no exista.

Para eliminarla: borrar `presupuestos/relacion-contrato.php`, los cuatro archivos de `calculadora/api/` (`contratos.php`, `contratos-adjunto.php`, `contratos-importar.php`, `_contratos.php`) y la carpeta `bs-data/contratos/`, y sacar el link de la pestaña de las cinco páginas que lo tienen (calculadora, presupuestos, tablero, calendario, stock). Si el link queda, lleva a una página que no existe; nada más.

## Lo que esta versión deja afuera a propósito

- El medio de la seña (efectivo o transferencia): hoy solo lo llevan los pagos.
- Un respaldo automático diario de los datos (la papelera cubre el borrado por error, no un problema del servidor).
- Permisos: hoy todo el equipo ve la plata (decisión del dueño, "por los momentos").
- Exportar a planilla y filtros por vendedor o procedencia.
- Un link directo al día de la obra en el calendario (hoy el link lleva a la ficha del tablero).

## Barrido de errores del 18/11/2026: lo que cambió en contratos

- **La seña que el contrato le pasa al tablero no pisa una que ya esté** (se manda con "solo si está vacía").
- **Al completar datos de la ficha desde el contrato, no se toca una ficha que parece de otra persona** (teléfonos distintos o nombres sin nada en común). En el registro figura como automático.
- **Enlazar un contrato sin N° con la ficha de un presupuesto le pone ese N°.**
- **"Sin cargar al tablero"** significa lo mismo en el filtro y en el selector de etapa: contratos sin enlace.
- **Exportar:** un saldo a favor sale como número (Excel lo suma); sin contratos, no exporta lo anterior.
- **Borrar un contrato enlazado** se frena si en ese momento no se pudo leer el tablero.
- **Cierre del mes:** los presupuestos adjuntos de contratos sin ficha en ese cierre van en `contratos-sin-ficha/`.
- **Pendiente de decisión:** un cobro queda atado a la moneda del total que había cuando se cargó; avisar si dos contratos tienen el mismo N°; qué mostrar cuando la obra archivada de un contrato cumple su año y se borra.

## El código interno (18/11/2026)

- "Cargar en Por medir" crea siempre una ficha con código interno, tenga o no N° el contrato, y el servidor la deja enlazada en el mismo paso. El N° del contrato viaja a la ficha como dato.
- Cambiar el N° en el contrato lo cambia en la ficha (al abrir Relación-Contrato), y cambiarlo en "Editar obra" del tablero lo cambia en el contrato. El enlace no depende del N° y no se rompe.
- Si el N° del contrato ya está en el tablero, no se carga otra obra: se usa "Enlazar con el tablero". Si ese N° es el presupuesto de otra persona, tampoco se carga.
- La confirmación avisa si el presupuesto de ese N° figura a nombre de otro.
