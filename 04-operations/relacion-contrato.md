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
| Vendedor, Fecha del contrato, Procedencia | Texto libre; vendedor y procedencia sugieren lo ya usado |
| Cliente, Teléfono, Dirección | |
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

## Va por mes

La lista muestra **un mes por vez**, según la fecha del contrato, con flechas para ir al anterior y al siguiente (decisión del dueño, 16/11/2026). Arriba, cuatro números del mes: contratos, contratado, cobrado y por cobrar; "por cobrar" suma además lo pendiente de todos los meses. El buscador busca en todos los meses a la vez. Un mes vacío ofrece ir al último que tiene contratos.

Cada renglón lleva el día del contrato como un almanaque, una barrita con cuánto del total ya entró y, si tiene, el primer renglón de la nota.

## Enlace con el tablero

- **Por defecto el renglón está suelto**: no toca el tablero.
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
| Datos | `bs-data/contratos/contratos.json`, aparte de `clientes/` y `tablero/` |

La página no modifica ningún endpoint existente: para crear la ficha usa `tablero-cargar.php` y `tablero.php`, los mismos que usa el tablero.

## Regla crítica: se puede borrar entero sin que se caiga nada

Exigencia del dueño (16/11/2026): **Relación-Contrato se tiene que poder eliminar completa sin afectar el tablero ni el calendario.**

- La dependencia va en un solo sentido: Relación-Contrato **lee** del tablero. El tablero, el calendario, el hub y la calculadora **nunca** leen ni escriben nada de contratos.
- Ningún archivo existente llama a `contratos.php` ni abre `bs-data/contratos/`. Lo único que la nombra desde afuera es el link de la pestaña en la barra de páginas.
- Las fichas creadas con "Cargar en Por medir" son fichas comunes del tablero: siguen ahí, con su fecha y su seña, aunque el contrato desaparezca.
- Verificado el 16/11/2026: con la página, el endpoint y los datos borrados, pasaron la regresión completa del tablero y las pruebas del calendario.

**Para quien toque esto después:** no agregar en `tablero.php`, `calendario.php` ni en sus endpoints nada que dependa de contratos. Si algún día el tablero necesita mostrar algo del contrato, tiene que tolerar que no exista.

Para eliminarla: borrar `presupuestos/relacion-contrato.php`, `calculadora/api/contratos.php` y la carpeta `bs-data/contratos/`, y sacar el link de la pestaña de las cinco páginas que lo tienen (calculadora, presupuestos, tablero, calendario, stock). Si el link queda, lleva a una página que no existe; nada más.

## Lo que esta versión deja afuera a propósito

- Pago en una moneda de un total en otra (pesos contra un total en dólares): no hay conversión; queda como saldo en una moneda y "a favor" en la otra.
- Permisos: hoy todo el equipo ve la plata (decisión del dueño, "por los momentos").
- Exportar a planilla y filtros por vendedor o procedencia.
- Un link directo al día de la obra en el calendario (hoy el link lleva a la ficha del tablero).
