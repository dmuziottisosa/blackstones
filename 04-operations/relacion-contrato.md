# Relación-Contrato

> Lo administrativo de cada obra: el contrato, su total y lo cobrado. Primera versión: 16/11/2026.
> Página: `blackstones.com.ar/presupuestos/relacion-contrato.php` (misma contraseña del equipo).

## Qué es

Un renglón por contrato. Nace cuando el cliente dice que contrata y se le coordina la medición, **antes** de que exista una ficha en el tablero. El tablero y el calendario siguen siendo lo operativo; esto es la plata.

## Campos del renglón

Los definió el dueño (16/11/2026), sobre la planilla que ya usaba:

| Campo | Notas |
|---|---|
| N° de presupuesto | No es obligatorio, pero debería estar: sin N° el renglón muestra "Sin N°" en ámbar |
| Fecha del contrato | Define en qué mes aparece |
| Vendedor | Lista cerrada (dueño, 16/11/2026): Laura, Sol, Diego, Jose, Lara, Dioge. Se edita en `$BS_CONTRATO_VENDEDORES`, en `_contratos.php` |
| Procedencia | Lista cerrada: Alberdi, Publicación/WhatsApp, Publicación/Alberdi. Se edita en `$BS_CONTRATO_PROCEDENCIAS` |
| Cliente, Teléfono, Dirección | El teléfono vale pegado de WhatsApp ("+54 9 11 5555-1234", incluso con los caracteres invisibles que agrega) o como diez dígitos ("1155551234"). Son el mismo número: se guarda y se muestra "11 5555-1234". Se ve debajo del nombre y abre WhatsApp con un toque. El buscador lo encuentra en cualquiera de los dos formatos |
| Contrato, Contrato paralelo | Texto libre (`status: hypothesis`: falta que el dueño defina qué va en cada uno) |
| Material | Sugiere del catálogo de la calculadora ("Marca · Color") |
| Medidas | Texto libre |
| Total del contrato | En dólares y/o en pesos, por separado |
| Seña recibida | Monto, moneda y fecha |
| Pagos | Fecha, monto y cómo: efectivo en pesos, efectivo en dólares o transferencia |
| Saldo pendiente | Se calcula solo, por moneda: total menos seña menos pagos |
| Factura, Muebles colocados | Sí / No (o sin marcar) |
| Pileta | Nuestra / Del cliente / No lleva, igual que en el tablero, así se copia tal cual |
| Medición coordinada | Opcional; es la fecha que se copia al tablero |
| Notas | Caja grande, arriba de la ficha: es donde van los detalles del contrato. Hasta 4000 caracteres, conserva los renglones. El primer renglón se ve en la lista |

## Va por mes, y se lee como una planilla de control

Criterio del dueño (16/11/2026): **no es un tablero de indicadores, es un centro de control de contratos y clientes. Lectura clara, control preciso.** Por eso la pantalla es una planilla, con columnas fijas y números alineados; nada decorativo.

- **Un mes por vez**, según la fecha del contrato, con flechas. El buscador busca en todos los meses.
- **Franja de resumen**, en un renglón: contratos, contratado, cobrado, por cobrar del mes, y por cobrar de todos los meses.
- **Filtros** con su cantidad: Todos, Con saldo, Pagados, Sin cargar al tablero, Sin N°.
- **Columnas:** Fecha, N°, Cliente (debajo: vendedor y procedencia), Material (debajo: medidas), Total, Cobrado, Saldo, Factura, Muebles, Pileta, Tablero y Notas. Fecha y Cliente ordenan la planilla al tocarlas.
- **Pie con la suma** de lo que se está viendo, debajo de Total, Cobrado y Saldo.
- **Un número no contradice a otro:** resumen, filtros y pie cuentan los mismos renglones. Los de prueba cuentan mientras existan, y el cartel lo dice.
- En pantallas medianas se ocultan primero Notas (queda un punto ámbar al lado del nombre) y después Factura / Muebles / Pileta; todo sigue en la ficha. Por debajo de 1100 px cada contrato es una tarjeta.

## El presupuesto del contrato: tres casos

Definido por el dueño (16/11/2026):

