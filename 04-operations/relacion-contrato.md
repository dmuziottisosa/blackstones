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
| Nota | |

## Enlace con el tablero

- **Por defecto el renglón está suelto**: no toca el tablero.
- **"Cargar en Por medir"** crea la ficha en el tablero y copia solo lo que el tablero ya tiene: cliente, teléfono, dirección, material, pileta, fecha de medición y seña. Total, pagos y el resto quedan acá.
- Si el renglón tiene **N° de presupuesto**, en vez de crear una ficha aprueba ese presupuesto (mismo camino que "Cargar obra" con número). Si ese presupuesto ya estaba en el tablero, solo se enlaza.
- Una ficha del tablero se enlaza con un solo contrato.
- **Enlazado, manda el tablero en lo operativo**: cliente, teléfono, dirección, material y pileta se ven como están en la ficha y se cambian desde el tablero. La plata se cambia acá.
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

## Lo que esta versión deja afuera a propósito

- Pago en una moneda de un total en otra (pesos contra un total en dólares): no hay conversión; queda como saldo en una moneda y "a favor" en la otra.
- Permisos: hoy todo el equipo ve la plata (decisión del dueño, "por los momentos").
- Exportar a planilla y filtros por vendedor o procedencia.
