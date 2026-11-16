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

**Solo el archivo adjunto verifica.** El N° enlaza, pero no pone el sello. Un archivo por contrato (PDF, .xlsx o .xls, hasta 10 MB); subir otro reemplaza al anterior. El filtro "Sin verificar" muestra los que no lo tienen. El archivo acompaña al contrato a la papelera y se borra con él a los 30 días.

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

## Carga de la planilla del equipo

Para pasar de una vez la planilla de contratos (la que el equipo llevaba en Excel). Regla del dueño (16/11/2026): **se cargan solo los que están en el tablero**, y el sistema se adapta al formato de la planilla, no al revés.

1. Se sube por FTP `bs-data/contratos/importar.json` (fuera de `public_html`; los datos de clientes no pasan por el repo).
2. Se abre `/calculadora/api/contratos-importar.php` con sesión iniciada. Muestra qué fila va con qué ficha y por qué. No toca nada.
3. Se tilda, se confirma y recién ahí se cargan. El archivo se renombra a `importar-hecho-<fecha>.json`.

Cómo encuentra la ficha de cada fila, en orden: por N° de presupuesto, por teléfono, por nombre igual y por nombre parecido ("Marta" con "Marta Ejemplo"). **Nombre parecido queda sin tildar**: se mira y se tilda a mano. Si dos filas van a la misma ficha, tampoco se tildan solas. El tablero solo se lee.

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

Menú con: copiar N° de presupuesto, copiar teléfono (como diez dígitos, listo para pegar en WhatsApp), copiar dirección y borrar contrato (va a la papelera). Lo que el renglón no tiene sale apagado.

## Enlace con el tablero

- **Por defecto el renglón está suelto**: no toca el tablero.
- Hay **dos formas de enlazar**: crear el contrato y usar "Cargar en Por medir", o ponerle al contrato el N° de un presupuesto que **ya está** en el tablero.
- En ese segundo caso el renglón muestra **"Enlazar con el tablero"** en vez de "Cargar en Por medir", y al guardar la ficha se ofrece enlazar ahí mismo. Enlazar no crea ni cambia nada en el tablero.
- **"Cargar en Por medir"** crea la ficha en el tablero y copia solo lo que el tablero ya tiene: cliente, teléfono, dirección, material, pileta, fecha de medición y seña. Total, pagos y el resto quedan acá.
- Si el renglón tiene **N° de presupuesto**, en vez de crear una ficha aprueba ese presupuesto (mismo camino que "Cargar obra" con número). Si ese presupuesto ya estaba en el tablero, solo se enlaza.
- Una ficha del tablero se enlaza con un solo contrato.
- **Enlazados, la ficha se borra en los dos lados (16/11/2026):** borrar el contrato manda también su ficha del tablero a la papelera (con sus tildes y adjuntos), y borrar la ficha en el tablero manda también el contrato (con sus pagos y su presupuesto adjunto). Los dos avisan antes y se recuperan durante 30 días. Si el contrato no está enlazado, o su ficha ya no está en el tablero, se borra solo. **Archivar no es borrar:** el contrato queda y dice "Fuera del tablero".
- **Borrar el presupuesto no toca nada de esto:** la ficha, el calendario y el contrato siguen (ver el decision log de la etapa).
- **Enlazado, manda el tablero en lo operativo**: cliente, teléfono, dirección, material y pileta se ven como están en la ficha y se cambian desde el tablero. La plata se cambia acá.
- **Distintivo de enlace:** el renglón enlazado lleva una raya verde a la izquierda y la marca "Enlazado" al lado de la etapa; la ficha dice "Enlazado con el tablero y el calendario" (el calendario lee del tablero, así que queda enlazado solo).
- El renglón muestra **la etapa** de la obra (Por medir, Por cortar…), la fecha de medición o instalación y el vencimiento, y lleva a la ficha con un toque.
- **Seña, un solo dato:** si se carga acá y la obra está en Por medir, queda tildada en el tablero ("Trajo la seña"). Si se carga en el tablero, aparece acá sola. Cuando el tablero ya tiene la seña, acá es de solo lectura.
- Si la ficha sale del tablero (archivada o borrada) el renglón dice "Fuera del tablero" y conserva la última seña.

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
- **Única excepción, a prueba de ausencia:** al borrar o restaurar una ficha, el tablero llama a `contratos.php` (`?enlace=` para avisar, `borrar_enlazado` y `restaurar_enlazado`). Si el archivo no existe, la llamada falla en silencio y el tablero borra la ficha igual. Probado el 16/11/2026 con Relación-Contrato borrada.
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