1. **Vacío:** no pasa nada. El renglón dice "Sin N°".
2. **Con N° de presupuesto:** el contrato queda enlazado con ese presupuesto de Presupuestos. La ficha lo busca mientras se escribe y muestra de quién es y su total, con el link para verlo; si el N° no existe, lo avisa (y en la planilla queda un "?" al lado del número).
3. **Con el PDF o Excel del presupuesto adjunto:** el contrato queda **VERIFICADO** (sello dorado al lado del nombre, en la planilla y en la ficha).

**Solo el archivo adjunto verifica.** El N° enlaza, pero no pone el sello. Un archivo por contrato (PDF, .xlsx o .xls, hasta 10 MB); subir otro reemplaza al anterior. El filtro "Sin verificar" muestra los que no lo tienen. Al borrar el contrato se borra su archivo.

## Los puntitos de cada renglón

Menú con: copiar N° de presupuesto, copiar teléfono (como diez dígitos, listo para pegar en WhatsApp), copiar dirección y borrar contrato. Lo que el renglón no tiene sale apagado.

## Enlace con el tablero

- **Por defecto el renglón está suelto**: no toca el tablero.
- Hay **dos formas de enlazar**: crear el contrato y usar "Cargar en Por medir", o ponerle al contrato el N° de un presupuesto que **ya está** en el tablero.
- En ese segundo caso el renglón muestra **"Enlazar con el tablero"** en vez de "Cargar en Por medir", y al guardar la ficha se ofrece enlazar ahí mismo. Enlazar no crea ni cambia nada en el tablero.
- **"Cargar en Por medir"** crea la ficha en el tablero y copia solo lo que el tablero ya tiene: cliente, teléfono, dirección, material, pileta, fecha de medición y seña. Total, pagos y el resto quedan acá.
- Si el renglón tiene **N° de presupuesto**, en vez de crear una ficha aprueba ese presupuesto (mismo camino que "Cargar obra" con número). Si ese presupuesto ya estaba en el tablero, solo se enlaza.
- Una ficha del tablero se enlaza con un solo contrato.
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
| Datos | `bs-data/contratos/contratos.json` y `bs-data/contratos/adjuntos/`, aparte de `clientes/` y `tablero/` |

La página no modifica ningún endpoint existente: para crear la ficha usa `tablero-cargar.php` y `tablero.php`, los mismos que usa el tablero.

## Regla crítica: se puede borrar entero sin que se caiga nada

Exigencia del dueño (16/11/2026): **Relación-Contrato se tiene que poder eliminar completa sin afectar el tablero ni el calendario.**

- La dependencia va en un solo sentido: Relación-Contrato **lee** del tablero. El tablero, el calendario, el hub y la calculadora **nunca** leen ni escriben nada de contratos.
- Ningún archivo existente llama a `contratos.php`, `contratos-adjunto.php` ni `_contratos.php`, ni abre `bs-data/contratos/`. Relación-Contrato sí lee `clientes/` (para saber si un N° existe) y el tablero, sin modificarlos. Lo único que la nombra desde afuera es el link de la pestaña en la barra de páginas.
- Las fichas creadas con "Cargar en Por medir" son fichas comunes del tablero: siguen ahí, con su fecha y su seña, aunque el contrato desaparezca.
- Verificado el 16/11/2026 (dos veces, la segunda con los adjuntos ya hechos): con la página, el endpoint y los datos borrados, pasaron la regresión completa del tablero y las pruebas del calendario.

**Para quien toque esto después:** no agregar en `tablero.php`, `calendario.php` ni en sus endpoints nada que dependa de contratos. Si algún día el tablero necesita mostrar algo del contrato, tiene que tolerar que no exista.

Para eliminarla: borrar `presupuestos/relacion-contrato.php`, los tres archivos de `calculadora/api/` (`contratos.php`, `contratos-adjunto.php`, `_contratos.php`) y la carpeta `bs-data/contratos/`, y sacar el link de la pestaña de las cinco páginas que lo tienen (calculadora, presupuestos, tablero, calendario, stock). Si el link queda, lleva a una página que no existe; nada más.

## Lo que esta versión deja afuera a propósito

- Pago en una moneda de un total en otra (pesos contra un total en dólares): no hay conversión; queda como saldo en una moneda y "a favor" en la otra.
- Permisos: hoy todo el equipo ve la plata (decisión del dueño, "por los momentos").
- Exportar a planilla y filtros por vendedor o procedencia.
- Un link directo al día de la obra en el calendario (hoy el link lleva a la ficha del tablero).
